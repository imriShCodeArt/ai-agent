<?php
// Simple OpenAI connectivity test for AI Agent
// URL: /wp-content/plugins/ai-agent/scripts/openai-connectivity-test.php

// Locate WordPress bootstrap
$wpLoadPaths = [
    __DIR__ . '/../../../../wp-load.php', // typical: wp-content/plugins/ai-agent/scripts → site root
    __DIR__ . '/../../../../../wp-load.php',
];

$wpLoaded = false;
foreach ($wpLoadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wpLoaded = true;
        break;
    }
}

if (!$wpLoaded) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Could not locate wp-load.php. Please move this script under a WordPress installation.";
    exit;
}

// Require admin capability
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    auth_redirect();
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

$apiKey = (string) get_option('ai_agent_openai_api_key', '');
$model  = (string) get_option('ai_agent_openai_model', 'gpt-5-mini');

if ($apiKey === '') {
    echo "OpenAI API key not set. Go to AI Agent → Settings → OpenAI and save a key.\n";
    exit;
}

$headers = [
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type'  => 'application/json',
];

$body = [
    'model' => $model,
    'messages' => [ [ 'role' => 'user', 'content' => 'ping' ] ],
];

// Omit temperature for GPT-5 models (some variants require default)
if (stripos($model, 'gpt-5') === false) {
    $body['temperature'] = 0.3;
}

$response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
    'headers' => $headers,
    'body'    => wp_json_encode($body),
    'timeout' => 20,
]);

if (is_wp_error($response)) {
    echo "WP_Error: " . $response->get_error_message() . "\n";
    $data = $response->get_error_data();
    if ($data) {
        echo "Error data: " . print_r($data, true) . "\n";
    }
    exit;
}

$code = (int) wp_remote_retrieve_response_code($response);
$bodyStr = (string) wp_remote_retrieve_body($response);

echo "HTTP Code: {$code}\n";
echo "Response:\n";
echo $bodyStr . "\n";

// Surface API error message if present
$json = json_decode($bodyStr, true);
if (is_array($json) && isset($json['error']['message'])) {
    echo "\nOpenAI error: " . $json['error']['message'] . "\n";
}


