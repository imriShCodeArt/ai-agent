<?php
/**
 * Security Audit Script for AI Agent Plugin
 * 
 * This script performs various security checks on the codebase
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SecurityAudit
{
    private array $issues = [];
    private array $warnings = [];

    public function run(): void
    {
        echo "🔍 Starting security audit...\n\n";

        $this->checkFilePermissions();
        $this->checkSensitiveData();
        $this->checkSQLInjection();
        $this->checkXSSVulnerabilities();
        $this->checkCSRFProtection();
        $this->checkInputValidation();
        $this->checkOutputEscaping();
        $this->checkAuthentication();
        $this->checkAuthorization();

        $this->reportResults();
    }

    private function checkFilePermissions(): void
    {
        echo "📁 Checking file permissions...\n";
        
        $sensitiveFiles = [
            'ai-agent.php',
            'uninstall.php',
            'config/config.php',
        ];

        foreach ($sensitiveFiles as $file) {
            if (file_exists($file)) {
                $perms = fileperms($file);
                if ($perms & 0x0002) { // World writable
                    $this->issues[] = "File $file is world writable";
                }
            }
        }
    }

    private function checkSensitiveData(): void
    {
        echo "🔐 Checking for sensitive data exposure...\n";
        
        $patterns = [
            '/password\s*=\s*["\'][^"\']+["\']/' => 'Hardcoded password found',
            '/api_key\s*=\s*["\'][^"\']+["\']/' => 'Hardcoded API key found',
            '/secret\s*=\s*["\'][^"\']+["\']/' => 'Hardcoded secret found',
            '/token\s*=\s*["\'][^"\']+["\']/' => 'Hardcoded token found',
        ];

        $this->scanFiles($patterns, 'src/');
    }

    private function checkSQLInjection(): void
    {
        echo "💉 Checking for SQL injection vulnerabilities...\n";
        
        $patterns = [
            '/\$wpdb->query\s*\(\s*["\'][^"\']*\$/' => 'Potential SQL injection in wpdb->query',
            '/\$wpdb->get_var\s*\(\s*["\'][^"\']*\$/' => 'Potential SQL injection in wpdb->get_var',
            '/\$wpdb->get_results\s*\(\s*["\'][^"\']*\$/' => 'Potential SQL injection in wpdb->get_results',
        ];

        $this->scanFiles($patterns, 'src/');
    }

    private function checkXSSVulnerabilities(): void
    {
        echo "🛡️ Checking for XSS vulnerabilities...\n";
        
        $patterns = [
            '/echo\s+\$[a-zA-Z_][a-zA-Z0-9_]*\s*;/' => 'Potential XSS: direct echo without escaping',
            '/print\s+\$[a-zA-Z_][a-zA-Z0-9_]*\s*;/' => 'Potential XSS: direct print without escaping',
        ];

        $this->scanFiles($patterns, 'src/');
    }

    private function checkCSRFProtection(): void
    {
        echo "🔒 Checking CSRF protection...\n";
        
        $files = glob('src/**/*.php');
        $hasNonceVerification = false;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'wp_verify_nonce') !== false) {
                $hasNonceVerification = true;
                break;
            }
        }

        if (!$hasNonceVerification) {
            $this->warnings[] = 'No nonce verification found in REST controllers';
        }
    }

    private function checkInputValidation(): void
    {
        echo "✅ Checking input validation...\n";
        
        $patterns = [
            '/sanitize_text_field\s*\(/' => 'Good: sanitize_text_field usage',
            '/sanitize_textarea_field\s*\(/' => 'Good: sanitize_textarea_field usage',
            '/sanitize_email\s*\(/' => 'Good: sanitize_email usage',
            '/wp_kses\s*\(/' => 'Good: wp_kses usage',
        ];

        $this->scanFiles($patterns, 'src/');
    }

    private function checkOutputEscaping(): void
    {
        echo "🚫 Checking output escaping...\n";
        
        $patterns = [
            '/esc_html\s*\(/' => 'Good: esc_html usage',
            '/esc_attr\s*\(/' => 'Good: esc_attr usage',
            '/esc_url\s*\(/' => 'Good: esc_url usage',
        ];

        $this->scanFiles($patterns, 'src/');
    }

    private function checkAuthentication(): void
    {
        echo "🔐 Checking authentication...\n";
        
        $files = glob('src/**/*.php');
        $hasAuthCheck = false;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'get_current_user_id') !== false || 
                strpos($content, 'is_user_logged_in') !== false) {
                $hasAuthCheck = true;
                break;
            }
        }

        if (!$hasAuthCheck) {
            $this->warnings[] = 'No authentication checks found in REST controllers';
        }
    }

    private function checkAuthorization(): void
    {
        echo "👤 Checking authorization...\n";
        
        $files = glob('src/**/*.php');
        $hasCapabilityCheck = false;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'current_user_can') !== false) {
                $hasCapabilityCheck = true;
                break;
            }
        }

        if (!$hasCapabilityCheck) {
            $this->warnings[] = 'No capability checks found in REST controllers';
        }
    }

    private function scanFiles(array $patterns, string $directory): void
    {
        $files = glob($directory . '**/*.php');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNum => $line) {
                foreach ($patterns as $pattern => $message) {
                    if (preg_match($pattern, $line)) {
                        if (strpos($message, 'Good:') === 0) {
                            // This is a positive finding, not an issue
                            continue;
                        }
                        $this->issues[] = "$message in $file:" . ($lineNum + 1);
                    }
                }
            }
        }
    }

    private function reportResults(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "🔍 SECURITY AUDIT RESULTS\n";
        echo str_repeat("=", 50) . "\n\n";

        if (empty($this->issues) && empty($this->warnings)) {
            echo "✅ No security issues found!\n";
            return;
        }

        if (!empty($this->issues)) {
            echo "❌ CRITICAL ISSUES FOUND:\n";
            foreach ($this->issues as $issue) {
                echo "  • $issue\n";
            }
            echo "\n";
        }

        if (!empty($this->warnings)) {
            echo "⚠️  WARNINGS:\n";
            foreach ($this->warnings as $warning) {
                echo "  • $warning\n";
            }
            echo "\n";
        }

        echo "Total issues: " . count($this->issues) . "\n";
        echo "Total warnings: " . count($this->warnings) . "\n";
    }
}

// Run the audit if called directly
if (php_sapi_name() === 'cli') {
    $audit = new SecurityAudit();
    $audit->run();
}
