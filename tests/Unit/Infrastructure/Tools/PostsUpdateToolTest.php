<?php
namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Tools\PostsUpdateTool;

final class PostsUpdateToolTest extends TestCase
{
    public function testInvalidInput(): void
    {
        $tool = new PostsUpdateTool();
        $this->assertSame(['error' => 'invalid_input'], $tool->execute([]));
    }

    public function testWpUnavailable(): void
    {
        $tool = new PostsUpdateTool();
        $result = $tool->execute(['id' => 1, 'fields' => ['post_title' => 'X']]);
        $this->assertSame('wp_unavailable', $result['error'] ?? null);
    }
}


