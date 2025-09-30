<?php

namespace AIAgent\Tests\Unit\Infrastructure\Security;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Security\SecurityMiddleware;
use AIAgent\Infrastructure\Security\HmacSigner;
use AIAgent\Infrastructure\Security\OAuth2Provider;
use AIAgent\Infrastructure\Security\RequestInterface;
use AIAgent\Support\Logger;

final class SecurityMiddlewareTest extends TestCase
{
    private SecurityMiddleware $middleware;
    private HmacSigner $hmacSigner;
    private OAuth2Provider $oauth2Provider;

    protected function setUp(): void
    {
        $logger = new Logger();
        $this->hmacSigner = new HmacSigner($logger, 'test-secret');
        $this->oauth2Provider = new OAuth2Provider($logger, []);
        $this->middleware = new SecurityMiddleware($logger, $this->hmacSigner, $this->oauth2Provider);
    }

    public function testCheckRateLimit(): void
    {
        $identifier = 'test-user';
        $limit = 5;
        $window = 60;
        
        // Should allow first 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($this->middleware->checkRateLimit($identifier, $limit, $window));
        }
        
        // Should block the 6th request
        $this->assertFalse($this->middleware->checkRateLimit($identifier, $limit, $window));
    }

    public function testCheckPermissions(): void
    {
        $requiredScopes = ['read', 'write'];
        $userScopes = ['read', 'write', 'admin'];
        
        $this->assertTrue($this->middleware->checkPermissions($requiredScopes, $userScopes));
    }

    public function testCheckPermissionsInsufficient(): void
    {
        $requiredScopes = ['read', 'write', 'admin'];
        $userScopes = ['read', 'write'];
        
        $this->assertFalse($this->middleware->checkPermissions($requiredScopes, $userScopes));
    }

    public function testSanitizeInput(): void
    {
        $input = [
            'post_title' => '<script>alert("xss")</script>Test Title',
            'post_content' => 'Normal content',
            'meta_data' => [
                'key1' => 'value1',
                'key2' => '<b>HTML</b> content',
            ],
            'numeric_value' => '123',
            'float_value' => '123.45',
        ];
        
        $sanitized = $this->middleware->sanitizeInput($input);
        
        $this->assertIsArray($sanitized);
        $this->assertArrayHasKey('post_title', $sanitized);
        $this->assertArrayHasKey('post_content', $sanitized);
        $this->assertArrayHasKey('meta_data', $sanitized);
        $this->assertArrayHasKey('numeric_value', $sanitized);
        $this->assertArrayHasKey('float_value', $sanitized);
        
        // Check that HTML was stripped
        $this->assertStringNotContainsString('<script>', $sanitized['post_title']);
        $this->assertStringNotContainsString('<b>', $sanitized['meta_data']['key2']);
        
        // Check numeric conversion
        $this->assertIsInt($sanitized['numeric_value']);
        $this->assertIsFloat($sanitized['float_value']);
    }

    public function testValidateHmacSignature(): void
    {
        // Create a mock request with HMAC signature
        $data = 'test data';
        $signature = $this->hmacSigner->sign($data);
        
        $request = $this->createMockRequest([
            'x-hmac-signature' => $signature,
            'x-hmac-algorithm' => 'sha256',
        ], $data);
        
        $this->assertTrue($this->middleware->validateHmacSignature($request));
    }

    public function testValidateInvalidHmacSignature(): void
    {
        $request = $this->createMockRequest([
            'x-hmac-signature' => 'invalid-signature',
            'x-hmac-algorithm' => 'sha256',
        ], 'test data');
        
        $this->assertFalse($this->middleware->validateHmacSignature($request));
    }

    public function testValidateWebhookSignature(): void
    {
        $payload = 'webhook payload';
        $signature = $this->hmacSigner->generateWebhookSignature($payload);
        
        $request = $this->createMockRequest([
            'x-hub-signature-256' => $signature,
        ], $payload);
        
        $this->assertTrue($this->middleware->validateWebhookSignature($request));
    }

    public function testValidateInvalidWebhookSignature(): void
    {
        $request = $this->createMockRequest([
            'x-hub-signature-256' => 'sha256=invalid',
        ], 'webhook payload');
        
        $this->assertFalse($this->middleware->validateWebhookSignature($request));
    }

    public function testLogSecurityEvent(): void
    {
        // This test just ensures the method doesn't throw an exception
        $this->middleware->logSecurityEvent('test_event', ['key' => 'value']);
        
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    private function createMockRequest(array $headers, string $body): RequestInterface
    {
        return new class($headers, $body) implements RequestInterface {
            private array $headers;
            private string $body;
            
            public function __construct(array $headers, string $body) {
                $this->headers = $headers;
                $this->body = $body;
            }
            
            public function get_header(string $name): string {
                return $this->headers[$name] ?? '';
            }
            
            public function get_body(): string {
                return $this->body;
            }
            
            public function get_route(): string {
                return '/test/route';
            }
        };
    }
}
