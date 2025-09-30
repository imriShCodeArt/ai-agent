<?php

namespace AIAgent\Infrastructure\Security;

/**
 * Adapter to make WP_REST_Request compatible with RequestInterface
 */
final class WPRestRequestAdapter implements RequestInterface
{
    /** @var object */
    private $request; // @phpstan-ignore-line

    /**
     * @param object $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    public function get_header(string $name): string
    {
        // @phpstan-ignore-next-line runtime method
        $value = $this->request->get_header($name);
        return $value !== null ? (string) $value : '';
    }

    public function get_body(): string
    {
        // @phpstan-ignore-next-line runtime method
        return (string) $this->request->get_body();
    }

    public function get_route(): string
    {
        // @phpstan-ignore-next-line runtime method
        return (string) $this->request->get_route();
    }
}
