<?php

namespace AIAgent\Tests\Unit\Infrastructure\Security;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Security\HmacSigner;
use AIAgent\Support\Logger;

final class HmacSignerTest extends TestCase
{
    private HmacSigner $signer;

    protected function setUp(): void
    {
        $logger = new Logger();
        $this->signer = new HmacSigner($logger, 'test-secret-key');
    }

    public function testSignData(): void
    {
        $data = 'test data';
        $signature = $this->signer->sign($data);
        
        $this->assertIsString($signature);
        $this->assertNotEmpty($signature);
    }

    public function testVerifyValidSignature(): void
    {
        $data = 'test data';
        $signature = $this->signer->sign($data);
        
        $isValid = $this->signer->verify($data, $signature);
        
        $this->assertTrue($isValid);
    }

    public function testVerifyInvalidSignature(): void
    {
        $data = 'test data';
        $invalidSignature = 'invalid-signature';
        
        $isValid = $this->signer->verify($data, $invalidSignature);
        
        $this->assertFalse($isValid);
    }

    public function testSignRequest(): void
    {
        $data = ['key' => 'value'];
        $result = $this->signer->signRequest($data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('payload', $result);
        $this->assertArrayHasKey('signature', $result);
        $this->assertArrayHasKey('algorithm', $result);
        $this->assertArrayHasKey('data', $result['payload']);
        $this->assertArrayHasKey('timestamp', $result['payload']);
        $this->assertArrayHasKey('nonce', $result['payload']);
    }

    public function testVerifyRequest(): void
    {
        $data = ['key' => 'value'];
        $signedRequest = $this->signer->signRequest($data);
        
        $result = $this->signer->verifyRequest($signedRequest);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['valid']);
        $this->assertEquals($data, $result['data']);
    }

    public function testVerifyExpiredRequest(): void
    {
        $data = ['key' => 'value'];
        $signedRequest = $this->signer->signRequest($data);
        
        // Manually set old timestamp
        $signedRequest['payload']['timestamp'] = time() - 400; // 6+ minutes ago
        
        $result = $this->signer->verifyRequest($signedRequest);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Request expired', $result['error']);
    }

    public function testVerifyInvalidRequest(): void
    {
        $invalidRequest = [
            'payload' => ['data' => ['key' => 'value']],
            'signature' => 'invalid-signature',
        ];
        
        $result = $this->signer->verifyRequest($invalidRequest);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid signature', $result['error']);
    }

    public function testGenerateWebhookSignature(): void
    {
        $payload = 'webhook payload';
        $signature = $this->signer->generateWebhookSignature($payload);
        
        $this->assertStringStartsWith('sha256=', $signature);
        $this->assertIsString($signature);
    }

    public function testVerifyWebhookSignature(): void
    {
        $payload = 'webhook payload';
        $signature = $this->signer->generateWebhookSignature($payload);
        
        $isValid = $this->signer->verifyWebhookSignature($payload, $signature);
        
        $this->assertTrue($isValid);
    }

    public function testVerifyInvalidWebhookSignature(): void
    {
        $payload = 'webhook payload';
        $invalidSignature = 'sha256=invalid';
        
        $isValid = $this->signer->verifyWebhookSignature($payload, $invalidSignature);
        
        $this->assertFalse($isValid);
    }

    public function testSignWithDifferentAlgorithm(): void
    {
        $data = 'test data';
        $sha1Signature = $this->signer->sign($data, 'sha1');
        $sha256Signature = $this->signer->sign($data, 'sha256');
        
        $this->assertNotEquals($sha1Signature, $sha256Signature);
        
        $this->assertTrue($this->signer->verify($data, $sha1Signature, 'sha1'));
        $this->assertTrue($this->signer->verify($data, $sha256Signature, 'sha256'));
    }

    public function testSignWithoutSecretKey(): void
    {
        $logger = new Logger();
        $signer = new HmacSigner($logger, '');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Secret key is required for HMAC signing');
        
        $signer->sign('test data');
    }

    public function testRotateSecretKey(): void
    {
        $originalKey = $this->signer->getSecretKey();
        $newKey = $this->signer->rotateSecretKey();
        
        $this->assertNotEquals($originalKey, $newKey);
        $this->assertEquals($newKey, $this->signer->getSecretKey());
    }

    public function testGenerateNonce(): void
    {
        $nonce1 = $this->signer->signRequest(['test' => 'data'])['payload']['nonce'];
        $nonce2 = $this->signer->signRequest(['test' => 'data'])['payload']['nonce'];
        
        $this->assertNotEquals($nonce1, $nonce2);
        $this->assertEquals(32, strlen($nonce1));
        $this->assertEquals(32, strlen($nonce2));
    }
}
