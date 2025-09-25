<?php
namespace AIAgent\Infrastructure\Hooks;

final class HooksLoader {
/** @var HookableInterface[] */
private array $hookables = [];

public function add(HookableInterface $hookable): void {
$this->hookables[] = $hookable;
}

public function register(): void {
foreach ($this->hookables as $hookable) {
$hookable->addHooks();
}
}
}