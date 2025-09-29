# AI Agent Plugin - Simple Test Suite (PowerShell)
# This script performs basic validation of the plugin structure

Write-Host "AI Agent Plugin - Basic Test Suite" -ForegroundColor Cyan
Write-Host "====================================" -ForegroundColor Cyan
Write-Host ""

$errors = @()
$warnings = @()
$passed = 0
$total = 0

function Test-Item {
    param(
        [string]$Name,
        [bool]$Condition,
        [string]$ErrorMessage = ""
    )
    
    $script:total++
    
    if ($Condition) {
        Write-Host "PASS: $Name" -ForegroundColor Green
        $script:passed++
    } else {
        Write-Host "FAIL: $Name" -ForegroundColor Red
        if ($ErrorMessage) {
            Write-Host "   - $ErrorMessage" -ForegroundColor Red
        }
        $script:errors += $Name
    }
}

# Test 1: Check if required files exist
Write-Host "File Structure Tests" -ForegroundColor Cyan
Write-Host "-------------------" -ForegroundColor Cyan

$requiredFiles = @(
    "ai-agent.php",
    "composer.json",
    "phpunit.xml.dist",
    "phpstan.neon.dist",
    "psalm.xml",
    "phpcs.xml.dist",
    "README.md",
    "docs/DEVELOPER_GUIDE.md",
    "docs/api/openapi.yaml",
    "src/Core/Plugin.php",
    "src/Core/Autoloader.php",
    "tests/bootstrap.php",
    "scripts/security-audit.php"
)

foreach ($file in $requiredFiles) {
    Test-Item "File exists: $file" (Test-Path $file)
}

Write-Host ""
Write-Host "Configuration Tests" -ForegroundColor Cyan
Write-Host "------------------" -ForegroundColor Cyan

# Test 2: Check composer.json structure
if (Test-Path "composer.json") {
    try {
        $composer = Get-Content "composer.json" | ConvertFrom-Json
        Test-Item "Composer.json is valid JSON" $true
        
        Test-Item "Has autoload configuration" ($composer.autoload -ne $null)
        Test-Item "Has dev dependencies" ($composer.'require-dev' -ne $null)
        Test-Item "Has test scripts" ($composer.scripts.test -ne $null)
        Test-Item "Has PHPStan script" ($composer.scripts.phpstan -ne $null)
        Test-Item "Has PHPCS script" ($composer.scripts.phpcs -ne $null)
        Test-Item "Has security scan script" ($composer.scripts.'security-scan' -ne $null)
    } catch {
        Test-Item "Composer.json is valid JSON" $false "Invalid JSON format"
    }
}

# Test 3: Check PHPUnit configuration
if (Test-Path "phpunit.xml.dist") {
    Test-Item "PHPUnit config exists" $true
    $phpunitContent = Get-Content "phpunit.xml.dist" -Raw
    Test-Item "Has bootstrap file" ($phpunitContent -match 'bootstrap=')
    Test-Item "Has test suites" ($phpunitContent -match 'testsuites')
    Test-Item "Has coverage configuration" ($phpunitContent -match 'coverage')
}

# Test 4: Check PHPStan configuration
if (Test-Path "phpstan.neon.dist") {
    $phpstanContent = Get-Content "phpstan.neon.dist" -Raw
    Test-Item "PHPStan config exists" $true
    Test-Item "PHPStan level 8 configured" ($phpstanContent -match 'level: 8')
}

Write-Host ""
Write-Host "Code Structure Tests" -ForegroundColor Cyan
Write-Host "-------------------" -ForegroundColor Cyan

# Test 5: Check for required directories
$requiredDirs = @(
    "src",
    "src/Core",
    "src/Domain",
    "src/Application",
    "src/Infrastructure",
    "src/REST",
    "tests",
    "tests/Unit",
    "tests/Integration",
    "tests/Performance",
    "docs",
    "scripts"
)

foreach ($dir in $requiredDirs) {
    Test-Item "Directory exists: $dir" (Test-Path $dir)
}

# Test 6: Check for required classes
$requiredClasses = @(
    "src/Core/Plugin.php",
    "src/Core/Autoloader.php",
    "src/Infrastructure/ServiceContainer.php",
    "src/Infrastructure/Security/Policy.php",
    "src/REST/Controllers/ChatController.php"
)

foreach ($class in $requiredClasses) {
    Test-Item "Class file exists: $class" (Test-Path $class)
}

Write-Host ""
Write-Host "Test Structure Tests" -ForegroundColor Cyan
Write-Host "-------------------" -ForegroundColor Cyan

# Test 7: Check for test files
$testFiles = Get-ChildItem -Path "tests" -Recurse -Filter "*Test.php" | Measure-Object
Test-Item "Has test files" ($testFiles.Count -gt 0) "Found $($testFiles.Count) test files"

# Test 8: Check for test factories
$factoryFiles = Get-ChildItem -Path "tests" -Recurse -Filter "*Factory.php" | Measure-Object
Test-Item "Has test factories" ($factoryFiles.Count -gt 0) "Found $($factoryFiles.Count) factory files"

Write-Host ""
Write-Host "Documentation Tests" -ForegroundColor Cyan
Write-Host "------------------" -ForegroundColor Cyan

# Test 9: Check documentation files
$docFiles = @(
    "README.md",
    "docs/DEVELOPER_GUIDE.md",
    "docs/api/openapi.yaml",
    "TESTING_GUIDE.md"
)

foreach ($doc in $docFiles) {
    Test-Item "Documentation exists: $doc" (Test-Path $doc)
}

# Test 10: Check README content
if (Test-Path "README.md") {
    $readme = Get-Content "README.md" -Raw
    Test-Item "README has features section" ($readme -match "Features")
    Test-Item "README has installation section" ($readme -match "Installation")
    Test-Item "README has usage section" ($readme -match "Usage")
}

Write-Host ""
Write-Host "Security Tests" -ForegroundColor Cyan
Write-Host "-------------" -ForegroundColor Cyan

# Test 11: Check security files
Test-Item "Security audit script exists" (Test-Path "scripts/security-audit.php")
Test-Item "Pre-commit config exists" (Test-Path ".pre-commit-config.yaml")

# Test 12: Check for security patterns in code
$securityPatterns = @{
    "wp_verify_nonce" = "Nonce verification"
    "sanitize_text_field" = "Input sanitization"
    "esc_html" = "Output escaping"
    "current_user_can" = "Capability checks"
}

$securityFiles = Get-ChildItem -Path "src" -Recurse -Filter "*.php"
$securityFound = @{}

foreach ($file in $securityFiles) {
    $content = Get-Content $file.FullName -Raw
    foreach ($pattern in $securityPatterns.Keys) {
        if ($content -match $pattern) {
            $securityFound[$pattern] = $true
        }
    }
}

foreach ($pattern in $securityPatterns.Keys) {
    $description = $securityPatterns[$pattern]
    Test-Item "Security pattern found: $description" ($securityFound.ContainsKey($pattern))
}

Write-Host ""
Write-Host "Test Results Summary" -ForegroundColor Cyan
Write-Host "===================" -ForegroundColor Cyan
Write-Host "Total tests: $total"
Write-Host "Passed: $passed" -ForegroundColor Green
Write-Host "Failed: $($errors.Count)" -ForegroundColor Red

$successRate = if ($total -gt 0) { [math]::Round(($passed / $total) * 100, 1) } else { 0 }
Write-Host "Success rate: $successRate%"

if ($errors.Count -gt 0) {
    Write-Host ""
    Write-Host "Failed Tests:" -ForegroundColor Red
    foreach ($error in $errors) {
        Write-Host "  - $error" -ForegroundColor Red
    }
}

if ($errors.Count -eq 0) {
    Write-Host ""
    Write-Host "All basic tests passed! The plugin structure looks good." -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "1. Install PHP 8.1+ and Composer"
    Write-Host "2. Run: composer install"
    Write-Host "3. Run: composer test"
    Write-Host "4. Run: composer phpstan"
    Write-Host "5. Run: composer security-scan"
} else {
    Write-Host ""
    Write-Host "Some tests failed. Please fix the issues above." -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "For detailed testing instructions, see TESTING_GUIDE.md" -ForegroundColor Cyan
