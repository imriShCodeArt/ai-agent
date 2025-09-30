<?php

namespace AIAgent\Infrastructure\Security;

interface RequestInterface
{
    public function get_header(string $name): string;
    public function get_body(): string;
}
