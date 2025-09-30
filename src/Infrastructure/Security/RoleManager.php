<?php

namespace AIAgent\Infrastructure\Security;

use AIAgent\Support\Logger;

final class RoleManager
{
    private Logger $logger;
    private const ROLE_NAME = 'ai_agent';
    private const ROLE_DISPLAY_NAME = 'AI Agent';

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function createRole(): void
    {
        $capabilities = Capabilities::getDefaultCapabilities();
        
        $result = add_role(
            self::ROLE_NAME,
            self::ROLE_DISPLAY_NAME,
            array_fill_keys($capabilities, true)
        );

        if ($result === null) {
            $this->logger->error('Failed to create AI Agent role - role may already exist');
        } else {
            $this->logger->info('AI Agent role created successfully', ['capabilities' => $capabilities]);
        }
    }

    public function removeRole(): void
    {
        remove_role(self::ROLE_NAME);
        $this->logger->info('AI Agent role removed');
    }

    public function addCapability(string $capability): void
    {
        $role = get_role(self::ROLE_NAME);
        if ($role) {
            $role->add_cap($capability);
            $this->logger->info('Capability added to AI Agent role', ['capability' => $capability]);
        }
    }

    public function removeCapability(string $capability): void
    {
        $role = get_role(self::ROLE_NAME);
        if ($role) {
            $role->remove_cap($capability);
            $this->logger->info('Capability removed from AI Agent role', ['capability' => $capability]);
        }
    }

    public function roleExists(): bool
    {
        return get_role(self::ROLE_NAME) !== null;
    }

    public function getRoleCapabilities(): array
    {
        $role = get_role(self::ROLE_NAME);
        // @phpstan-ignore-next-line WordPress runtime class WP_Role has capabilities
        return $role ? (array) $role->capabilities : [];
    }

    public function createServiceUser(): int
    {
        $username = 'ai_agent_svc';
        $email = 'ai-agent@' . parse_url(home_url(), PHP_URL_HOST);
        
        // Check if user already exists
        $user = get_user_by('login', $username);
        if ($user) {
            $this->logger->info('AI Agent service user already exists', ['user_id' => $user->ID]);
            return $user->ID;
        }

        $user_id = wp_create_user($username, wp_generate_password(), $email);
        
        if (is_wp_error($user_id)) {
            $this->logger->error('Failed to create AI Agent service user', ['error' => $user_id->get_error_message()]);
            throw new \Exception('Failed to create service user: ' . $user_id->get_error_message());
        }

        // Assign the AI Agent role
        $user = new \WP_User($user_id);
        $user->set_role(self::ROLE_NAME);

        $this->logger->info('AI Agent service user created', ['user_id' => $user_id, 'username' => $username]);
        
        return $user_id;
    }

    public function getServiceUserId(): ?int
    {
        $user = get_user_by('login', 'ai_agent_svc');
        // @phpstan-ignore-next-line WP_User available at runtime in WP
        return $user ? (int) $user->ID : null;
    }

    public function ensureServiceUser(): int
    {
        $user_id = $this->getServiceUserId();
        if (!$user_id) {
            $user_id = $this->createServiceUser();
        }
        return $user_id;
    }
}
