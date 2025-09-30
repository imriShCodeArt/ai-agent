<?php

namespace AIAgent\Tests\Unit\Infrastructure\Security;

use AIAgent\Infrastructure\Security\EnhancedPolicy;
use AIAgent\Infrastructure\Security\PolicyManager;
use AIAgent\Support\Logger;
use PHPUnit\Framework\TestCase;

final class PolicyDiffTest extends TestCase
{
    public function testPolicyDiffShowsAddedRemovedModified(): void
    {
        $logger = new Logger();
        $policy = new EnhancedPolicy($logger);
        $manager = new PolicyManager($logger, $policy);

        $tool = 'posts.update';
        $v1 = [ 'rate_limits' => ['per_hour' => 10], 'entity_rules' => [] ];
        $v2 = [ 'rate_limits' => ['per_hour' => 20], 'entity_rules' => [], 'time_windows' => ['allowed_hours' => [9]] ];

        $policy->updatePolicy($tool, $v1);
        $policy->createPolicyVersion($tool, $v1, 1);
        $policy->createPolicyVersion($tool, $v2, 1);

        $versions = $policy->getPolicyVersions($tool);
        $this->assertGreaterThanOrEqual(2, count($versions));

        $v1ver = $versions[0]['version'];
        $v2ver = $versions[1]['version'];

        $diff = $manager->getPolicyDiff($tool, $v1ver, $v2ver);
        $this->assertTrue($diff['success']);
        $this->assertArrayHasKey('diff', $diff);
        $this->assertArrayHasKey('modified', $diff['diff']);
    }
}


