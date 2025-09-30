<?php

namespace AIAgent\Infrastructure\Security;

use AIAgent\Support\Logger;

final class OAuth2Provider
{
    private Logger $logger;
    private array $config;

    public function __construct(Logger $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = array_merge([
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => '',
            'authorization_url' => '',
            'token_url' => '',
            'user_info_url' => '',
            'scopes' => ['read', 'write'],
        ], $config);
    }

    public function getAuthorizationUrl(string $state = null): string
    {
        $state = $state ?: $this->generateState();
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(' ', $this->config['scopes']),
            'state' => $state,
        ];
        
        $url = $this->config['authorization_url'] . '?' . http_build_query($params);
        
        $this->logger->info('OAuth2 authorization URL generated', [
            'state' => $state,
            'scopes' => $this->config['scopes'],
        ]);
        
        return $url;
    }

    public function exchangeCodeForToken(string $code, ?string $state = null): array
    {
        if (!$this->validateState($state)) {
            throw new \InvalidArgumentException('Invalid state parameter');
        }
        
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->config['redirect_uri'],
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ];
        
        $response = $this->makeTokenRequest($data);
        
        if (isset($response['error'])) {
            $this->logger->error('OAuth2 token exchange failed', [
                'error' => $response['error'],
                'error_description' => $response['error_description'] ?? '',
            ]);
            
            throw new \Exception('Token exchange failed: ' . $response['error_description']);
        }
        
        $this->logger->info('OAuth2 token obtained successfully', [
            'token_type' => $response['token_type'] ?? 'unknown',
            'expires_in' => $response['expires_in'] ?? 0,
        ]);
        
        return $response;
    }

    public function refreshToken(string $refreshToken): array
    {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ];
        
        $response = $this->makeTokenRequest($data);
        
        if (isset($response['error'])) {
            $this->logger->error('OAuth2 token refresh failed', [
                'error' => $response['error'],
                'error_description' => $response['error_description'] ?? '',
            ]);
            
            throw new \Exception('Token refresh failed: ' . $response['error_description']);
        }
        
        $this->logger->info('OAuth2 token refreshed successfully');
        
        return $response;
    }

    public function getUserInfo(string $accessToken): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
        ];
        
        $response = wp_remote_get($this->config['user_info_url'], [
            'headers' => $headers,
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            $this->logger->error('OAuth2 user info request failed', [
                'error' => $response->get_error_message(),
            ]);
            
            throw new \Exception('Failed to get user info: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from user info endpoint');
        }
        
        $this->logger->info('OAuth2 user info retrieved successfully', [
            'user_id' => $data['id'] ?? 'unknown',
        ]);
        
        return $data;
    }

    public function validateToken(string $accessToken): bool
    {
        try {
            $userInfo = $this->getUserInfo($accessToken);
            return !empty($userInfo);
        } catch (\Exception $e) {
            $this->logger->warning('OAuth2 token validation failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function revokeToken(string $accessToken): bool
    {
        $data = [
            'token' => $accessToken,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ];
        
        $response = wp_remote_post($this->config['revoke_url'] ?? '', [
            'body' => $data,
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            $this->logger->error('OAuth2 token revocation failed', [
                'error' => $response->get_error_message(),
            ]);
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $success = $code >= 200 && $code < 300;
        
        $this->logger->info('OAuth2 token revocation', [
            'success' => $success,
            'status_code' => $code,
        ]);
        
        return $success;
    }

    public function createApplicationPassword(int $userId, string $name, array $scopes = []): array
    {
        if (!current_user_can('manage_options')) {
            throw new \Exception('Insufficient permissions to create application password');
        }
        
        $password = $this->generateApplicationPassword();
        $hashedPassword = wp_hash_password($password);
        
        $appPassword = [
            'name' => $name,
            'password' => $hashedPassword,
            'scopes' => $scopes,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id(),
            'last_used' => null,
        ];
        
        $existingPasswords = get_user_meta($userId, 'ai_agent_app_passwords', true) ?: [];
        $existingPasswords[] = $appPassword;
        
        update_user_meta($userId, 'ai_agent_app_passwords', $existingPasswords);
        
        $this->logger->info('Application password created', [
            'user_id' => $userId,
            'name' => $name,
            'scopes' => $scopes,
        ]);
        
        return [
            'id' => count($existingPasswords) - 1,
            'name' => $name,
            'password' => $password, // Only returned once
            'scopes' => $scopes,
            'created_at' => $appPassword['created_at'],
        ];
    }

    public function validateApplicationPassword(int $userId, string $password): array
    {
        $appPasswords = get_user_meta($userId, 'ai_agent_app_passwords', true) ?: [];
        
        foreach ($appPasswords as $index => $appPassword) {
            if (wp_check_password($password, $appPassword['password'])) {
                // Update last used timestamp
                $appPasswords[$index]['last_used'] = current_time('mysql');
                update_user_meta($userId, 'ai_agent_app_passwords', $appPasswords);
                
                $this->logger->info('Application password validated', [
                    'user_id' => $userId,
                    'name' => $appPassword['name'],
                ]);
                
                return [
                    'valid' => true,
                    'scopes' => $appPassword['scopes'],
                    'name' => $appPassword['name'],
                ];
            }
        }
        
        $this->logger->warning('Invalid application password attempt', [
            'user_id' => $userId,
        ]);
        
        return ['valid' => false];
    }

    public function revokeApplicationPassword(int $userId, int $passwordId): bool
    {
        $appPasswords = get_user_meta($userId, 'ai_agent_app_passwords', true) ?: [];
        
        if (!isset($appPasswords[$passwordId])) {
            return false;
        }
        
        $passwordName = $appPasswords[$passwordId]['name'];
        unset($appPasswords[$passwordId]);
        $appPasswords = array_values($appPasswords); // Re-index array
        
        update_user_meta($userId, 'ai_agent_app_passwords', $appPasswords);
        
        $this->logger->info('Application password revoked', [
            'user_id' => $userId,
            'name' => $passwordName,
        ]);
        
        return true;
    }

    public function getApplicationPasswords(int $userId): array
    {
        $appPasswords = get_user_meta($userId, 'ai_agent_app_passwords', true) ?: [];
        
        return array_map(function($password, $index) {
            return [
                'id' => $index,
                'name' => $password['name'],
                'scopes' => $password['scopes'],
                'created_at' => $password['created_at'],
                'last_used' => $password['last_used'],
            ];
        }, $appPasswords, array_keys($appPasswords));
    }

    private function makeTokenRequest(array $data): array
    {
        $response = wp_remote_post($this->config['token_url'], [
            'body' => $data,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('Token request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from token endpoint');
        }
        
        return $data;
    }

    private function generateState(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function validateState(?string $state): bool
    {
        // In a real implementation, you would store and validate the state
        // For now, we'll just check if it's a valid hex string
        return $state !== null && ctype_xdigit($state) && strlen($state) === 32;
    }

    private function generateApplicationPassword(): string
    {
        // Generate a secure application password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        
        for ($i = 0; $i < 24; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}
