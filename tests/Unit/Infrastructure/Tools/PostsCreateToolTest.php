<?php
namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Tools\PostsCreateTool;

final class PostsCreateToolTest extends TestCase
{
    public function testInvalidInput(): void
    {
        $tool = new PostsCreateTool();
        $this->assertSame(['error' => 'invalid_input'], $tool->execute([]));
    }

    public function testWpUnavailable(): void
    {
        $tool = new PostsCreateTool();
        $result = $tool->execute(['fields' => ['post_title' => 'T', 'post_content' => 'C']]);
        $this->assertSame('wp_unavailable', $result['error'] ?? null);
    }
}


