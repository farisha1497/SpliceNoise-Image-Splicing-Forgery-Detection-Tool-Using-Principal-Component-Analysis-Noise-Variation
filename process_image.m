function process_image(input_image_path, output_dir)
    % Note: We remove the addpath command as paths are handled during compilation
    try
        % Read the input image
        original = imread(input_image_path);
        
        % Convert to grayscale if color image
        if size(original, 3) > 1
            spliced = rgb2gray(original);
        else
            spliced = original;
        end
        
        % Save original image directly from input
        original_path = fullfile(output_dir, 'original.png');
        imwrite(original, original_path);
        
        % Get image dimensions and preprocess for analysis
        [M, N] = size(spliced);
        
        % Convert to double
        I = double(spliced);
        
        % Block size
        B = 64;
        
        % Ensure dimensions are multiples of block size
        I = I(1:floor(M/B)*B,1:floor(N/B)*B);
        [M, N] = size(I);
        
        % Process blocks
        for i = 1 : M/B
            for j = 1 : N/B
                Ib = I((i-1)*B+1:i*B,(j-1)*B+1:j*B);
                [label64(i,j), Noise_64(i,j)] = PCANoiseLevelEstimator(Ib,5);
                meanIb(i,j) = mean2(Ib);
            end
        end
        
        % Process valid blocks
        valid = find(label64==0);
        re = ones(numel(label64),1);
        
        % Second method (proposed) - this is our final result
        attenfactor = model(meanIb);
        Noise_64c = Noise_64.*attenfactor;
        [u3, re3] = KMeans(Noise_64c(valid),2);
        re(valid) = re3(:,2);
        result_proposed = (reshape(re,size(Noise_64c)));
        
        % Create figure for final result with red highlights
        h2 = figure('Visible', 'off');
        
        % Set figure size to match image dimensions
        set(h2, 'Position', [100, 100, N, M]);
        
        % Create the detection result
        result_img = uint8(zeros(M, N, 3));
        
        % Convert grayscale to RGB
        for c = 1:3
            result_img(:,:,c) = uint8(I);
        end
        
        % Highlight detected regions in red
        for i = 1:size(result_proposed, 1)
            for j = 1:size(result_proposed, 2)
                if result_proposed(i,j) == 2
                    % Block coordinates
                    row_start = (i-1)*B + 1;
                    row_end = min(i*B, M);
                    col_start = (j-1)*B + 1;
                    col_end = min(j*B, N);
                    
                    % Make block reddish
                    block = result_img(row_start:row_end, col_start:col_end, :);
                    block(:,:,1) = min(255, double(block(:,:,1)) + 100); % Increase red
                    block(:,:,2) = max(0, double(block(:,:,2)) - 50);    % Decrease green
                    block(:,:,3) = max(0, double(block(:,:,3)) - 50);    % Decrease blue
                    result_img(row_start:row_end, col_start:col_end, :) = block;
                    
                    % Add red border
                    thickness = 2;
                    result_img(row_start:row_start+thickness-1, col_start:col_end, 1) = 255;
                    result_img(row_end-thickness+1:row_end, col_start:col_end, 1) = 255;
                    result_img(row_start:row_end, col_start:col_start+thickness-1, 1) = 255;
                    result_img(row_start:row_end, col_end-thickness+1:col_end, 1) = 255;
                    
                    result_img(row_start:row_start+thickness-1, col_start:col_end, 2:3) = 0;
                    result_img(row_end-thickness+1:row_end, col_start:col_end, 2:3) = 0;
                    result_img(row_start:row_end, col_start:col_start+thickness-1, 2:3) = 0;
                    result_img(row_start:row_end, col_end-thickness+1:col_end, 2:3) = 0;
                end
            end
        end
        
        % Save the result
        final_result_path = fullfile(output_dir, 'final_result.png');
        imwrite(result_img, final_result_path);
        close(h2);
        
        % Determine if image is spliced
        % If there are any blocks detected as spliced (value 2), consider it spliced
        is_spliced = any(result_proposed(:) == 2);
        
        % Create results structure
        result_info = struct();
        result_info.is_spliced = is_spliced;
        result_info.timestamp = datestr(now);
        result_info.original_image = 'original.png';
        result_info.final_result_image = 'final_result.png';
        
        % Save results to JSON file manually since jsonencode is more widely available
        results_json = fullfile(output_dir, 'analysis_results.json');
        fid = fopen(results_json, 'w');
        if fid == -1
            error('Failed to open JSON file for writing');
        end
        json_str = jsonencode(result_info);
        fprintf(fid, '%s', json_str);
        fclose(fid);
        
    catch ME
        % Write error to a file
        error_file = fullfile(output_dir, 'error.txt');
        fid = fopen(error_file, 'w');
        if fid ~= -1
            fprintf(fid, 'Error: %s\n', ME.message);
            fprintf(fid, 'Stack trace:\n');
            for k = 1:length(ME.stack)
                fprintf(fid, '  File: %s, Line: %d, Function: %s\n', ...
                    ME.stack(k).file, ME.stack(k).line, ME.stack(k).name);
            end
            fclose(fid);
        end
        % Rethrow the error
        rethrow(ME);
    end
end 