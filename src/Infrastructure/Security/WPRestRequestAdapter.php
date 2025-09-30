<?php

namespace AIAgent\Infrastructure\Security;

/**
 * Adapter to make WP_REST_Request compatible with RequestInterface
 */
final class WPRestRequestAdapter implements RequestInterface
{
    /** @var \WP_REST_Request */
    private $request;

    public function __construct(\WP_REST_Request $request)
    {
        $this->request = $request;
    }

    public function get_header(string $name): string
    {
        $value = $this->request->get_header($name);
        return $value !== null ? (string) $value : '';
    }

    public function get_body(): string
    {
        return (string) $this->request->get_body();
    }

    public function get_route(): string
    {
        return (string) $this->request->get_route();
    }
}
