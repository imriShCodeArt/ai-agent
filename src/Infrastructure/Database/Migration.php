<?php

namespace AIAgent\Infrastructure\Database;

final class Migration implements MigrationInterface
{
    private string $version;
    private string $description;
    /** @var callable */
    private $up;
    /** @var callable */
    private $down;

    public function __construct(string $version, string $description, callable $up, callable $down)
    {
        $this->version = $version;
        $this->description = $description;
        $this->up = $up;
        $this->down = $down;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function up(): void
    {
        ($this->up)();
    }

    public function down(): void
    {
        ($this->down)();
    }
}
