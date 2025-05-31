function dethighlightHZ(I, B, result, mode)
    % If mode is not specified, use default behavior (triple image)
    if nargin < 4
        mode = 'triple';
    end
    
    % Get image dimensions
    [M, N] = size(I);
    
    % Create the detection visualization
    if strcmp(mode, 'single')
        % Single image mode
        imagesc(I);
        colormap(gray);
        hold on;
        
        % Highlight detected regions in red
        for i = 1:size(result,1)
            for j = 1:size(result,2)
                if result(i,j) == 2
                    % Calculate block coordinates
                    x = (j-1)*B + 1;
                    y = (i-1)*B + 1;
                    
                    % Draw red rectangle around detected block
                    rectangle('Position', [x, y, B-1, B-1], ...
                            'EdgeColor', 'red', ...
                            'LineWidth', 2);
                    
                    % Add semi-transparent red fill
                    patch([x x+B-1 x+B-1 x], [y y y+B-1 y+B-1], 'red', ...
                          'FaceAlpha', 0.3, 'EdgeColor', 'none');
                end
            end
        end
        
        % Adjust display properties
        axis equal;
        axis tight;
        set(gca, 'YDir', 'reverse');
    else
        % Original triple image display
        % Create three copies of the image
        I3 = [I,I,I];
        imagesc(I3);
        colormap(gray);
        hold on;
        
        % Process each copy
        for k = 0:2
            for i = 1:size(result,1)
                for j = 1:size(result,2)
                    if result(i,j) == 2
                        x = (j-1)*B + 1 + k*N;
                        y = (i-1)*B + 1;
                        rectangle('Position', [x,y,B-1,B-1], ...
                                'EdgeColor', 'red', ...
                                'LineWidth', 2);
                    end
                end
            end
        end
    end
    hold off;
end