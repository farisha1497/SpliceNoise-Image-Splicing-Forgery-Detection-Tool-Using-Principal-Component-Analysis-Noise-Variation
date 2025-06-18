from flask import Flask, request, jsonify, session
from flask_cors import CORS
import os
import cv2
import numpy as np
import json
from datetime import datetime
from werkzeug.utils import secure_filename
from flask import make_response
import logging
from flask import send_from_directory
from sklearn.cluster import KMeans
from scipy.linalg import svd

app = Flask(__name__)
app.secret_key = 'your_secret_key_here'
CORS(app, resources={r"/upload": {"origins": ["https://seal-app-kntr3.ondigitalocean.app/"]}}, supports_credentials=True)

# Configuration
UPLOAD_DIR = 'uploads'
RESULTS_DIR = 'results'

# Ensure directories exist
os.makedirs(UPLOAD_DIR, exist_ok=True)
os.makedirs(RESULTS_DIR, exist_ok=True)

def PCANoiseLevelEstimator(Ib, patch_size):
    # Parameters from MATLAB implementation
    UpperBoundLevel = 0.0005
    UpperBoundFactor = 3.1
    M1 = patch_size
    M2 = patch_size
    M = M1 * M2
    EigenValueCount = 7
    EigenValueDiffThreshold = 49.0
    LevelStep = 0.05
    MinLevel = 0.06
    MaxClippedPixelCount = round(0.1 * M)

    # Convert block to float
    Ib = Ib.astype(np.float64)
    
    # Compute block info
    block_info = []
    for y in range(Ib.shape[0] - M2):
        for x in range(Ib.shape[1] - M1):
            block = Ib[y:y+M2, x:x+M1]
            val_sum = np.sum(block)
            val_sum2 = np.sum(block**2)
            clipped = np.sum((block == 0) | (block == 255))
            
            if clipped <= MaxClippedPixelCount:
                var = (val_sum2 - val_sum*val_sum/M) / M
                block_info.append([var, x, y])
    
    if not block_info:
        return 1, np.var(Ib)
        
    block_info = np.array(sorted(block_info, key=lambda x: x[0]))
    
    # Find first non-zero variance index
    nozero_idx = np.where(block_info[:, 0] > 0)[0]
    if len(nozero_idx) == 0:
        return 1, np.var(Ib)
    nozero_idx = nozero_idx[0]
    
    # Compute upper bound
    max_idx = len(block_info) - 1
    idx = int(min(max(round(UpperBoundLevel * max_idx) + 1, nozero_idx), len(block_info)-1))
    upper_bound = UpperBoundFactor * block_info[idx, 0]
    
    # Compute eigenvalues for different subsets
    variance = upper_bound
    prev_variance = 0
    
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
        blocks = np.array([Ib[int(y):int(y+M2), int(x):int(x+M1)].flatten() 
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
        return 1, np.sqrt(np.var(Ib))
        
    return 0, np.sqrt(variance)

def model(x):
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
    return y.reshape(x.shape)

def process_image(input_image_path, output_dir):
    try:
        original = cv2.imread(input_image_path)
        if original is None:
            raise Exception("Unable to load image.")

        if len(original.shape) == 3 and original.shape[2] > 1:
            spliced = cv2.cvtColor(original, cv2.COLOR_BGR2GRAY)
        else:
            spliced = original

        original_path = os.path.join(output_dir, 'original.png')
        cv2.imwrite(original_path, original)

        M, N = spliced.shape
        I = spliced.astype(np.float64)

        B = 64
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
        labels = custom_kmeans(Noise_64c[valid], 2)
        
        # Reshape result
        result = np.ones(label64.size)
        result[valid] = labels
        result_proposed = result.reshape(label64.shape)
        
        # Remove duplicate clustering attempts
        # First valid clustering
        labels = custom_kmeans(Noise_64c[valid], 2)
        
        # Remove these conflicting lines:
        # re = np.ones(label64.size)
        # re3 = kmeans.labels_  
        # result_proposed = re.reshape(label64.shape)

        # Keep only one result processing
        result = np.ones(label64.size)
        result[valid] = labels
        result_proposed = result.reshape(label64.shape)
        
        # Second conflicting attempt
        re = np.ones(label64.size)
        re3 = kmeans.labels_  # This will fail as kmeans is not defined
        re[np.ravel_multi_index(valid, label64.shape)] = re3 + 1

        result_proposed = re.reshape(label64.shape)

        result_img = np.stack([spliced] * 3, axis=-1).astype(np.uint8)

        for i in range(result_proposed.shape[0]):
            for j in range(result_proposed.shape[1]):
                if result_proposed[i, j] == 2:
                    row_start = i * B
                    row_end = min((i + 1) * B, M)
                    col_start = j * B
                    col_end = min((j + 1) * B, N)

                    block = result_img[row_start:row_end, col_start:col_end, :]
                    block[:, :, 0] = np.minimum(255, block[:, :, 0] + 100)
                    block[:, :, 1] = np.maximum(0, block[:, :, 1] - 50)
                    block[:, :, 2] = np.maximum(0, block[:, :, 2] - 50)
                    result_img[row_start:row_end, col_start:col_end, :] = block

                    thickness = 2
                    result_img[row_start:row_start + thickness, col_start:col_end, 0] = 255
                    result_img[row_end - thickness:row_end, col_start:col_end, 0] = 255
                    result_img[row_start:row_end, col_start:col_start + thickness, 0] = 255
                    result_img[row_start:row_end, col_end - thickness:col_end, 0] = 255

                    result_img[row_start:row_start + thickness, col_start:col_end, 1:] = 0
                    result_img[row_end - thickness:row_end, col_start:col_end, 1:] = 0
                    result_img[row_start:row_end, col_start:col_start + thickness, 1:] = 0
                    result_img[row_start:row_end, col_end - thickness:col_end, 1:] = 0

        final_result_path = os.path.join(output_dir, 'final_result.png')
        cv2.imwrite(final_result_path, result_img)

        is_spliced = np.any(result_proposed == 2)

        result_info = {
            'is_spliced': bool(is_spliced),
            'timestamp': datetime.now().strftime('%Y-%m-%d_%H-%M-%S'),  # Match process.php format
            'original_image': f"{output_dir}/original.png",  # Remove leading slash
            'final_result_image': f"{output_dir}/final_result.png"  # Remove leading slash
        }

        return result_info

    except Exception as e:
        return {"error": f"Error processing image: {str(e)}"}

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

            # Return JSON response with links
            return jsonify(result_info)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

def add_cors_headers(response):
    response.headers.add("Access-Control-Allow-Origin", "*")
    response.headers.add("Access-Control-Allow-Headers", "Content-Type,Authorization")
    response.headers.add("Access-Control-Allow-Methods", "GET,POST,OPTIONS")
    return response

app.after_request(add_cors_headers)

@app.route('/results/<path:filename>')
def serve_result(filename):
    try:
        return send_from_directory(RESULTS_DIR, filename)
    except Exception as e:
        return jsonify({"error": f"File not found: {filename}"}), 404

if __name__ == '__main__':
    # Ensure proper static file handling
    app.config['SEND_FILE_MAX_AGE_DEFAULT'] = 0
    app.run(host='0.0.0.0', port=8080, debug=True)


def custom_kmeans(data, n_clusters, max_iters=100):
    # Simple KMeans implementation
    indices = np.random.choice(len(data), n_clusters, replace=False)
    centroids = data[indices]
    
    for _ in range(max_iters):
        distances = np.linalg.norm(data[:, None] - centroids, axis=2)
        labels = np.argmin(distances, axis=1)
        
        new_centroids = np.array([data[labels == i].mean(axis=0) for i in range(n_clusters)])
        
        if np.all(centroids == new_centroids):
            break
            
        centroids = new_centroids
    
    return labels
