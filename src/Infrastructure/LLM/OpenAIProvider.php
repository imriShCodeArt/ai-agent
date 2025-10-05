<?php
namespace AIAgent\Infrastructure\LLM;

use AIAgent\Support\Logger;

final class OpenAIProvider implements LLMProviderInterface
{
    private Logger $logger;
    private string $apiKey;

    public function __construct(Logger $logger, string $apiKey)
    {
        $this->logger = $logger;
        $this->apiKey = $apiKey;
    }

    /** @inheritDoc */
    public function complete(string $prompt, array $options = []): string
    {
        // If API key is not configured, return a helpful stub
        if (trim($this->apiKey) === '') {
            $this->logger->warning('OpenAI API key not configured');
            return 'The AI provider is not configured. Please set an OpenAI API key in AI Agent → Settings → OpenAI.';
        }

        $model = (string) ($options['model'] ?? (function_exists('get_option') ? get_option('ai_agent_openai_model', 'gpt-5-mini') : 'gpt-5-mini'));
        $temperature = (float) ($options['temperature'] ?? 0.3);

        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];
        // Some GPT‑5 models only support default temperature; omit when model starts with gpt-5
        if (stripos($model, 'gpt-5') === false) {
            $body['temperature'] = $temperature;
        }

        // In non-WordPress/unit-test contexts, fall back to a stubbed response
        if (!function_exists('wp_remote_post')) {
            $this->logger->info('OpenAI complete in non-WP context, returning stub');
            return (string) substr('summary: ' . $prompt, 0, 200);
        }

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
            'timeout' => 20,
        ]);

        if (function_exists('is_wp_error') && is_wp_error($response)) {
            $this->logger->error('OpenAI request failed', ['error' => $response->get_error_message()]);
            return 'I could not contact the AI service. Please verify your OpenAI API key and internet connectivity.';
        }

        $code = function_exists('wp_remote_retrieve_response_code') ? (int) wp_remote_retrieve_response_code($response) : 200;
        $bodyStr = function_exists('wp_remote_retrieve_body') ? (string) wp_remote_retrieve_body($response) : '';
        $data = json_decode($bodyStr, true);
        if ($code !== 200 || !is_array($data)) {
            $errorMsg = '';
            if (is_array($data) && isset($data['error']['message'])) {
                $errorMsg = (string) $data['error']['message'];
            }
            $this->logger->error('OpenAI bad response', ['code' => $code, 'body' => $data]);
            return $errorMsg !== ''
                ? 'OpenAI error: ' . $errorMsg
                : 'The AI service returned an unexpected response. Please try again later or adjust your model settings.';
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        return is_string($content) ? $content : '';
    }
}


