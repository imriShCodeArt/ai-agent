<?php

namespace AIAgent\Infrastructure\Security;

use AIAgent\Support\Logger;
use AIAgent\Infrastructure\Security\RequestInterface;

final class SecurityMiddleware
{
    private Logger $logger;
    private HmacSigner $hmacSigner;
    private OAuth2Provider $oauth2Provider;
    private array $rateLimits = [];

    public function __construct(Logger $logger, HmacSigner $hmacSigner, OAuth2Provider $oauth2Provider)
    {
        $this->logger = $logger;
        $this->hmacSigner = $hmacSigner;
        $this->oauth2Provider = $oauth2Provider;
    }

    public function authenticateRequest(\WP_REST_Request $request): array
    {
        $authResult = [
            'authenticated' => false,
            'user_id' => null,
            'auth_method' => null,
            'scopes' => [],
            'error' => null,
        ];

        // Check for HMAC signature
        $hmacResult = $this->authenticateHmac($request);
        if ($hmacResult['authenticated']) {
            return $hmacResult;
        }

        // Check for OAuth2 token
        $oauthResult = $this->authenticateOAuth2($request);
        if ($oauthResult['authenticated']) {
            return $oauthResult;
        }

        // Check for application password
        $appPasswordResult = $this->authenticateApplicationPassword($request);
        if ($appPasswordResult['authenticated']) {
            return $appPasswordResult;
        }

        // Check for WordPress authentication
        $wpResult = $this->authenticateWordPress($request);
        if ($wpResult['authenticated']) {
            return $wpResult;
        }

        $this->logger->warning('Authentication failed for request', [
            'ip' => $this->getClientIp(),
            'user_agent' => $request->get_header('user_agent'),
            'endpoint' => $request->get_route(),
        ]);

        return $authResult;
    }

    public function checkRateLimit(string $identifier, int $limit, int $window): bool
    {
        $key = "rate_limit:{$identifier}:" . floor(time() / $window);
        $current = $this->rateLimits[$key] ?? 0;

        if ($current >= $limit) {
            $this->logger->warning('Rate limit exceeded', [
                'identifier' => $identifier,
                'limit' => $limit,
                'window' => $window,
                'current' => $current,
            ]);
            return false;
        }

        $this->rateLimits[$key] = $current + 1;
        return true;
    }

    public function checkPermissions(array $requiredScopes, array $userScopes): bool
    {
        foreach ($requiredScopes as $scope) {
            if (!in_array($scope, $userScopes, true)) {
                $this->logger->warning('Insufficient permissions', [
                    'required' => $requiredScopes,
                    'user_scopes' => $userScopes,
                ]);
                return false;
            }
        }

        return true;
    }

    public function validateHmacSignature(RequestInterface $request): bool
    {
        $signature = $request->get_header('x-hmac-signature');
        $algorithm = $request->get_header('x-hmac-algorithm') ?: 'sha256';
        
        if (empty($signature)) {
            return false;
        }

        $body = $request->get_body();
        return $this->hmacSigner->verify($body, $signature, $algorithm);
    }

    public function validateWebhookSignature(RequestInterface $request): bool
    {
        $signature = $request->get_header('x-hub-signature-256');
        
        if (empty($signature)) {
            return false;
        }

        $body = $request->get_body();
        return $this->hmacSigner->verifyWebhookSignature($body, $signature);
    }

    public function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $key = function_exists('sanitize_key') ? sanitize_key($key) : preg_replace('/[^a-z0-9_-]/', '', strtolower($key));
            
            if (is_string($value)) {
                $value = function_exists('sanitize_text_field') ? sanitize_text_field($value) : strip_tags($value);
            } elseif (is_array($value)) {
                $value = $this->sanitizeInput($value);
            } elseif (is_numeric($value)) {
                $value = is_float($value) ? (float) $value : (int) $value;
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }

    public function logSecurityEvent(string $event, array $context = []): void
    {
        $this->logger->info('Security event', array_merge([
            'event' => $event,
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'),
        ], $context));
    }

    private function authenticateHmac(RequestInterface $request): array
    {
        $signature = $request->get_header('x-hmac-signature');
        
        if (empty($signature)) {
            return ['authenticated' => false];
        }

        try {
            $body = $request->get_body();
            $algorithm = $request->get_header('x-hmac-algorithm') ?: 'sha256';
            
            if ($this->hmacSigner->verify($body, $signature, $algorithm)) {
                return [
                    'authenticated' => true,
                    'user_id' => 0, // System user
                    'auth_method' => 'hmac',
                    'scopes' => ['system'],
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('HMAC authentication error', [
                'error' => $e->getMessage(),
            ]);
        }

        return ['authenticated' => false];
    }

    private function authenticateOAuth2(\WP_REST_Request $request): array
    {
        $authHeader = $request->get_header('authorization');
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return ['authenticated' => false];
        }

        $token = $matches[1];

        try {
            if ($this->oauth2Provider->validateToken($token)) {
                $userInfo = $this->oauth2Provider->getUserInfo($token);
                
                return [
                    'authenticated' => true,
                    'user_id' => $userInfo['id'] ?? 0,
                    'auth_method' => 'oauth2',
                    'scopes' => $userInfo['scopes'] ?? ['read'],
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('OAuth2 authentication error', [
                'error' => $e->getMessage(),
            ]);
        }

        return ['authenticated' => false];
    }

    private function authenticateApplicationPassword(\WP_REST_Request $request): array
    {
        $authHeader = $request->get_header('authorization');
        
        if (empty($authHeader) || !preg_match('/Basic\s+(.*)$/i', $authHeader, $matches)) {
            return ['authenticated' => false];
        }

        $credentials = base64_decode($matches[1]);
        if (!$credentials || !strpos($credentials, ':')) {
            return ['authenticated' => false];
        }

        [$username, $password] = explode(':', $credentials, 2);
        $user = get_user_by('login', $username);
        
        if (!$user) {
            return ['authenticated' => false];
        }

        try {
            $result = $this->oauth2Provider->validateApplicationPassword($user->ID, $password);
            
            if ($result['valid']) {
                return [
                    'authenticated' => true,
                    'user_id' => $user->ID,
                    'auth_method' => 'app_password',
                    'scopes' => $result['scopes'],
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Application password authentication error', [
                'error' => $e->getMessage(),
                'username' => $username,
            ]);
        }

        return ['authenticated' => false];
    }

    private function authenticateWordPress(\WP_REST_Request $request): array
    {
        if (!is_user_logged_in()) {
            return ['authenticated' => false];
        }

        $user = wp_get_current_user();
        
        return [
            'authenticated' => true,
            'user_id' => $user->ID,
            'auth_method' => 'wordpress',
            'scopes' => $this->getUserScopes($user),
        ];
    }

    private function getUserScopes(\WP_User $user): array
    {
        $scopes = ['read'];
        
        if ($user->has_cap('ai_agent_edit_posts')) {
            $scopes[] = 'write';
        }
        
        if ($user->has_cap('ai_agent_manage_policies')) {
            $scopes[] = 'admin';
        }
        
        if ($user->has_cap('manage_options')) {
            $scopes[] = 'super_admin';
        }
        
        return $scopes;
    }

    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
