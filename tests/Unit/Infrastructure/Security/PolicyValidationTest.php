<?php

namespace AIAgent\Tests\Unit\Infrastructure\Security;

use AIAgent\REST\Controllers\PolicyController;
use AIAgent\Support\Logger;
use PHPUnit\Framework\TestCase;

final class PolicyValidationTest extends TestCase
{
    private PolicyController $controller;

    protected function setUp(): void
    {
        $this->controller = new PolicyController(new Logger());
    }

    public function testValidatePolicyDataPassesForAllowedKeys(): void
    {
        $policy = [
            'rate_limits' => ['per_hour' => 10, 'per_day' => 100, 'per_ip_hour' => 20],
            'time_windows' => ['allowed_hours' => [9,10], 'allowed_days' => [1,2]],
            'content_restrictions' => ['blocked_terms' => ['spam']],
            'entity_rules' => [],
            'approval_workflows' => [],
        ];
        $this->assertTrue($this->controller->validate_policy_data($policy));
    }

    public function testValidatePolicyDataFailsForUnknownKey(): void
    {
        $policy = [ 'unknown_key' => true ];
        $this->assertFalse($this->controller->validate_policy_data($policy));
    }

    public function testValidatePolicyDataFailsForNonArray(): void
    {
        $this->assertFalse($this->controller->validate_policy_data('not-an-array'));
    }
}


