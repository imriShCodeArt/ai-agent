<?php

namespace AIAgent\Tests\Unit\REST\Controllers;

use AIAgent\REST\Controllers\ReviewController;
use AIAgent\Support\Logger;
use PHPUnit\Framework\TestCase;

final class ReviewControllerTest extends TestCase
{
    public function testListReturnsResponse(): void
    {
        $c = new ReviewController(new Logger());
        $res = $c->list(new \WP_REST_Request(['page' => 1, 'per_page' => 10]));
        $this->assertInstanceOf(\WP_REST_Response::class, $res);
    }

    public function testApproveRejectCommentDoNotError(): void
    {
        $c = new ReviewController(new Logger());
        $this->assertInstanceOf(\WP_REST_Response::class, $c->approve(new \WP_REST_Request(['id' => 1])));
        $this->assertInstanceOf(\WP_REST_Response::class, $c->reject(new \WP_REST_Request(['id' => 1, 'reason' => 'nope'])));
        $this->assertInstanceOf(\WP_REST_Response::class, $c->comment(new \WP_REST_Request(['id' => 1, 'comment' => 'ok'])));
    }

    public function testNotifyEndpoint(): void
    {
        $c = new ReviewController(new Logger());
        $res = $c->notify(new \WP_REST_Request(['id' => 1, 'type' => 'pending', 'message' => 'test']));
        $this->assertInstanceOf(\WP_REST_Response::class, $res);
    }
}


