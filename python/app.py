from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import os
import cv2
import numpy as np
import json
from datetime import datetime
from werkzeug.utils import secure_filename
import logging

app = Flask(__name__)
app.secret_key = 'your_secret_key_here'
CORS(app, resources={r"/upload": {"origins": ["https://seal-app-kntr3.ondigitalocean.app/"]}}, supports_credentials=True)

# Configuration
UPLOAD_DIR = 'uploads'
RESULTS_DIR = 'results'

# Ensure directories exist
os.makedirs(UPLOAD_DIR, exist_ok=True)
os.makedirs(RESULTS_DIR, exist_ok=True)

# ===== MATLAB Function Conversions =====

def PCANoiseLevelEstimator(image, Bsize):
    """Python implementation of PCANoiseLevelEstimator.m"""
    # Parameters
    UpperBoundLevel = 0.0005
    UpperBoundFactor = 3.1
    M1 = Bsize
    M2 = Bsize
    M = M1 * M2
    EigenValueCount = 7
    EigenValueDiffThreshold = 49.0
    LevelStep = 0.05
    MinLevel = 0.06
    MaxClippedPixelCount = round(0.1 * M)
    
    # Initialize
    label = 0
    
    # Compute block info
    block_info = []
    for y in range(1, image.shape[0] - M2 + 1):
        for x in range(1, image.shape[1] - M1 + 1):
            sum1 = 0.0
            sum2 = 0.0
            clipped_pixel_count = 0
            
            for by in range(y, y + M2):
                for bx in range(x, x + M1):
                    val = image[by-1, bx-1]  # Adjust for 0-indexing
                    sum1 += val
                    sum2 += val * val
                    if val == 0 or val == 255:
                        clipped_pixel_count += 1
            
            if clipped_pixel_count <= MaxClippedPixelCount:
                var = (sum2 - sum1*sum1/M) / M
                block_info.append([var, x, y])
    
    if len(block_info) == 0:
        label = 1
        return label, np.sqrt(np.var(image))
    
    # Sort blocks by variance
    block_info = sorted(block_info, key=lambda x: x[0])
    block_info = np.array(block_info)
    
    # Find first non-zero variance index
    nozero_idx = np.where(block_info[:, 0] > 0)[0]
    if len(nozero_idx) == 0:
        nozero_idx = 0
    else:
        nozero_idx = nozero_idx[0]
    
    # Compute upper bound
    max_idx = len(block_info) - 1
    idx = int(min(max(round(UpperBoundLevel * max_idx) + 1, nozero_idx), len(block_info)-1))
    upper_bound = UpperBoundFactor * block_info[idx, 0]
    
    # Compute eigenvalues for different subsets
    prev_variance = 0
    variance = upper_bound
    
    for _ in range(10):
        if abs(prev_variance - variance) < 1e-5:
            break
        prev_variance = variance
        
        # Get subset of blocks
        subset_idx = int(round(MinLevel * len(block_info)))
        subset = block_info[:subset_idx]
        
        if len(subset) < M:
            continue
            
        # Compute PCA
        blocks = np.array([image[int(y)-1:int(y)-1+M2, int(x)-1:int(x)-1+M1].flatten() 
                          for _, x, y in subset])
        mean_block = np.mean(blocks, axis=0, keepdims=True)
        blocks -= mean_block
        cov_matrix = np.cov(blocks.T)
        eigenvals = np.sort(np.linalg.eigvals(cov_matrix).real)
        
        if eigenvals[0] < 1e-5:
            continue
            
        diff = eigenvals[EigenValueCount-1] - eigenvals[0]
        diff_threshold = EigenValueDiffThreshold * prev_variance / np.sqrt(len(subset))
        
        if diff < diff_threshold and eigenvals[0] < upper_bound:
            variance = eigenvals[0]
            break
    
    if variance < 0:
        label = 1
        return label, np.sqrt(np.var(image))
        
    return label, np.sqrt(variance)

def model(x):
    """Python implementation of model.m"""
    low = 20
    high = 110
    C = 2 * low * high**2 / (high-low)**2
    
    y = np.zeros_like(x)
    high_mask = x > low
    low_mask = x <= low
    
    y[high_mask] = (2*high**2 - (x[high_mask]-high)**2) / (2*high**2)
    y[low_mask] = 1 - x[low_mask]/C
    
    y = np.maximum(y, 1/2)  # More conservative setting than paper
    y = 1/y
    return y

def custom_kmeans(data, n_clusters, max_iters=200):
    """Python implementation of KMeans.m"""
    m = len(data)
    
    # Initialize centroids
    sorted_data = np.sort(data)
    u = np.zeros(n_clusters)
    u[0] = np.mean(sorted_data[-(m//4):])  # Higher values
    u[1] = np.mean(sorted_data[:(m//4)])   # Lower values
    
    # Limit maximum values
    umax = np.median(sorted_data[-(m//100):])
    data_capped = np.copy(data)
    data_capped[data_capped > umax] = umax
    
    # Iterative refinement
    for _ in range(max_iters):
        pre_u = np.copy(u)
        
        # Compute distances to centroids
        distances = np.abs(data_capped.reshape(-1, 1) - u)
        labels = np.argmin(distances, axis=1)
        
        # Update centroids
        for i in range(n_clusters):
            mask = labels == i
            if np.sum(mask) > 0.01:
                u[i] = np.sum(data_capped[mask]) / np.sum(mask)
        
        if np.linalg.norm(pre_u - u) < 0.02:
            break
    
    # Final assignment
    distances = np.abs(data.reshape(-1, 1) - u)
    labels = np.argmin(distances, axis=1)
    
    # Ensure smaller region gets label 2
    if np.sum(labels == 0) < m/2:
        labels = 1 - labels
    
    # Add 1 to match MATLAB 1-indexing
    return labels + 1

def process_image(input_image_path, output_dir):
    """Python implementation of process_image.m"""
    try:
        # Read the input image
        original = cv2.imread(input_image_path)
        if original is None:
            raise Exception("Unable to load image.")

        # Convert to grayscale if color image
        if len(original.shape) == 3 and original.shape[2] > 1:
            spliced = cv2.cvtColor(original, cv2.COLOR_BGR2GRAY)
        else:
            spliced = original

        # Save original image
        original_path = os.path.join(output_dir, 'original.png')
        cv2.imwrite(original_path, original)

        # Get image dimensions and preprocess
        M, N = spliced.shape
        I = spliced.astype(np.float64)

        # Block size
        B = 64
        
        # Ensure dimensions are multiples of block size
        I = I[:(M // B) * B, :(N // B) * B]
        M, N = I.shape

        # Process blocks
        label64 = np.zeros((M // B, N // B))
        Noise_64 = np.zeros_like(label64)
        meanIb = np.zeros_like(label64)
        
        for i in range(M // B):
            for j in range(N // B):
                Ib = I[i*B:(i+1)*B, j*B:(j+1)*B]
                label64[i,j], Noise_64[i,j] = PCANoiseLevelEstimator(Ib, 5)
                meanIb[i,j] = np.mean(Ib)
        
        # Process valid blocks
        valid = np.where(label64 == 0)[0]
        if len(valid) == 0:
            raise Exception("No valid blocks found")
            
        # Apply model and clustering
        attenfactor = model(meanIb)
        Noise_64c = Noise_64 * attenfactor
        
        # Perform clustering
        labels = custom_kmeans(Noise_64c.flatten()[valid], 2)
        
        # Reshape result
        result = np.ones(label64.size)
        result[valid] = labels
        result_proposed = result.reshape(label64.shape)

        # Create result image with highlighted regions
        result_img = np.stack([spliced] * 3, axis=-1).astype(np.uint8)

        for i in range(result_proposed.shape[0]):
            for j in range(result_proposed.shape[1]):
                if result_proposed[i, j] == 2:
                    row_start = i * B
                    row_end = min((i + 1) * B, M)
                    col_start = j * B
                    col_end = min((j + 1) * B, N)

                    # Make block reddish
                    block = result_img[row_start:row_end, col_start:col_end, :]
                    block[:, :, 0] = np.minimum(255, block[:, :, 0] + 100)  # Increase red
                    block[:, :, 1] = np.maximum(0, block[:, :, 1] - 50)     # Decrease green
                    block[:, :, 2] = np.maximum(0, block[:, :, 2] - 50)     # Decrease blue
                    result_img[row_start:row_end, col_start:col_end, :] = block

                    # Add red border
                    thickness = 2
                    result_img[row_start:row_start+thickness, col_start:col_end, 0] = 255
                    result_img[row_end-thickness:row_end, col_start:col_end, 0] = 255
                    result_img[row_start:row_end, col_start:col_start+thickness, 0] = 255
                    result_img[row_start:row_end, col_end-thickness:col_end, 0] = 255

                    result_img[row_start:row_start+thickness, col_start:col_end, 1:] = 0
                    result_img[row_end-thickness:row_end, col_start:col_end, 1:] = 0
                    result_img[row_start:row_end, col_start:col_start+thickness, 1:] = 0
                    result_img[row_start:row_end, col_end-thickness:col_end, 1:] = 0

        # Save the result
        final_result_path = os.path.join(output_dir, 'final_result.png')
        cv2.imwrite(final_result_path, result_img)

        # Determine if image is spliced
        is_spliced = np.any(result_proposed == 2)

        # Create results structure
        result_info = {
            'is_spliced': bool(is_spliced),
            'timestamp': datetime.now().strftime('%Y-%m-%d_%H-%M-%S'),
            'original_image': f"{output_dir}/original.png",
            'final_result_image': f"{output_dir}/final_result.png"
        }

        # Save results to JSON file
        with open(os.path.join(output_dir, 'analysis_results.json'), 'w') as f:
            json.dump(result_info, f)

        return result_info

    except Exception as e:
        error_msg = f"Error processing image: {str(e)}"
        logging.error(error_msg)
        return {"error": error_msg}

# ===== Flask Routes =====

@app.route('/upload', methods=['POST'])
def upload():
    try:
        if 'image' not in request.files:
            return jsonify({"error": "No file uploaded"}), 400

        file = request.files['image']
        if file.filename == '':
            return jsonify({"error": "No file selected"}), 400

        if file:
            filename = secure_filename(file.filename)
            timestamp = datetime.now().strftime('%Y-%m-%d_%H-%M-%S')
            result_folder = os.path.join(RESULTS_DIR, timestamp)
            os.makedirs(result_folder, exist_ok=True)

            # Save uploaded file
            upload_path = os.path.join(UPLOAD_DIR, filename)
            file.save(upload_path)

            # Process image
            result_info = process_image(upload_path, result_folder)

            # Return JSON response
            return jsonify(result_info)
    except Exception as e:
        logging.error(f"Upload error: {str(e)}")
        return jsonify({"error": str(e)}), 500

@app.route('/results/<path:filename>')
def serve_result(filename):
    try:
        return send_from_directory(RESULTS_DIR, filename)
    except Exception as e:
        return jsonify({"error": f"File not found: {filename}"}), 404

# Add CORS headers
@app.after_request
def add_cors_headers(response):
    response.headers.add("Access-Control-Allow-Origin", "*")
    response.headers.add("Access-Control-Allow-Headers", "Content-Type,Authorization")
    response.headers.add("Access-Control-Allow-Methods", "GET,POST,OPTIONS")
    return response

if __name__ == '__main__':
    # Configure logging
    logging.basicConfig(level=logging.INFO, 
                      format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
    
    # Disable file caching
    app.config['SEND_FILE_MAX_AGE_DEFAULT'] = 0
    
    # Run the server
    app.run(host='0.0.0.0', port=8080, debug=True)