# AI Agent Plugin - Developer Guide

## Table of Contents
- [Overview](#overview)
- [Architecture](#architecture)
- [Getting Started](#getting-started)
- [Development Environment](#development-environment)
- [Code Structure](#code-structure)
- [Testing](#testing)
- [Code Quality](#code-quality)
- [Security](#security)
- [API Documentation](#api-documentation)
- [Contributing](#contributing)

## Overview

The AI Agent Plugin is a WordPress plugin that provides AI-powered content management capabilities. It follows Domain-Driven Design (DDD) principles and implements a clean, testable architecture.

### Key Features
- AI-powered content creation and editing
- Policy-based security controls
- REST API for external integrations
- Comprehensive audit logging
- Role-based access control

## Architecture

The plugin follows a layered architecture with clear separation of concerns:

```
┌─────────────────────────────────────┐
│           Presentation Layer        │
│  (Admin UI, Frontend, REST API)     │
├─────────────────────────────────────┤
│          Application Layer          │
│        (Service Providers)          │
├─────────────────────────────────────┤
│            Domain Layer             │
│    (Entities, Contracts, Policies)  │
├─────────────────────────────────────┤
│         Infrastructure Layer        │
│  (Database, Security, Hooks, Queue) │
└─────────────────────────────────────┘
```

### Core Components

- **Core**: Plugin bootstrap and autoloader
- **Domain**: Business logic and entities
- **Application**: Service providers and orchestration
- **Infrastructure**: WordPress integration and external services
- **REST**: API controllers and endpoints

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- WordPress 6.0 or higher
- Composer
- Node.js (for development tools)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/your-org/ai-agent-plugin.git
cd ai-agent-plugin
```

2. Install dependencies:
```bash
composer install
```

3. Activate the plugin in WordPress admin

### Development Setup

1. Install development dependencies:
```bash
composer install --dev
```

2. Set up pre-commit hooks:
```bash
composer run setup-hooks
```

3. Run tests:
```bash
composer test
```

## Development Environment

### Required Tools

- **PHP**: 8.1+ with extensions: mbstring, json, curl
- **Composer**: For dependency management
- **PHPUnit**: For testing
- **PHPStan**: For static analysis
- **PHPCS**: For code style checking

### IDE Configuration

#### VS Code
Install the following extensions:
- PHP Intelephense
- PHP CS Fixer
- PHPUnit
- WordPress Snippets

#### PhpStorm
Configure PHP interpreter and enable:
- PHP CS Fixer
- PHPStan integration
- WordPress support

### Environment Variables

Create a `.env` file in the plugin root:

```env
WP_DEBUG=true
WP_DEBUG_LOG=true
AI_AGENT_LOG_LEVEL=debug
AI_AGENT_API_KEY=your_api_key_here
```

## Code Structure

### Directory Layout

```
ai-agent/
├── src/                          # Source code
│   ├── Core/                     # Plugin core
│   ├── Domain/                   # Business logic
│   ├── Application/              # Service providers
│   ├── Infrastructure/           # WordPress integration
│   └── REST/                     # API controllers
├── tests/                        # Test files
│   ├── Unit/                     # Unit tests
│   ├── Integration/              # Integration tests
│   └── Performance/              # Performance tests
├── docs/                         # Documentation
├── scripts/                      # Utility scripts
└── assets/                       # Frontend assets
```

### Naming Conventions

- **Classes**: PascalCase (e.g., `ChatController`)
- **Methods**: camelCase (e.g., `processPrompt`)
- **Properties**: camelCase (e.g., `$userId`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `READ_CAPABILITY`)
- **Files**: kebab-case (e.g., `chat-controller.php`)

### Namespace Structure

```php
AIAgent\
├── Core\                    # Plugin core
├── Domain\
│   ├── Entities\            # Domain entities
│   ├── Contracts\           # Interfaces
│   └── Policies\            # Business rules
├── Application\
│   └── Providers\           # Service providers
├── Infrastructure\
│   ├── Database\            # Data access
│   ├── Security\            # Security components
│   ├── Hooks\               # WordPress hooks
│   └── Queue\               # Background processing
└── REST\
    └── Controllers\         # API controllers
```

## Testing

### Test Structure

The plugin uses PHPUnit for testing with three test suites:

- **Unit Tests**: Test individual classes in isolation
- **Integration Tests**: Test WordPress integration
- **Performance Tests**: Test performance benchmarks

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suite
composer test -- --testsuite=Unit
composer test -- --testsuite=Integration
composer test -- --testsuite=Performance

# Run with coverage
composer test-coverage

# Run performance tests only
composer test-performance
```

### Writing Tests

#### Unit Test Example

```php
<?php

namespace AIAgent\Tests\Unit\Domain;

use AIAgent\Domain\Entities\Post;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    public function testCanCreatePost(): void
    {
        $post = new Post('Test Title', 'Test Content');
        
        $this->assertEquals('Test Title', $post->getTitle());
        $this->assertEquals('Test Content', $post->getContent());
    }
}
```

#### Integration Test Example

```php
<?php

namespace AIAgent\Tests\Integration;

use AIAgent\REST\Controllers\ChatController;
use PHPUnit\Framework\TestCase;

class ChatControllerTest extends TestCase
{
    public function testChatEndpointRequiresAuthentication(): void
    {
        // Test authentication requirement
        $this->markTestIncomplete('Integration test needs WordPress environment');
    }
}
```

### Test Coverage

The plugin maintains 90%+ test coverage. Coverage reports are generated in the `coverage/` directory.

## Code Quality

### Static Analysis

The plugin uses multiple static analysis tools:

- **PHPStan**: Level 8 (strictest)
- **Psalm**: Additional type checking
- **PHPCS**: WordPress coding standards

### Running Quality Checks

```bash
# Run all quality checks
composer quality-check

# Individual tools
composer phpstan
composer psalm
composer phpcs

# Fix code style issues
composer cs-fix
```

### Code Style

The plugin follows WordPress coding standards with some customizations:

- Use short array syntax `[]` instead of `array()`
- Use strict type declarations
- Follow PSR-4 autoloading
- Use meaningful variable and method names

## Security

### Security Measures

- **Input Validation**: All inputs are sanitized and validated
- **Output Escaping**: All outputs are properly escaped
- **Nonce Verification**: CSRF protection on all forms
- **Capability Checks**: Role-based access control
- **SQL Injection Prevention**: Prepared statements only
- **XSS Prevention**: Proper escaping and sanitization

### Security Audit

Run the security audit script:

```bash
composer security-scan
```

This will check for:
- Hardcoded credentials
- SQL injection vulnerabilities
- XSS vulnerabilities
- Missing security measures

### Security Best Practices

1. **Always validate input**:
```php
$input = sanitize_text_field($_POST['input']);
```

2. **Escape output**:
```php
echo esc_html($user_content);
```

3. **Use nonces**:
```php
wp_nonce_field('action_name', 'nonce_field');
```

4. **Check capabilities**:
```php
if (!current_user_can('ai_agent_read')) {
    wp_die('Insufficient permissions');
}
```

## API Documentation

### REST API Endpoints

The plugin provides REST API endpoints for external integrations:

- `POST /wp-json/ai-agent/v1/chat` - Send chat messages
- `POST /wp-json/ai-agent/v1/dry-run` - Preview changes

### Authentication

All API endpoints require:
- WordPress user authentication
- Valid nonce for CSRF protection

### Example Usage

```javascript
// Send a chat message
const response = await fetch('/wp-json/ai-agent/v1/chat', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        prompt: 'Create a new blog post about WordPress security',
        mode: 'suggest'
    })
});

const data = await response.json();
console.log(data.data.suggested_actions);
```

### OpenAPI Specification

Complete API documentation is available in `docs/api/openapi.yaml`.

## Contributing

### Development Workflow

1. **Create a feature branch**:
```bash
git checkout -b feature/new-feature
```

2. **Make your changes** following the coding standards

3. **Write tests** for new functionality

4. **Run quality checks**:
```bash
composer quality-check
```

5. **Commit your changes**:
```bash
git commit -m "feat: add new feature"
```

6. **Push and create a pull request**

### Pull Request Guidelines

- Include tests for new functionality
- Update documentation as needed
- Follow the existing code style
- Ensure all quality checks pass
- Include a clear description of changes

### Code Review Process

All changes must be reviewed by at least one team member. Reviewers should check:

- Code quality and style
- Test coverage
- Security implications
- Performance impact
- Documentation updates

## Troubleshooting

### Common Issues

#### Tests Failing
- Ensure WordPress test environment is set up
- Check that all dependencies are installed
- Verify PHP version compatibility

#### Code Style Issues
- Run `composer cs-fix` to auto-fix issues
- Check PHPCS configuration
- Ensure IDE is configured correctly

#### Performance Issues
- Run performance tests to identify bottlenecks
- Check database query performance
- Monitor memory usage

### Getting Help

- Check the documentation in `docs/`
- Review existing issues on GitHub
- Ask questions in the team chat
- Create an issue for bugs or feature requests

## License

This plugin is licensed under the GPL-2.0-or-later license. See the LICENSE file for details.
