function process_image(input_image_path, output_dir)
    % Note: We remove the addpath command as paths are handled during compilation
    try
        % Read the input image
        spliced = imread(input_image_path);
        
        % Save original image directly from input (no preprocessing)
        original_path = fullfile(output_dir, 'original.png');
        copyfile(input_image_path, original_path);
        
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
        
        % First method (original) - we'll calculate but not save the figure
        [u, re2] = KMeans(Noise_64(valid),2);
        re(valid) = re2(:,2);
        result = (reshape(re,size(Noise_64)));
        
        % Second method (proposed) - this is our final result
        attenfactor = model(meanIb);
        Noise_64c = Noise_64.*attenfactor;
        [u3, re3] = KMeans(Noise_64c(valid),2);
        re(valid) = re3(:,2);
        result_proposed = (reshape(re,size(Noise_64c)));
        
        % Create figure only for the final method - with single image
        h2 = figure('Visible', 'off');
        % Get the current figure size
        set(h2, 'Position', [100, 100, N, M]); % Set figure size to match image
        
        % Create a single detection result
        imagesc(I);
        colormap(gray);
        hold on;
        
        % Overlay detection result in red
        detected = result_proposed' == 2;
        red_mask = cat(3, ones(size(I)), zeros(size(I)), zeros(size(I)));
        h = imagesc(red_mask);
        set(h, 'AlphaData', 0.3 * detected); % Semi-transparent red for detected regions
        
        % Remove axes for cleaner look
        axis off;
        axis equal;
        
        % Save the result
        final_result_path = fullfile(output_dir, 'final_result.png');
        saveas(h2, final_result_path);
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