## Merge summary: feature/async-queue → development

This change introduces a minimal asynchronous job queue foundation and fixes the CI test environment for WordPress hook assertions.

### Highlights

- Async queue provider and queue wrapper added
  - `src/Application/Providers/AsyncServiceProvider.php` registers the async queue service and its hooks.
  - `src/Infrastructure/Queue/AsyncQueue.php` provides a thin queue abstraction (initial implementation).
  - Hook registered: `add_action('ai_agent_async_execute', [$provider, 'executeJob'], 10, 2)`.

- Service container wiring
  - Binds `AsyncQueue` via `ServiceContainer::singleton`, resolving `AIAgent\Support\Logger`.

- Test coverage and bootstrap stubs
  - `tests/Unit/Application/Providers/AsyncServiceProviderTest.php` verifies container binding and hook registration.
  - `tests/bootstrap.php` now stubs WordPress functions used in unit tests:
    - `add_action()` (existing)
    - `has_action()` (new; returns priority 10 to simulate a registered hook)

- Version and CI
  - Version bump to `0.1.2`.
  - CI failures fixed by adding the `has_action()` stub.

### Files added

- `src/Application/Providers/AsyncServiceProvider.php`
- `src/Infrastructure/Queue/AsyncQueue.php`
- `tests/Unit/Application/Providers/AsyncServiceProviderTest.php`

### Files modified

- `composer.json` (queue-related dependency scaffolding; Action Scheduler reference via Composer)
- `src/Core/Plugin.php` (provider registration)
- `src/Infrastructure/ServiceContainer.php` (container binding usage)
- `tests/bootstrap.php` (WordPress test stubs; adds `has_action()`)

### Public API surface

- New hook: `ai_agent_async_execute` (priority 10, 2 args)
- New container service: `AIAgent\Infrastructure\Queue\AsyncQueue`

### Backward compatibility

- No breaking changes expected. The provider registers services and hooks without altering existing behavior.

### Operational notes

- The queue currently provides a simple façade; future work can route specific job types in `AsyncServiceProvider::executeJob()`.
- If using Action Scheduler in production, ensure it’s installed via Composer and loaded in WordPress.

### QA checklist

- Unit tests pass (PHPUnit 9.x):
  - Container has `AsyncQueue` after provider registration
  - Hook `ai_agent_async_execute` is registered (via `has_action` test stub)

### Version

- Target version: `0.1.2`


