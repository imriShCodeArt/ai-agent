<?php

namespace AIAgent\Infrastructure\Security;

use AIAgent\Support\Logger;

final class PolicyManager
{
    private Logger $logger;
    private EnhancedPolicy $policy;

    public function __construct(Logger $logger, EnhancedPolicy $policy)
    {
        $this->logger = $logger;
        $this->policy = $policy;
    }

    public function createPolicy(string $tool, array $policyData, int $userId = null): array
    {
        try {
            $policyId = $this->policy->createPolicyVersion($tool, $policyData, $userId);
            
            // Update the active policy
            $this->policy->updatePolicy($tool, $policyData);
            
            $this->logger->info('Policy created successfully', [
                'tool' => $tool,
                'policy_id' => $policyId,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'policy_id' => $policyId,
                'version' => $this->policy->generateVersionNumber($tool),
                'message' => 'Policy created successfully',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create policy', [
                'tool' => $tool,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function updatePolicy(string $tool, array $policyData, int $userId = null): array
    {
        try {
            $policyId = $this->policy->createPolicyVersion($tool, $policyData, $userId);
            
            // Update the active policy
            $this->policy->updatePolicy($tool, $policyData);
            
            $this->logger->info('Policy updated successfully', [
                'tool' => $tool,
                'policy_id' => $policyId,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'policy_id' => $policyId,
                'version' => $this->policy->generateVersionNumber($tool),
                'message' => 'Policy updated successfully',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to update policy', [
                'tool' => $tool,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getPolicyVersions(string $tool): array
    {
        try {
            $versions = $this->policy->getPolicyVersions($tool);
            
            return [
                'success' => true,
                'versions' => $versions,
                'count' => count($versions),
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get policy versions', [
                'tool' => $tool,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getPolicyDiff(string $tool, string $version1, string $version2): array
    {
        try {
            $versions = $this->policy->getPolicyVersions($tool);
            
            $policy1 = null;
            $policy2 = null;
            
            foreach ($versions as $version) {
                if ($version['version'] === $version1) {
                    $policy1 = $version['policy'];
                }
                if ($version['version'] === $version2) {
                    $policy2 = $version['policy'];
                }
            }
            
            if (!$policy1 || !$policy2) {
                return [
                    'success' => false,
                    'error' => 'One or both policy versions not found',
                ];
            }
            
            $diff = $this->calculatePolicyDiff($policy1, $policy2);
            
            return [
                'success' => true,
                'diff' => $diff,
                'version1' => $version1,
                'version2' => $version2,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get policy diff', [
                'tool' => $tool,
                'version1' => $version1,
                'version2' => $version2,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function rollbackPolicy(string $tool, string $version): array
    {
        try {
            $versions = $this->policy->getPolicyVersions($tool);
            
            $targetVersion = null;
            foreach ($versions as $v) {
                if ($v['version'] === $version) {
                    $targetVersion = $v;
                    break;
                }
            }
            
            if (!$targetVersion) {
                return [
                    'success' => false,
                    'error' => 'Policy version not found',
                ];
            }
            
            // Update the active policy
            $this->policy->updatePolicy($tool, $targetVersion['policy']);
            
            $this->logger->info('Policy rolled back successfully', [
                'tool' => $tool,
                'version' => $version,
            ]);

            return [
                'success' => true,
                'version' => $version,
                'message' => 'Policy rolled back successfully',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to rollback policy', [
                'tool' => $tool,
                'version' => $version,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function testPolicy(string $tool, array $testData): array
    {
        try {
            $result = $this->policy->isAllowed($tool, $testData['entity_id'] ?? null, $testData['fields'] ?? []);
            
            return [
                'success' => true,
                'result' => $result,
                'test_data' => $testData,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to test policy', [
                'tool' => $tool,
                'test_data' => $testData,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function calculatePolicyDiff(array $policy1, array $policy2): array
    {
        $diff = [
            'added' => [],
            'removed' => [],
            'modified' => [],
        ];

        // Compare all keys
        $allKeys = array_unique(array_merge(array_keys($policy1), array_keys($policy2)));

        foreach ($allKeys as $key) {
            $inPolicy1 = array_key_exists($key, $policy1);
            $inPolicy2 = array_key_exists($key, $policy2);

            if ($inPolicy1 && !$inPolicy2) {
                $diff['removed'][$key] = $policy1[$key];
            } elseif (!$inPolicy1 && $inPolicy2) {
                $diff['added'][$key] = $policy2[$key];
            } elseif ($policy1[$key] !== $policy2[$key]) {
                $diff['modified'][$key] = [
                    'old' => $policy1[$key],
                    'new' => $policy2[$key],
                ];
            }
        }

        return $diff;
    }
}
