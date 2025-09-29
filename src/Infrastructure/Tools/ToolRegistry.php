<?php
namespace AIAgent\Infrastructure\Tools;

final class ToolRegistry
{
    /** @var array<string, ToolInterface> */
    private array $tools = [];

    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): ToolInterface
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException('Tool not found: ' . $name);
        }
        return $this->tools[$name];
    }
}


