<?php

namespace AIAgent\Infrastructure\Database;

interface MigrationInterface
{
    public function up(): void;
    public function down(): void;
    public function getVersion(): string;
    public function getDescription(): string;
}
