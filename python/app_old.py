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
from zoneinfo import ZoneInfo

app = Flask(__name__)
app.secret_key = 'your_secret_key_here'
CORS(app, resources={r"/upload": {"origins": "*"}}, supports_credentials=True)

# Configuration
UPLOAD_DIR = 'uploads'
RESULTS_DIR = 'results'

# Ensure directories exist
os.makedirs(UPLOAD_DIR, exist_ok=True)
os.makedirs(RESULTS_DIR, exist_ok=True)

import os
import cv2
import numpy as np
import json
from datetime import datetime
from sklearn.cluster import KMeans
from scipy.linalg import svd


def PCANoiseLevelEstimator(Ib, patch_size):
    """
    Estimate noise level using PCA on image patches.
    Ib: input block (2D array)
    patch_size: size of square patches
    """
    M, N = Ib.shape
    num_patches_x = M - patch_size + 1
    num_patches_y = N - patch_size + 1
    num_patches = num_patches_x * num_patches_y

    if num_patches <= 0:
        return 1, 0  # invalid block

    patches = np.zeros((patch_size * patch_size, num_patches))
    idx = 0
    for i in range(num_patches_x):
        for j in range(num_patches_y):
            patch = Ib[i:i + patch_size, j:j + patch_size].flatten()
            patches[:, idx] = patch
            idx += 1

    mean_patch = np.mean(patches, axis=1, keepdims=True)
    patches -= mean_patch

    cov_matrix = np.cov(patches)
    _, s, _ = svd(cov_matrix)
    noise_std = np.sqrt(s[-1])

    return 0, noise_std  # 0 indicates valid block


def model(meanIb):
    """
    Placeholder attenuation factor model.
    Currently returns ones (no attenuation).
    Replace with real logic or ML model if available.
    """
    return np.ones_like(meanIb)


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

        label64 = np.zeros((M // B, N // B))
        Noise_64 = np.zeros_like(label64)
        meanIb = np.zeros_like(label64)

        for i in range(M // B):
            for j in range(N // B):
                Ib = I[i * B:(i + 1) * B, j * B:(j + 1) * B]
                label64[i, j], Noise_64[i, j] = PCANoiseLevelEstimator(Ib, 5)
                meanIb[i, j] = np.mean(Ib)

        valid = np.where(label64 == 0)
        re = np.ones(label64.size)

        attenfactor = model(meanIb)
        Noise_64c = Noise_64 * attenfactor
        flat_valid = Noise_64c[valid].reshape(-1, 1)

        if flat_valid.shape[0] < 2:
            raise Exception("Not enough valid blocks for KMeans.")

        kmeans = KMeans(n_clusters=2, random_state=0, n_init='auto').fit(flat_valid)
        re3 = kmeans.labels_
        re[np.ravel_multi_index(valid, label64.shape)] = re3 + 1  # label from 1, 2

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
                    block[:, :, 0] = np.maximum(0, block[:, :, 0] - 50)     # Blue
                    block[:, :, 1] = np.maximum(0, block[:, :, 1] - 50)     # Green
                    block[:, :, 2] = np.minimum(255, block[:, :, 2] + 100)
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
            'timestamp': datetime.now(ZoneInfo("Asia/Kuala_Lumpur")).strftime('%Y-%m-%d_%H-%M-%S'), # Match process.php format
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
    app.run(host='0.0.0.0', port=8080, debug=True)