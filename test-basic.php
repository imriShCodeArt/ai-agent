<?php
/**
 * Basic Test Script for AI Agent Plugin
 * 
 * This script performs basic validation of the plugin structure
 * without requiring Composer or WordPress environment.
 */

echo "🧪 AI Agent Plugin - Basic Test Suite\n";
echo "=====================================\n\n";

$errors = [];
$warnings = [];
$passed = 0;
$total = 0;

function test($name, $condition, $errorMessage = '') {
    global $errors, $warnings, $passed, $total;
    $total++;
    
    if ($condition) {
        echo "✅ $name\n";
        $passed++;
    } else {
        echo "❌ $name";
        if ($errorMessage) {
            echo " - $errorMessage";
        }
        echo "\n";
        $errors[] = $name;
    }
}

function warn($name, $message) {
    global $warnings;
    echo "⚠️  $name - $message\n";
    $warnings[] = $name;
}

// Test 1: Check if required files exist
echo "📁 File Structure Tests\n";
echo "----------------------\n";

$requiredFiles = [
    'ai-agent.php' => 'Main plugin file',
    'composer.json' => 'Composer configuration',
    'phpunit.xml.dist' => 'PHPUnit configuration',
    'phpstan.neon.dist' => 'PHPStan configuration',
    'psalm.xml' => 'Psalm configuration',
    'phpcs.xml.dist' => 'PHPCS configuration',
    'README.md' => 'Project documentation',
    'docs/DEVELOPER_GUIDE.md' => 'Developer guide',
    'docs/api/openapi.yaml' => 'API documentation',
    'src/Core/Plugin.php' => 'Core plugin class',
    'src/Core/Autoloader.php' => 'Autoloader class',
    'tests/bootstrap.php' => 'Test bootstrap file',
    'scripts/security-audit.php' => 'Security audit script',
];

foreach ($requiredFiles as $file => $description) {
    test("File exists: $file", file_exists($file), "Missing $description");
}

echo "\n📋 Configuration Tests\n";
echo "---------------------\n";

// Test 2: Check composer.json structure
if (file_exists('composer.json')) {
    $composer = json_decode(file_get_contents('composer.json'), true);
    test("Composer.json is valid JSON", $composer !== null);
    
    if ($composer) {
        test("Has autoload configuration", isset($composer['autoload']));
        test("Has dev dependencies", isset($composer['require-dev']));
        test("Has test scripts", isset($composer['scripts']['test']));
        test("Has PHPStan script", isset($composer['scripts']['phpstan']));
        test("Has PHPCS script", isset($composer['scripts']['phpcs']));
        test("Has security scan script", isset($composer['scripts']['security-scan']));
    }
}

// Test 3: Check PHPUnit configuration
if (file_exists('phpunit.xml.dist')) {
    $phpunit = simplexml_load_file('phpunit.xml.dist');
    test("PHPUnit config is valid XML", $phpunit !== false);
    
    if ($phpunit) {
        test("Has bootstrap file", isset($phpunit['bootstrap']));
        test("Has test suites", isset($phpunit->testsuites));
        test("Has coverage configuration", isset($phpunit->coverage));
    }
}

// Test 4: Check PHPStan configuration
if (file_exists('phpstan.neon.dist')) {
    $phpstan = file_get_contents('phpstan.neon.dist');
    test("PHPStan config exists", !empty($phpstan));
    test("PHPStan level 8 configured", strpos($phpstan, 'level: 8') !== false);
}

// Test 5: Check Psalm configuration
if (file_exists('psalm.xml')) {
    $psalm = simplexml_load_file('psalm.xml');
    test("Psalm config is valid XML", $psalm !== false);
}

echo "\n🔍 Code Quality Tests\n";
echo "--------------------\n";

// Test 6: Check for basic PHP syntax
$phpFiles = glob('src/**/*.php');
$syntaxErrors = 0;

foreach ($phpFiles as $file) {
    $output = [];
    $returnCode = 0;
    exec("php -l \"$file\" 2>&1", $output, $returnCode);
    
    if ($returnCode !== 0) {
        $syntaxErrors++;
        warn("Syntax error in $file", implode(' ', $output));
    }
}

test("No PHP syntax errors", $syntaxErrors === 0, "$syntaxErrors files have syntax errors");

// Test 7: Check for required classes
$requiredClasses = [
    'AIAgent\\Core\\Plugin',
    'AIAgent\\Core\\Autoloader',
    'AIAgent\\Infrastructure\\ServiceContainer',
    'AIAgent\\Infrastructure\\Security\\Policy',
    'AIAgent\\REST\\Controllers\\ChatController',
];

foreach ($requiredClasses as $class) {
    $file = str_replace('AIAgent\\', 'src/', $class) . '.php';
    test("Class file exists: $class", file_exists($file));
}

echo "\n🧪 Test Structure Tests\n";
echo "----------------------\n";

// Test 8: Check test directory structure
$testDirs = ['tests/Unit', 'tests/Integration', 'tests/Performance'];
foreach ($testDirs as $dir) {
    test("Test directory exists: $dir", is_dir($dir));
}

// Test 9: Check for test files
$testFiles = glob('tests/**/*Test.php');
test("Has test files", count($testFiles) > 0, "Found " . count($testFiles) . " test files");

// Test 10: Check for test factories
$factoryFiles = glob('tests/**/*Factory.php');
test("Has test factories", count($factoryFiles) > 0, "Found " . count($factoryFiles) . " factory files");

echo "\n📚 Documentation Tests\n";
echo "---------------------\n";

// Test 11: Check documentation files
$docFiles = [
    'README.md',
    'docs/DEVELOPER_GUIDE.md',
    'docs/api/openapi.yaml',
    'TESTING_GUIDE.md',
];

foreach ($docFiles as $doc) {
    test("Documentation exists: $doc", file_exists($doc));
}

// Test 12: Check README content
if (file_exists('README.md')) {
    $readme = file_get_contents('README.md');
    test("README has features section", strpos($readme, '## 🚀 Features') !== false);
    test("README has installation section", strpos($readme, '## 🛠️ Installation') !== false);
    test("README has usage section", strpos($readme, '## 📖 Usage') !== false);
}

echo "\n🔒 Security Tests\n";
echo "----------------\n";

// Test 13: Check security files
test("Security audit script exists", file_exists('scripts/security-audit.php'));
test("Pre-commit config exists", file_exists('.pre-commit-config.yaml'));

// Test 14: Check for security patterns in code
$securityPatterns = [
    'wp_verify_nonce' => 'Nonce verification',
    'sanitize_text_field' => 'Input sanitization',
    'esc_html' => 'Output escaping',
    'current_user_can' => 'Capability checks',
];

$securityFiles = glob('src/**/*.php');
$securityFound = [];

foreach ($securityFiles as $file) {
    $content = file_get_contents($file);
    foreach ($securityPatterns as $pattern => $description) {
        if (strpos($content, $pattern) !== false) {
            $securityFound[$pattern] = true;
        }
    }
}

foreach ($securityPatterns as $pattern => $description) {
    test("Security pattern found: $description", isset($securityFound[$pattern]));
}

echo "\n📊 Test Results Summary\n";
echo "======================\n";
echo "Total tests: $total\n";
echo "Passed: $passed\n";
echo "Failed: " . count($errors) . "\n";
echo "Warnings: " . count($warnings) . "\n";

$successRate = ($passed / $total) * 100;
echo "Success rate: " . number_format($successRate, 1) . "%\n\n";

if (!empty($errors)) {
    echo "❌ Failed Tests:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
    echo "\n";
}

if (count($errors) === 0) {
    echo "🎉 All basic tests passed! The plugin structure looks good.\n";
    echo "\nNext steps:\n";
    echo "1. Install PHP 8.1+ and Composer\n";
    echo "2. Run: composer install\n";
    echo "3. Run: composer test\n";
    echo "4. Run: composer phpstan\n";
    echo "5. Run: composer security-scan\n";
} else {
    echo "❌ Some tests failed. Please fix the issues above.\n";
    exit(1);
}

echo "\n📖 For detailed testing instructions, see TESTING_GUIDE.md\n";
