<?php
namespace AIAgent\Infrastructure;

final class ServiceContainer {
	/**
	 * @var array<string, callable>
	 */
	private array $bindings = [];

public function bind(string $id, callable $factory): void {
$this->bindings[$id] = $factory;
}

public function singleton(string $id, callable $factory): void {
$this->bindings[$id] = static function () use ($factory) {
static $instance;
if ($instance === null) {
$instance = $factory();
}
return $instance;
};
}

	/**
	 * @return mixed
	 */
	public function get(string $id) {
if (!isset($this->bindings[$id])) {
throw new \InvalidArgumentException('Service not bound: ' . $id);
}
return ($this->bindings[$id])();
}
}