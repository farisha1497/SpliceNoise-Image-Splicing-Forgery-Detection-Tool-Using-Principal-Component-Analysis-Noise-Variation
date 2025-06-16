# SpliceNoise - Image Splicing Forgery Detection Tool Using Principal Component Analysis Noise Variation

## Overview
SpliceNoise is a tool that helps detect if parts of an image have been combined from different sources by examining the noise patterns that naturally exist in digital photos.

## How to Use SpliceNoise

### Web Interface
1. Create an account or log in to your existing account
2. Click the "Upload Image" button on the navigation menu
3. Select your image file (JPG or PNG format recommended)
4. Wait for the analysis to complete (usually takes about a minute)
5. View your results - red areas in the image indicate potential splicing

### Best Practices
- The detection works best when the source images have different ISO settings
- Use original, uncompressed images for best results
- JPG or PNG formats are recommended
- Higher resolution images provide more accurate analysis

## Technical Background
SpliceNoise is based on the research paper "Exposing Image splicing with inconsistent sensor noise levels" by Hui Zeng, Anjie Peng, and Xiaodan Lin. The tool employs a noise-based image splicing localization method specifically designed for cases where source images have distinct ISO settings.

## Troubleshooting
- If analysis fails, try using a different image format
- Ensure the image is not heavily compressed
- For technical issues, contact support with details about your image and the error encountered

## Further Information
For more information on the research behind this tool, visit:
https://link.springer.com/article/10.1007/s11042-020-09280-z
