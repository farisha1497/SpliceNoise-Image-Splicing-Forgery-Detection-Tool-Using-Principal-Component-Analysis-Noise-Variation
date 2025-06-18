<?php
// reCAPTCHA v3 Configuration
define('RECAPTCHA_SITE_KEY', '6Lf4p2QrAAAAANHoToFWaxcKvCVmTw73RJOHXLAS'); 
define('RECAPTCHA_SECRET_KEY', '6Lf4p2QrAAAAAMfFTn8oRZMxy-sdCBpPWyqU6CZj'); 
define('RECAPTCHA_SCORE_THRESHOLD', 0.5); // Minimum score to consider human (0.0 to 1.0)

// Function to verify reCAPTCHA response
function verifyRecaptcha($recaptcha_response) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $result = json_decode($response);

    return [
        'success' => $result->success ?? false,
        'score' => $result->score ?? 0,
        'action' => $result->action ?? '',
        'error-codes' => $result->{'error-codes'} ?? []
    ];
}
?> 
