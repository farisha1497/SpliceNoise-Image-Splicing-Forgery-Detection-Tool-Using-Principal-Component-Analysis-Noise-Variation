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

app = Flask(__name__)
app.secret_key = 'your_secret_key_here'
CORS(app, resources={r"/upload": {"origins": "*"}}, supports_credentials=True)

# Configuration
UPLOAD_DIR = 'uploads'
RESULTS_DIR = 'results'

# Ensure directories exist
os.makedirs(UPLOAD_DIR, exist_ok=True)
os.makedirs(RESULTS_DIR, exist_ok=True)

def process_image(input_image_path, output_dir):
    try:
        # Read the input image
        original = cv2.imread(input_image_path)

        # Validate the loaded image
        if original is None:
            raise Exception("Error: Unable to load image. Ensure file format is valid and path is correct.")

        # Log original image shape
        logging.debug(f"Original image shape: {original.shape}")

        # Check the dimensions of the image
        if len(original.shape) == 3 and original.shape[2] > 1:
            spliced = cv2.cvtColor(original, cv2.COLOR_BGR2GRAY)
        elif len(original.shape) == 2:
            spliced = original  # Already grayscale
        else:
            raise Exception("Error: Unexpected image format. Verify the uploaded image.")

        # Log spliced image shape
        logging.debug(f"Spliced image shape: {spliced.shape}")

        # Save original image directly from input
        original_path = os.path.join(output_dir, 'original.png')
        cv2.imwrite(original_path, original)

        # Get image dimensions and preprocess for analysis
        M, N = spliced.shape

        # Ensure the image is 2-dimensional
        if len(spliced.shape) != 2:
            raise Exception("Error: Processed image is not 2-dimensional. Verify preprocessing steps.")

        # Convert to double
        I = spliced.astype(float)

        # Block size
        B = 64

        # Ensure dimensions are multiples of block size
        I = I[:(M // B) * B, :(N // B) * B]
        M, N = I.shape

        # Process blocks
        label64 = np.zeros((M // B, N // B))
        Noise_64 = np.zeros((M // B, N // B))
        meanIb = np.zeros((M // B, N // B))

        for i in range(M // B):
            for j in range(N // B):
                Ib = I[i * B:(i + 1) * B, j * B:(j + 1) * B]
                label64[i, j] = 0  # Replace with actual noise level estimator logic
                Noise_64[i, j] = np.random.random()  # Replace with actual logic
                meanIb[i, j] = np.mean(Ib)

        # Process valid blocks
        valid = np.where(label64 == 0)
        re = np.ones(label64.size)

        # Second method (proposed) - this is our final result
        attenfactor = np.ones_like(meanIb)  # Replace with actual model logic
        Noise_64c = Noise_64 * attenfactor
        re3 = np.random.random(valid[0].shape)  # Replace with actual clustering logic
        flat_index = valid[0] * (N // B) + valid[1]
        re[flat_index] = re3
        result_proposed = re.reshape(Noise_64c.shape)

        # Create figure for final result with red highlights
        result_img = np.zeros((M, N, 3), dtype=np.uint8)

        # Convert grayscale to RGB
        for c in range(3):
            result_img[:, :, c] = spliced

        # Highlight detected regions in red
        for i in range(result_proposed.shape[0]):
            for j in range(result_proposed.shape[1]):
                if result_proposed[i, j] == 2:
                    # Block coordinates
                    row_start = i * B
                    row_end = min((i + 1) * B, M)
                    col_start = j * B
                    col_end = min((j + 1) * B, N)

                    # Make block reddish
                    block = result_img[row_start:row_end, col_start:col_end, :]
                    block[:, :, 0] = np.minimum(255, block[:, :, 0] + 100)  # Increase red
                    block[:, :, 1] = np.maximum(0, block[:, :, 1] - 50)    # Decrease green
                    block[:, :, 2] = np.maximum(0, block[:, :, 2] - 50)    # Decrease blue
                    result_img[row_start:row_end, col_start:col_end, :] = block

                    # Add red border
                    thickness = 2
                    result_img[row_start:row_start + thickness, col_start:col_end, 0] = 255
                    result_img[row_end - thickness:row_end, col_start:col_end, 0] = 255
                    result_img[row_start:row_end, col_start:col_start + thickness, 0] = 255
                    result_img[row_start:row_end, col_end - thickness:col_end, 0] = 255

                    result_img[row_start:row_start + thickness, col_start:col_end, 1:] = 0
                    result_img[row_end - thickness:row_end, col_start:col_end, 1:] = 0
                    result_img[row_start:row_end, col_start:col_start + thickness, 1:] = 0
                    result_img[row_start:row_end, col_end - thickness:col_end, 1:] = 0

        # Save the result
        final_result_path = os.path.join(output_dir, 'final_result.png')
        cv2.imwrite(final_result_path, result_img)

        # Determine if image is spliced
        is_spliced = np.any(result_proposed == 2)

        # Create results structure
        result_info = {
            'is_spliced': is_spliced,
            'timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'original_image': f"/{output_dir}/original.png",
            'final_result_image': f"/{output_dir}/final_result.png"
        }

        return result_info

    except Exception as e:
        raise Exception(f"Error processing image: {str(e)}")

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

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080, debug=True)
