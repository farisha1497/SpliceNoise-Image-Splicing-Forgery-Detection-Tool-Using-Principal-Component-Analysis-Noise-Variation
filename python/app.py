from flask import Flask, request, render_template, redirect, url_for, jsonify, session
import os
import cv2
import numpy as np
import json
from datetime import datetime
from werkzeug.utils import secure_filename

app = Flask(__name__)
app.secret_key = 'your_secret_key_here'

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

        # Convert to grayscale if it's a color image
        if original.shape[2] > 1:
            spliced = cv2.cvtColor(original, cv2.COLOR_BGR2GRAY)
        else:
            spliced = original

        # Save original image directly from input
        original_path = os.path.join(output_dir, 'original.png')
        cv2.imwrite(original_path, original)

        # Get image dimensions and preprocess for analysis
        M, N = spliced.shape

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
        re[valid] = re3
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
            'original_image': 'original.png',
            'final_result_image': 'final_result.png'
        }

        # Save results to JSON file
        results_json = os.path.join(output_dir, 'analysis_results.json')
        with open(results_json, 'w') as f:
            json.dump(result_info, f)

        return result_info

    except Exception as e:
        # Write error to a file
        error_file = os.path.join(output_dir, 'error.txt')
        with open(error_file, 'w') as f:
            f.write(f"Error: {str(e)}\n")
        raise


@app.route('/upload', methods=['GET', 'POST'])
def upload():
    if request.method == 'POST':
        try:
            # Check if the user is logged in
            if 'loggedin' not in session or not session['loggedin']:
                return redirect(url_for('login'))

            # Handle file upload
            if 'image' not in request.files:
                return "No file uploaded", 400

            file = request.files['image']
            if file.filename == '':
                return "No file selected", 400

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

                # Success - render results
                return jsonify(result_info)
        except Exception as e:
            return f"Error processing image: {str(e)}", 500

    return render_template('upload.html')


@app.route('/login')
def login():
    # Dummy login page for demonstration
    return render_template('login.html')


if __name__ == '__main__':
    app.run(debug=True)
