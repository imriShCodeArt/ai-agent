<?php

namespace AIAgent\Infrastructure\Security;

use AIAgent\Support\Logger;

final class HmacSigner
{
    private Logger $logger;
    private string $secretKey;

    public function __construct(Logger $logger, string $secretKey = '')
    {
        $this->logger = $logger;
        $this->secretKey = $secretKey ?: $this->getSecretKey();
    }

    public function sign(string $data, string $algorithm = 'sha256'): string
    {
        if (empty($this->secretKey)) {
            throw new \InvalidArgumentException('Secret key is required for HMAC signing');
        }

        $signature = hash_hmac($algorithm, $data, $this->secretKey);
        
        $this->logger->debug('HMAC signature generated', [
            'algorithm' => $algorithm,
            'data_length' => strlen($data),
        ]);

        return $signature;
    }

    public function verify(string $data, string $signature, string $algorithm = 'sha256'): bool
    {
        if (empty($this->secretKey)) {
            $this->logger->warning('Cannot verify HMAC signature: no secret key');
            return false;
        }

        $expectedSignature = hash_hmac($algorithm, $data, $this->secretKey);
        $isValid = hash_equals($expectedSignature, $signature);
        
        $this->logger->debug('HMAC signature verification', [
            'algorithm' => $algorithm,
            'is_valid' => $isValid,
        ]);

        return $isValid;
    }

    public function signRequest(array $data, string $algorithm = 'sha256'): array
    {
        $timestamp = time();
        $nonce = $this->generateNonce();
        
        $payload = [
            'data' => $data,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
        ];
        
        $jsonPayload = wp_json_encode($payload);
        $signature = $this->sign($jsonPayload, $algorithm);
        
        return [
            'payload' => $payload,
            'signature' => $signature,
            'algorithm' => $algorithm,
        ];
    }

    public function verifyRequest(array $signedRequest, string $algorithm = 'sha256'): array
    {
        if (!isset($signedRequest['payload'], $signedRequest['signature'])) {
            return [
                'valid' => false,
                'error' => 'Invalid request format',
            ];
        }

        $payload = $signedRequest['payload'];
        $signature = $signedRequest['signature'];
        
        // Check timestamp (prevent replay attacks)
        $timestamp = $payload['timestamp'] ?? 0;
        $currentTime = time();
        $maxAge = 300; // 5 minutes
        
        if (($currentTime - $timestamp) > $maxAge) {
            $this->logger->warning('HMAC request expired', [
                'timestamp' => $timestamp,
                'current_time' => $currentTime,
                'age' => $currentTime - $timestamp,
            ]);
            
            return [
                'valid' => false,
                'error' => 'Request expired',
            ];
        }
        
        // Verify signature
        $jsonPayload = wp_json_encode($payload);
        $isValid = $this->verify($jsonPayload, $signature, $algorithm);
        
        if (!$isValid) {
            $this->logger->warning('HMAC signature verification failed', [
                'algorithm' => $algorithm,
            ]);
            
            return [
                'valid' => false,
                'error' => 'Invalid signature',
            ];
        }
        
        return [
            'valid' => true,
            'data' => $payload['data'] ?? [],
            'timestamp' => $timestamp,
            'nonce' => $payload['nonce'] ?? '',
        ];
    }

    public function generateWebhookSignature(string $payload, string $secret = null): string
    {
        $secret = $secret ?: $this->secretKey;
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secret = null): bool
    {
        $secret = $secret ?: $this->secretKey;
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    public function rotateSecretKey(): string
    {
        $newKey = $this->generateSecretKey();
        $this->updateSecretKey($newKey);
        
        $this->logger->info('HMAC secret key rotated');
        
        return $newKey;
    }

    public function getSecretKey(): string
    {
        if (empty($this->secretKey)) {
            $this->secretKey = get_option('ai_agent_hmac_secret_key', '');
            
            if (empty($this->secretKey)) {
                $this->secretKey = $this->generateSecretKey();
                $this->updateSecretKey($this->secretKey);
            }
        }
        
        return $this->secretKey;
    }

    private function generateSecretKey(): string
    {
        return bin2hex(random_bytes(32)); // 64 character hex string
    }

    private function generateNonce(): string
    {
        return bin2hex(random_bytes(16)); // 32 character hex string
    }

    private function updateSecretKey(string $key): void
    {
        update_option('ai_agent_hmac_secret_key', $key);
        $this->secretKey = $key;
    }
}
