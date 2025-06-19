from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import numpy as np
import cv2
import os
import logging
from PIL import Image
import io
import base64
import json
import time
from sklearn.cluster import KMeans
from numpy.lib.stride_tricks import as_strided
from concurrent.futures import ProcessPoolExecutor
from datetime import datetime
from zoneinfo import ZoneInfo

app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "*"}}, supports_credentials=True)

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Global variables
RESULT_FOLDER = 'results'
os.makedirs(RESULT_FOLDER, exist_ok=True)

def clamp(x, a, b):
    return max(a, min(x, b))

def pca_noise_level_estimator(image, bsize):
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
        variance = np.var(image.astype(np.float32))
    else:
        block_info = block_info[block_info[:, 0].argsort()]
        sum1, sum2, subset_size = compute_statistics(image, block_info, m1, m2, m, level_step, min_level)
        
        if len(subset_size) == 0 or subset_size[-1] == 0:
            label = 1
            variance = np.var(image)
        else:
            upper_bound = compute_upper_bound(block_info, upper_bound_level, upper_bound_factor)
            prev_variance = 0
            variance = upper_bound
            
            for _ in range(10):
                if abs(prev_variance - variance) < 1e-5:
                    break
                prev_variance = variance
                variance = get_next_estimate(sum1, sum2, subset_size, variance, upper_bound, 
                                             eigen_value_count, eigen_value_diff_threshold)
            
            if variance < 0:
                label = 1
                variance = np.var(image)
    
    return label, np.sqrt(variance) if variance >= 0 else np.sqrt(np.var(image))

def extract_blocks(img, block_size=8, step=4):
    h, w = img.shape
    shape = ((h - block_size) // step + 1, (w - block_size) // step + 1, block_size, block_size)
    strides = (img.strides[0] * step, img.strides[1] * step, img.strides[0], img.strides[1])
    return as_strided(img, shape=shape, strides=strides)


def compute_block_info(image, m1, m2, m, max_clipped_pixel_count):
    height, width = image.shape
    block_info = []

    for y in range(height - m2 + 1):
        for x in range(width - m1 + 1):
            block = image[y:y + m2, x:x + m1].astype(np.float32)
            clipped = np.logical_or(block == 0, block == 255)
            if np.count_nonzero(clipped) <= max_clipped_pixel_count:
                variance = np.var(block)
                block_info.append([variance, x, y])

    return np.array(block_info) if block_info else np.array([])

def compute_statistics(image, block_info, m1, m2, m, level_step, min_level):
    sum1_list, sum2_list, subset_size = [], [], []
    p = 1.0
    while p >= min_level:
        q = max(p - level_step, min_level)
        max_index = len(block_info) - 1
        beg_index = clamp(round(q * max_index), 0, max_index)
        end_index = clamp(round(p * max_index), 0, max_index)
        
        curr_sum1 = np.zeros(m, dtype=np.float32)
        curr_sum2 = np.zeros((m, m), dtype=np.float32)
        
        for k in range(beg_index, end_index):
            x, y = int(block_info[k, 1]), int(block_info[k, 2])
            block = image[y:y + m2, x:x + m1].flatten().astype(np.float32)
            curr_sum1 += block
            curr_sum2 += np.outer(block, block)
        
        sum1_list.append(curr_sum1)
        sum2_list.append(curr_sum2)
        subset_size.append(end_index - beg_index)
        p -= level_step

    for i in range(len(subset_size) - 2, -1, -1):
        sum1_list[i] += sum1_list[i+1]
        sum2_list[i] += sum2_list[i+1]
        subset_size[i] += subset_size[i+1]
    
    return sum1_list, sum2_list, subset_size

def compute_upper_bound(block_info, upper_bound_level, upper_bound_factor):
    max_index = len(block_info) - 1
    nonzero_index = np.where(block_info[:, 0] == 0)[0]
    start_index = nonzero_index[-1] + 1 if len(nonzero_index) else 0
    index = clamp(round(upper_bound_level * max_index), start_index, max_index)
    return upper_bound_factor * block_info[index, 0]

def apply_pca(sum1, sum2, subset_size):
    if subset_size == 0:
        return np.array([0])
    mean = (sum1 / subset_size).astype(np.float32)
    cov = (sum2 / subset_size).astype(np.float32) - np.outer(mean, mean)
    try:
        # Use faster symmetric eigenvalue computation
        eigenvalues = np.linalg.eigvalsh(cov)
        return np.sort(eigenvalues)
    except Exception:
        return np.array([0])

def get_next_estimate(sum1_list, sum2_list, subset_size, prev_estimate, upper_bound, 
                      eigen_value_count, eigen_value_diff_threshold):
    for i in range(len(subset_size)):
        eigenvalues = apply_pca(sum1_list[i], sum2_list[i], subset_size[i])
        if eigenvalues[0] < 1e-5:
            break
        if len(eigenvalues) >= eigen_value_count:
            diff = eigenvalues[eigen_value_count - 1] - eigenvalues[0]
            threshold = eigen_value_diff_threshold * prev_estimate / (subset_size[i] ** 0.5)
            if diff < threshold and eigenvalues[0] < upper_bound:
                return eigenvalues[0]
    return eigenvalues[0]

def dethighlight_hz(image):
    img = image.astype(np.float32)
    blurred = cv2.GaussianBlur(img, (5, 5), 1.0)
    diff = img - blurred
    mask = np.abs(diff) > 10.0
    img[mask] -= 0.5 * diff[mask]
    return np.clip(img, 0, 255).astype(np.uint8)

def model_function(noise_levels):
    if len(noise_levels) < 2:
        return 0
    try:
        noise_levels_np = np.array(noise_levels).reshape(-1, 1)
        kmeans = KMeans(n_clusters=2, random_state=42).fit(noise_levels_np)
        centroids = kmeans.cluster_centers_.flatten()
        separation = abs(centroids[0] - centroids[1])
        return int(separation > 0.1)
    except:
        return 0

def process_block(block):
    try:
        label, variance = pca_noise_level_estimator(block, block.shape[0])
        return variance if label == 0 else None
    except Exception:
        return None

def process_image_function(image_array):
    processed = dethighlight_hz(image_array)
    blocks = extract_blocks(processed, block_size=8, step=4)
    block_list = blocks.reshape(-1, 8, 8)

    # Run block analysis in parallel
    with ProcessPoolExecutor() as executor:
        results = list(executor.map(process_block, block_list))

    noise_levels = [r for r in results if r is not None]

    if noise_levels:
        noise_levels_np = np.array(noise_levels, dtype=np.float32)
        avg_noise = float(noise_levels_np.mean())
        std_noise = float(noise_levels_np.std())
    else:
        avg_noise = std_noise = 0.0

    is_spliced = model_function(noise_levels)

    return {
        'is_spliced': bool(is_spliced)
    }

@app.route('/')
def index():
    return "Flask Image Processing Server is running!"

@app.route('/upload', methods=['POST'])
def upload_file():
    try:
        if 'image' not in request.files:
            return jsonify({'error': 'No file provided'}), 400

        file = request.files['image']
        if file.filename == '':
            return jsonify({'error': 'No file selected'}), 400

        # Convert image
        image = Image.open(io.BytesIO(file.read())).convert("L")
        image = image.resize((1024, 1024)) 
        image_np = np.array(image, dtype=np.float32)

        # Create date-based subfolder
        current_date_str = datetime.now(ZoneInfo("Asia/Kuala_Lumpur")).strftime('%Y-%m-%d_%H-%M-%S')
        result_subfolder = os.path.join(RESULT_FOLDER, current_date_str)
        os.makedirs(result_subfolder, exist_ok=True)

        # Process image
        result = process_image_function(image_np)

        # Save result JSON
        timestamp = int(time.time())
        result_filename = f"result_{timestamp}.json"
        result_path = os.path.join(result_subfolder, result_filename)
        with open(result_path, 'w') as f:
            json.dump(result, f)

        # Save images
        original_img_path = os.path.join(result_subfolder, "original.png")
        final_result_img_path = os.path.join(result_subfolder, "final_result.png")
        Image.fromarray(image_np.astype(np.uint8)).save(original_img_path)
        # For demo, you could reuse same image as final_result.png
        Image.fromarray(image_np.astype(np.uint8)).save(final_result_img_path)

        return jsonify({
            'is_spliced': bool(result.get('is_spliced', False)),
            'timestamp': datetime.now(ZoneInfo("Asia/Kuala_Lumpur")).strftime('%Y-%m-%d_%H-%M-%S'),
            'original_image': f"{RESULT_FOLDER}/{current_date_str}/original.png",
            'final_result_image': f"{RESULT_FOLDER}/{current_date_str}/final_result.png"
        })
    except Exception as e:
        logger.error(f"Upload error: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/results/<path:filename>')
def get_result(filename):
    try:
        return send_from_directory(RESULT_FOLDER, filename)
    except Exception:
        return jsonify({'error': 'File not found'}), 404

@app.route('/health')
def health_check():
    return jsonify({'status': 'healthy', 'timestamp': time.time()})

@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Endpoint not found'}), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({'error': 'Internal server error'}), 500

def add_cors_headers(response):
    response.headers.add("Access-Control-Allow-Origin", "*")
    response.headers.add("Access-Control-Allow-Headers", "Content-Type,Authorization")
    response.headers.add("Access-Control-Allow-Methods", "GET,POST,OPTIONS")
    return response

app.after_request(add_cors_headers)

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=8080)
