<?php

namespace AIAgent\Tests\Unit\Infrastructure\Security;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Security\EnhancedPolicy;
use AIAgent\Support\Logger;

final class EnhancedPolicyCommerceTest extends TestCase
{
    public function testDenyHighPriceChangeRequiresApproval(): void
    {
        $policy = new EnhancedPolicy(new Logger());
        $result = $policy->isAllowed('products.update', null, ['price' => 1000000]);
        $this->assertIsArray($result);
        $this->assertFalse($result['allowed']);
    }
}

<?php

namespace AIAgent\Tests\Unit\Infrastructure\Security;

use AIAgent\Infrastructure\Security\EnhancedPolicy;
use AIAgent\Support\Logger;
use PHPUnit\Framework\TestCase;

final class EnhancedPolicyCommerceTest extends TestCase
{
    public function testPriceThresholdTriggersApproval(): void
    {
        $policy = new EnhancedPolicy(new Logger());
        $fields = ['price' => 1000];
        $result = $policy->isAllowed('products.update', 1, $fields);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed', $result);
        $this->assertFalse($result['allowed']);
        $this->assertSame('approval_required', $result['reason']);
    }
}


