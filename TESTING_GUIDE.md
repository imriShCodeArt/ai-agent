# Local Testing Guide for AI Agent Plugin

## Prerequisites

Before running tests locally, ensure you have the following installed:

### Required Software
- **PHP 8.1+** with extensions: `mbstring`, `json`, `curl`, `xml`
- **Composer** for dependency management
- **WordPress 6.0+** (for integration tests)
- **MySQL 5.7+** or **MariaDB 10.3+**

### Optional but Recommended
- **Docker** and **Docker Compose** (for isolated testing)
- **Node.js** (for frontend asset compilation)
- **VS Code** with PHP extensions

## Environment Setup

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install development dependencies
composer install --dev
```

### 2. WordPress Test Environment

For integration tests, you'll need a WordPress test environment:

```bash
# Using WP-CLI (recommended)
wp core download --path=wordpress-test
wp config create --dbname=ai_agent_test --dbuser=root --dbpass=password
wp db create

# Or using Docker
docker-compose up -d
```

## Running Tests

### 1. Unit Tests

```bash
# Run all unit tests
composer test

# Run specific test suite
composer test -- --testsuite=Unit

# Run with coverage
composer test-coverage

# Run performance tests
composer test-performance
```

### 2. Code Quality Checks

```bash
# Run PHPStan (static analysis)
composer phpstan

# Run Psalm (additional static analysis)
composer psalm

# Run PHPCS (code style)
composer phpcs

# Fix code style issues
composer cs-fix

# Run security audit
composer security-scan
```

### 3. Integration Tests

```bash
# Run integration tests (requires WordPress)
composer test -- --testsuite=Integration

# Run all tests including integration
composer test
```

## Test Structure

### Unit Tests (`tests/Unit/`)
- **Core**: Plugin, Autoloader
- **Domain**: Entities, Contracts
- **Infrastructure**: ServiceContainer, Security, Hooks
- **Application**: Service Providers
- **REST**: Controllers
- **Support**: Factories, Utilities

### Integration Tests (`tests/Integration/`)
- Plugin activation/deactivation
- WordPress hook integration
- Database operations
- REST API endpoints

### Performance Tests (`tests/Performance/`)
- Database query performance
- Memory usage benchmarks
- Response time measurements
- Load testing

## Test Configuration

### PHPUnit Configuration (`phpunit.xml.dist`)
- **Bootstrap**: `tests/bootstrap.php`
- **Coverage**: HTML and Clover reports
- **Test Suites**: Unit, Integration, Performance
- **Groups**: Performance tests excluded by default

### Test Environment Variables
Create a `.env.test` file:

```env
WP_DEBUG=true
WP_DEBUG_LOG=true
AI_AGENT_LOG_LEVEL=debug
DB_NAME=ai_agent_test
DB_USER=root
DB_PASSWORD=password
DB_HOST=localhost
```

## Continuous Integration

The project includes GitHub Actions workflows:

### CI Pipeline (`.github/workflows/ci.yml`)
- Runs on: `feature/*`, `chore/*`, `refactor/*`, `fix/*`, `hotfix/*`, `main`, `development`
- PHP 8.1 with extensions
- Composer install
- PHPCS linting
- PHPStan analysis
- PHPUnit tests

### Release Pipeline (`.github/workflows/release.yml`)
- Runs on: `main` branch pushes
- Creates Git tags
- Generates GitHub releases

## Troubleshooting

### Common Issues

#### 1. PHP Version Mismatch
```bash
# Check PHP version
php -v

# Should be 8.1 or higher
# If not, install correct version or use Docker
```

#### 2. Missing Extensions
```bash
# Check loaded extensions
php -m | grep -E "(mbstring|json|curl|xml)"

# Install missing extensions
# Ubuntu/Debian: sudo apt-get install php8.1-mbstring php8.1-json php8.1-curl php8.1-xml
# CentOS/RHEL: sudo yum install php-mbstring php-json php-curl php-xml
```

#### 3. Composer Issues
```bash
# Update Composer
composer self-update

# Clear Composer cache
composer clear-cache

# Reinstall dependencies
rm -rf vendor/
composer install
```

#### 4. WordPress Test Environment
```bash
# Using WP-CLI
wp core download --path=wordpress-test --force
wp config create --dbname=ai_agent_test --dbuser=root --dbpass=password --force

# Or using Docker
docker-compose up -d
docker-compose exec wordpress wp core install --url=localhost:8080 --title="AI Agent Test" --admin_user=admin --admin_password=admin --admin_email=admin@example.com
```

#### 5. Database Connection Issues
```bash
# Test database connection
wp db check

# Create test database
wp db create

# Import test data (if needed)
wp db import test-data.sql
```

### Performance Issues

#### 1. Slow Tests
```bash
# Run tests in parallel
composer test -- --processes=4

# Run only unit tests (faster)
composer test -- --testsuite=Unit

# Skip performance tests
composer test -- --exclude-group=performance
```

#### 2. Memory Issues
```bash
# Increase PHP memory limit
php -d memory_limit=512M vendor/bin/phpunit

# Or set in php.ini
memory_limit = 512M
```

## Test Data

### Sample Test Data
The project includes test factories for creating sample data:

```php
// User factory
$user = UserFactory::create(['manage_options']);
$admin = UserFactory::createAdmin();
$editor = UserFactory::createEditor();

// Post factory
$post = PostFactory::create(['post_title' => 'Test Post']);
$draft = PostFactory::createDraft();
$page = PostFactory::createPage();
```

### Database Fixtures
Test database is automatically set up and torn down for each test.

## Coverage Reports

### HTML Coverage Report
```bash
composer test-coverage
# Opens coverage/index.html in browser
```

### Clover Coverage Report
```bash
composer test-coverage
# Generates coverage.xml for CI/CD
```

### Coverage Thresholds
- **Minimum Coverage**: 90%
- **Critical Files**: 95%
- **New Code**: 100%

## Pre-commit Hooks

The project includes pre-commit hooks for automated quality checks:

```bash
# Install pre-commit hooks
composer run setup-hooks

# Manual pre-commit check
composer run pre-commit
```

## Docker Testing

### Using Docker Compose
```bash
# Start test environment
docker-compose up -d

# Run tests in container
docker-compose exec php composer test

# Run specific test
docker-compose exec php composer test -- --filter=PluginTest

# Access WordPress
# http://localhost:8080
```

### Docker Configuration
See `docker-compose.yml` for complete configuration.

## Debugging

### Enable Debug Mode
```bash
# Set debug environment variables
export WP_DEBUG=true
export WP_DEBUG_LOG=true
export AI_AGENT_LOG_LEVEL=debug

# Run tests with debug output
composer test -- --verbose
```

### Test Debugging
```php
// In test files
$this->markTestIncomplete('Debugging test');
var_dump($variable);
error_log('Debug message');
```

## Best Practices

### Writing Tests
1. **One assertion per test** (when possible)
2. **Descriptive test names** that explain what's being tested
3. **Arrange-Act-Assert** pattern
4. **Mock external dependencies**
5. **Test edge cases and error conditions**

### Test Organization
1. **Group related tests** in the same class
2. **Use setUp() and tearDown()** for common setup
3. **Use data providers** for testing multiple scenarios
4. **Keep tests independent** and isolated

### Performance Testing
1. **Set realistic performance thresholds**
2. **Test with realistic data volumes**
3. **Monitor memory usage**
4. **Test under load conditions**

## Support

If you encounter issues:

1. **Check this guide** for common solutions
2. **Review test logs** for error details
3. **Check GitHub Issues** for known problems
4. **Create a new issue** with detailed information

### Issue Template
When creating an issue, include:
- PHP version (`php -v`)
- Composer version (`composer --version`)
- Test command that failed
- Complete error output
- Steps to reproduce
- Expected vs actual behavior
