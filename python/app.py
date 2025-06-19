from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import numpy as np
import cv2
import os
import logging
from PIL import Image
import io
import base64
from scipy.linalg import eig
import json
import time

app = Flask(__name__)
CORS(app)

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Global variables
UPLOAD_FOLDER = 'uploads'
RESULT_FOLDER = 'results'
os.makedirs(UPLOAD_FOLDER, exist_ok=True)
os.makedirs(RESULT_FOLDER, exist_ok=True)

def clamp(x, a, b):
    """Clamp function equivalent to MATLAB Clamp"""
    return max(a, min(x, b))

def pca_noise_level_estimator(image, bsize):
    """Python conversion of PCANoiseLevelEstimator.m"""
    # Parameters
    upper_bound_level = 0.0005
    upper_bound_factor = 3.1
    m1 = bsize
    m2 = bsize
    m = m1 * m2
    eigen_value_count = 7
    eigen_value_diff_threshold = 49.0
    level_step = 0.05
    min_level = 0.06
    max_clipped_pixel_count = round(0.1 * m)
    
    label = 0
    block_info = compute_block_info(image, m1, m2, m, max_clipped_pixel_count)
    
    if len(block_info) == 0:
        label = 1
        variance = np.var(image)
    else:
        # Sort by first column (variance)
        block_info = block_info[block_info[:, 0].argsort()]
        sum1, sum2, subset_size = compute_statistics(image, block_info, m1, m2, m, level_step, min_level)
        
        if len(subset_size) == 0 or subset_size[-1] == 0:
            label = 1
            variance = np.var(image)
        else:
            upper_bound = compute_upper_bound(block_info, upper_bound_level, upper_bound_factor)
            prev_variance = 0
            variance = upper_bound
            
            for iter in range(10):
                if abs(prev_variance - variance) < 1e-5:
                    break
                prev_variance = variance
                variance = get_next_estimate(sum1, sum2, subset_size, variance, upper_bound, 
                                           eigen_value_count, eigen_value_diff_threshold)
            
            if variance < 0:
                label = 1
                variance = np.var(image)
    
    variance = np.sqrt(variance) if variance >= 0 else np.sqrt(np.var(image))
    return label, variance

def compute_block_info(image, m1, m2, m, max_clipped_pixel_count):
    """Compute block information"""
    height, width = image.shape
    block_info = []
    
    for y in range(height - m2 + 1):
        for x in range(width - m1 + 1):
            sum1 = 0.0
            sum2 = 0.0
            clipped_pixel_count = 0
            
            for by in range(y, y + m2):
                for bx in range(x, x + m1):
                    val = image[by, bx]
                    sum1 += val
                    sum2 += val * val
                    if val == 0 or val == 255:
                        clipped_pixel_count += 1
            
            if clipped_pixel_count <= max_clipped_pixel_count:
                variance = (sum2 - sum1 * sum1 / m) / m
                block_info.append([variance, x, y])
    
    return np.array(block_info) if block_info else np.array([])

def compute_statistics(image, block_info, m1, m2, m, level_step, min_level):
    """Compute statistics for PCA"""
    sum1_list = []
    sum2_list = []
    subset_size = []
    
    p = 1.0
    while p >= min_level:
        q = 0
        if p - level_step > min_level:
            q = p - level_step
        
        max_index = len(block_info) - 1
        beg_index = clamp(round(q * max_index), 0, len(block_info) - 1)
        end_index = clamp(round(p * max_index), 0, len(block_info) - 1)
        
        curr_sum1 = np.zeros(m)
        curr_sum2 = np.zeros((m, m))
        
        for k in range(beg_index, end_index):
            curr_x = int(block_info[k, 1])
            curr_y = int(block_info[k, 2])
            
            block = image[curr_y:curr_y + m2, curr_x:curr_x + m1].flatten()
            curr_sum1 += block
            curr_sum2 += np.outer(block, block)
        
        sum1_list.append(curr_sum1)
        sum2_list.append(curr_sum2)
        subset_size.append(end_index - beg_index)
        
        p -= level_step
    
    # Accumulate statistics
    for i in range(len(subset_size) - 1, 0, -1):
        sum1_list[i-1] += sum1_list[i]
        sum2_list[i-1] += sum2_list[i]
        subset_size[i-1] += subset_size[i]
    
    return sum1_list, sum2_list, subset_size

def compute_upper_bound(block_info, upper_bound_level, upper_bound_factor):
    """Compute upper bound"""
    max_index = len(block_info) - 1
    
    # Find first non-zero variance index
    nozero_indices = np.where(block_info[:, 0] == 0)[0]
    nozero_index = nozero_indices[-1] + 1 if len(nozero_indices) > 0 else 0
    
    index = clamp(round(upper_bound_level * max_index), nozero_index, len(block_info) - 1)
    upper_bound = upper_bound_factor * block_info[index, 0]
    return upper_bound

def apply_pca(sum1, sum2, subset_size):
    """Apply PCA to compute eigenvalues"""
    if subset_size == 0:
        return np.array([0])
    
    mean = sum1 / subset_size
    cov_matrix = sum2 / subset_size - np.outer(mean, mean)
    
    try:
        eigenvalues, _ = eig(cov_matrix)
        eigenvalues = np.real(eigenvalues)
        eigenvalues = np.sort(eigenvalues)
        return eigenvalues
    except:
        return np.array([0])

def get_next_estimate(sum1_list, sum2_list, subset_size, prev_estimate, upper_bound, 
                     eigen_value_count, eigen_value_diff_threshold):
    """Get next variance estimate"""
    variance = 0
    
    for i in range(len(subset_size)):
        eigenvalues = apply_pca(sum1_list[i], sum2_list[i], subset_size[i])
        variance = eigenvalues[0] if len(eigenvalues) > 0 else 0
        
        if variance < 1e-5:
            break
        
        if len(eigenvalues) >= eigen_value_count:
            diff = eigenvalues[eigen_value_count - 1] - eigenvalues[0]
            diff_threshold = eigen_value_diff_threshold * prev_estimate / (subset_size[i] ** 0.5)
            
            if diff < diff_threshold and variance < upper_bound:
                break
    
    return variance

def dethighlight_hz(image):
    """Python conversion of dethighlightHZ.m"""
    # Convert to float for processing
    img = image.astype(np.float64)
    
    # Apply Gaussian blur
    blurred = cv2.GaussianBlur(img, (5, 5), 1.0)
    
    # Compute difference
    diff = img - blurred
    
    # Threshold and enhance
    threshold = 10.0
    mask = np.abs(diff) > threshold
    
    # Apply enhancement
    enhanced = img.copy()
    enhanced[mask] = img[mask] - 0.5 * diff[mask]
    
    # Clamp values
    enhanced = np.clip(enhanced, 0, 255)
    
    return enhanced.astype(np.uint8)

def custom_kmeans(data, k, max_iters=100):
    """Python conversion of KMeans.m"""
    if len(data) == 0:
        return np.array([]), np.array([])
    
    data = np.array(data)
    if data.ndim == 1:
        data = data.reshape(-1, 1)
    
    n_samples, n_features = data.shape
    
    # Initialize centroids randomly
    np.random.seed(42)  # For reproducibility
    centroids = data[np.random.choice(n_samples, k, replace=False)]
    
    for _ in range(max_iters):
        # Assign points to closest centroid
        distances = np.sqrt(((data - centroids[:, np.newaxis])**2).sum(axis=2))
        labels = np.argmin(distances, axis=0)
        
        # Update centroids
        new_centroids = np.array([data[labels == i].mean(axis=0) for i in range(k)])
        
        # Check for convergence
        if np.allclose(centroids, new_centroids):
            break
        
        centroids = new_centroids
    
    return labels, centroids

def model_function(noise_levels):
    """Python conversion of model.m"""
    if len(noise_levels) == 0:
        return 0
    
    noise_levels = np.array(noise_levels)
    
    # Apply K-means clustering
    labels, centroids = custom_kmeans(noise_levels, 2)
    
    if len(centroids) < 2:
        return 0
    
    # Calculate cluster statistics
    cluster_0_mean = centroids[0]
    cluster_1_mean = centroids[1]
    
    # Determine if image is spliced based on cluster separation
    separation = abs(cluster_0_mean - cluster_1_mean)
    threshold = 0.1  # Threshold for splicing detection
    
    return 1 if separation > threshold else 0

def process_image_function(image_path):
    """Python conversion of process_image.m"""
    try:
        # Read image
        image = cv2.imread(image_path, cv2.IMREAD_GRAYSCALE)
        if image is None:
            raise ValueError(f"Could not read image: {image_path}")
        
        # Apply dethighlighting
        processed_image = dethighlight_hz(image)
        
        # Parameters
        block_size = 8
        step_size = 4
        
        # Extract noise levels from blocks
        noise_levels = []
        height, width = processed_image.shape
        
        for y in range(0, height - block_size + 1, step_size):
            for x in range(0, width - block_size + 1, step_size):
                block = processed_image[y:y + block_size, x:x + block_size]
                label, variance = pca_noise_level_estimator(block, block_size)
                
                if label == 0:  # Valid block
                    noise_levels.append(variance)
        
        # Apply model to determine if spliced
        is_spliced = model_function(noise_levels)
        
        # Calculate statistics
        noise_levels = np.array(noise_levels)
        avg_noise = np.mean(noise_levels) if len(noise_levels) > 0 else 0
        std_noise = np.std(noise_levels) if len(noise_levels) > 0 else 0
        
        return {
            'is_spliced': bool(is_spliced),
            'avg_noise_level': float(avg_noise),
            'std_noise_level': float(std_noise),
            'total_blocks': len(noise_levels),
            'noise_levels': noise_levels.tolist()
        }
    
    except Exception as e:
        logger.error(f"Error processing image: {str(e)}")
        raise

@app.route('/')
def index():
    """Serve the main page"""
    return "Flask Image Processing Server is running!"

@app.route('/upload', methods=['POST'])
def upload_file():
    print("Request files:", request.files)
    print("Request form:", request.form)
    """Handle file upload and processing"""
    try:
        if 'image' not in request.files:
            return jsonify({'error': 'No file provided'}), 400
        
        file = request.files['image']
        if file.filename == '':
            return jsonify({'error': 'No file selected'}), 400
        
        # Save uploaded file
        filename = f"upload_{int(time.time())}_{file.filename}"
        filepath = os.path.join(UPLOAD_FOLDER, filename)
        file.save(filepath)
        
        # Process the image
        result = process_image_function(filepath)
        
        # Save result
        result_filename = f"result_{int(time.time())}.json"
        result_filepath = os.path.join(RESULT_FOLDER, result_filename)
        with open(result_filepath, 'w') as f:
            json.dump(result, f)
        
        # Clean up uploaded file
        os.remove(filepath)
        
        return jsonify({
            'success': True,
            'result': result,
            'message': 'Image processed successfully'
        })
    
    except Exception as e:
        logger.error(f"Upload error: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/process', methods=['POST'])
def process_image_endpoint():
    """Process image from base64 data"""
    try:
        data = request.get_json()
        if not data or 'image' not in data:
            return jsonify({'error': 'No image data provided'}), 400
        
        # Decode base64 image
        image_data = base64.b64decode(data['image'])
        image = Image.open(io.BytesIO(image_data))
        
        # Convert to grayscale and save temporarily
        if image.mode != 'L':
            image = image.convert('L')
        
        temp_filename = f"temp_{int(time.time())}.png"
        temp_filepath = os.path.join(UPLOAD_FOLDER, temp_filename)
        image.save(temp_filepath)
        
        # Process the image
        result = process_image_function(temp_filepath)
        
        # Clean up
        os.remove(temp_filepath)
        
        return jsonify({
            'success': True,
            'result': result
        })
    
    except Exception as e:
        logger.error(f"Process error: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/health')
def health_check():
    """Health check endpoint"""
    return jsonify({'status': 'healthy', 'timestamp': time.time()})

@app.route('/results/<filename>')
def get_result(filename):
    """Serve result files"""
    try:
        return send_from_directory(RESULT_FOLDER, filename)
    except Exception as e:
        return jsonify({'error': 'File not found'}), 404

@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Endpoint not found'}), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=8080)