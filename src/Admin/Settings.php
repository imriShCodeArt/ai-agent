<?php

namespace AIAgent\Admin;

use AIAgent\Support\Logger;

final class Settings
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function registerSettings(): void
    {
        // Register settings
        register_setting('ai_agent_settings', 'ai_agent_mode');
        register_setting('ai_agent_settings', 'ai_agent_allowed_post_types');
        register_setting('ai_agent_settings', 'ai_agent_rate_limit_hourly');
        register_setting('ai_agent_settings', 'ai_agent_rate_limit_daily');
        register_setting('ai_agent_settings', 'ai_agent_require_approval');
        register_setting('ai_agent_settings', 'ai_agent_auto_publish');
        register_setting('ai_agent_settings', 'ai_agent_blocked_terms');
        register_setting('ai_agent_settings', 'ai_agent_allowed_hours');
        register_setting('ai_agent_settings', 'ai_agent_woocommerce_enabled');
		// OpenAI settings
		register_setting('ai_agent_settings', 'ai_agent_openai_api_key');
		register_setting('ai_agent_settings', 'ai_agent_openai_model');
        register_setting('ai_agent_settings', 'ai_agent_oauth2_client_id');
        register_setting('ai_agent_settings', 'ai_agent_oauth2_client_secret');
        register_setting('ai_agent_settings', 'ai_agent_oauth2_authorization_url');
        register_setting('ai_agent_settings', 'ai_agent_oauth2_token_url');
        register_setting('ai_agent_settings', 'ai_agent_oauth2_user_info_url');
        register_setting('ai_agent_settings', 'ai_agent_oauth2_scopes');

        $this->logger->info('AI Agent settings registered');
    }

    public function addSettingsPage(): void
    {
        // Settings page is now handled by AdminMenu
        // This method is kept for compatibility but does nothing
    }

    public function renderSettingsPage(): void
    {
        if (isset($_POST['submit'])) {
            $this->handleSettingsSave();
        }

        $settings = $this->getSettings();
        ?>
        <div class="wrap">
            <h1>AI Agent Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('ai_agent_settings', 'ai_agent_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ai_agent_mode">Operation Mode</label>
                        </th>
                        <td>
                            <select name="ai_agent_mode" id="ai_agent_mode">
                                <option value="suggest" <?php selected($settings['mode'], 'suggest'); ?>>Suggest Only</option>
                                <option value="review" <?php selected($settings['mode'], 'review'); ?>>Review Required</option>
                                <option value="autonomous" <?php selected($settings['mode'], 'autonomous'); ?>>Autonomous</option>
                            </select>
                            <p class="description">Controls how the AI Agent operates. Suggest mode only proposes changes, Review mode requires human approval, Autonomous mode executes changes directly.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai_agent_allowed_post_types">Allowed Post Types</label>
                        </th>
                        <td>
                            <?php
                            $postTypes = get_post_types(['public' => true], 'objects');
                            foreach ($postTypes as $postType) {
                                $checked = in_array($postType->name, $settings['allowed_post_types'], true) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="ai_agent_allowed_post_types[]" value="' . esc_attr($postType->name) . '" ' . $checked . '> ' . esc_html($postType->label) . '</label><br>';
                            }
                            ?>
                            <p class="description">Select which post types the AI Agent can work with.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai_agent_rate_limit_hourly">Hourly Rate Limit</label>
                        </th>
                        <td>
                            <input type="number" name="ai_agent_rate_limit_hourly" id="ai_agent_rate_limit_hourly" value="<?php echo esc_attr($settings['rate_limit_hourly']); ?>" min="1" max="1000">
                            <p class="description">Maximum number of operations per hour.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai_agent_rate_limit_daily">Daily Rate Limit</label>
                        </th>
                        <td>
                            <input type="number" name="ai_agent_rate_limit_daily" id="ai_agent_rate_limit_daily" value="<?php echo esc_attr($settings['rate_limit_daily']); ?>" min="1" max="10000">
                            <p class="description">Maximum number of operations per day.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai_agent_require_approval">Require Approval</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="ai_agent_require_approval" id="ai_agent_require_approval" value="1" <?php checked($settings['require_approval']); ?>>
                                All changes require human approval before being applied
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai_agent_auto_publish">Auto Publish</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="ai_agent_auto_publish" id="ai_agent_auto_publish" value="1" <?php checked($settings['auto_publish']); ?>>
                                Allow AI Agent to publish content directly (not recommended for production)
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai_agent_blocked_terms">Blocked Terms</label>
                        </th>
                        <td>
                            <textarea name="ai_agent_blocked_terms" id="ai_agent_blocked_terms" rows="3" cols="50"><?php echo esc_textarea(implode(', ', $settings['blocked_terms'])); ?></textarea>
                            <p class="description">Comma-separated list of terms that should not appear in AI-generated content.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai_agent_allowed_hours">Allowed Hours</label>
                        </th>
                        <td>
                            <select name="ai_agent_allowed_hours[]" id="ai_agent_allowed_hours" multiple size="8">
                                <?php
                                for ($hour = 0; $hour < 24; $hour++) {
                                    $selected = in_array($hour, $settings['allowed_hours'], true) ? 'selected' : '';
                                    $time = sprintf('%02d:00', $hour);
                                    echo '<option value="' . $hour . '" ' . $selected . '>' . $time . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Select hours when AI Agent operations are allowed (hold Ctrl/Cmd to select multiple).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai_agent_woocommerce_enabled">WooCommerce Integration</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="ai_agent_woocommerce_enabled" id="ai_agent_woocommerce_enabled" value="1" <?php checked($settings['woocommerce_enabled']); ?>>
                                Enable WooCommerce product management
                            </label>
                            <p class="description">Allow AI Agent to create and manage WooCommerce products (requires WooCommerce plugin).</p>
                        </td>
                    </tr>
                </table>

				<h2>OpenAI</h2>
				<table class="form-table">
					<tr>
						<th scope="row">API Key</th>
						<td>
							<input type="password" name="ai_agent_openai_api_key" value="<?php echo esc_attr(get_option('ai_agent_openai_api_key', '')); ?>" autocomplete="off" />
							<p class="description">Stored in the WordPress options table. Required for OpenAI features.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Model</th>
						<td>
							<select name="ai_agent_openai_model">
							<?php $currentModel = (string) get_option('ai_agent_openai_model', 'gpt-5-mini'); ?>
								<option value="gpt-5-mini" <?php selected($currentModel, 'gpt-5-mini'); ?>>gpt-5-mini</option>
								<option value="gpt-5" <?php selected($currentModel, 'gpt-5'); ?>>gpt-5</option>
								<option value="gpt-5.1-mini" <?php selected($currentModel, 'gpt-5.1-mini'); ?>>gpt-5.1-mini</option>
								<option value="gpt-5.1" <?php selected($currentModel, 'gpt-5.1'); ?>>gpt-5.1</option>
								<option value="gpt-4o-mini" <?php selected($currentModel, 'gpt-4o-mini'); ?>>gpt-4o-mini</option>
								<option value="gpt-4o" <?php selected($currentModel, 'gpt-4o'); ?>>gpt-4o</option>
								<option value="gpt-4.1-mini" <?php selected($currentModel, 'gpt-4.1-mini'); ?>>gpt-4.1-mini</option>
								<option value="gpt-4.1" <?php selected($currentModel, 'gpt-4.1'); ?>>gpt-4.1</option>
							</select>
							<p class="description">Default model for text generation and summaries.</p>
						</td>
					</tr>
				</table>

                <h2>Integrations</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">WooCommerce</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ai_agent_woocommerce_enabled" value="1" <?php checked($settings['woocommerce_enabled']); ?>> Enable WooCommerce features
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">OAuth2</th>
                        <td>
                            <p><label>Client ID <input type="text" name="ai_agent_oauth2_client_id" value="<?php echo esc_attr(get_option('ai_agent_oauth2_client_id', '')); ?>" /></label></p>
                            <p><label>Client Secret <input type="password" name="ai_agent_oauth2_client_secret" value="<?php echo esc_attr(get_option('ai_agent_oauth2_client_secret', '')); ?>" /></label></p>
                            <p><label>Authorization URL <input type="url" name="ai_agent_oauth2_authorization_url" value="<?php echo esc_attr(get_option('ai_agent_oauth2_authorization_url', '')); ?>" /></label></p>
                            <p><label>Token URL <input type="url" name="ai_agent_oauth2_token_url" value="<?php echo esc_attr(get_option('ai_agent_oauth2_token_url', '')); ?>" /></label></p>
                            <p><label>User Info URL <input type="url" name="ai_agent_oauth2_user_info_url" value="<?php echo esc_attr(get_option('ai_agent_oauth2_user_info_url', '')); ?>" /></label></p>
                            <p><label>Scopes (comma separated) <input type="text" name="ai_agent_oauth2_scopes" value="<?php echo esc_attr(implode(',', (array) get_option('ai_agent_oauth2_scopes', ['read','write']))); ?>" /></label></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>

            <hr>

            <h2>System Status</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Database Tables</th>
                    <td>
                        <?php
                        global $wpdb;
                        $tables = [
                            'ai_agent_actions',
                            'ai_agent_sessions',
                            'ai_agent_policies',
                        ];
                        
                        foreach ($tables as $table) {
                            $tableName = $wpdb->prefix . $table;
                            $exists = $wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName;
                            $status = $exists ? '✅' : '❌';
                            echo '<p>' . $status . ' ' . $table . '</p>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">AI Agent Role</th>
                    <td>
                        <?php
                        $role = get_role('ai_agent');
                        echo $role ? '✅ Created' : '❌ Not found';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Service User</th>
                    <td>
                        <?php
                        $user = get_user_by('login', 'ai_agent_svc');
                        // @phpstan-ignore-next-line WP_User available at runtime in WP
                        echo $user ? '✅ Created (ID: ' . (int) $user->ID . ')' : '❌ Not found';
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    private function handleSettingsSave(): void
    {
        if (!wp_verify_nonce($_POST['ai_agent_nonce'], 'ai_agent_settings')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $settings = [
            'mode' => sanitize_text_field($_POST['ai_agent_mode']),
            'allowed_post_types' => array_map('sanitize_text_field', $_POST['ai_agent_allowed_post_types'] ?? []),
            'rate_limit_hourly' => (int) $_POST['ai_agent_rate_limit_hourly'],
            'rate_limit_daily' => (int) $_POST['ai_agent_rate_limit_daily'],
            'require_approval' => isset($_POST['ai_agent_require_approval']),
            'auto_publish' => isset($_POST['ai_agent_auto_publish']),
            'blocked_terms' => array_filter(array_map('trim', explode(',', sanitize_textarea_field($_POST['ai_agent_blocked_terms'])))),
            'allowed_hours' => array_map('intval', $_POST['ai_agent_allowed_hours'] ?? []),
            'woocommerce_enabled' => isset($_POST['ai_agent_woocommerce_enabled']),
        ];

        // Additional integration options
        update_option('ai_agent_oauth2_client_id', sanitize_text_field((string) ($_POST['ai_agent_oauth2_client_id'] ?? '')));
        update_option('ai_agent_oauth2_client_secret', sanitize_text_field((string) ($_POST['ai_agent_oauth2_client_secret'] ?? '')));
        update_option('ai_agent_oauth2_authorization_url', esc_url_raw((string) ($_POST['ai_agent_oauth2_authorization_url'] ?? '')));
        update_option('ai_agent_oauth2_token_url', esc_url_raw((string) ($_POST['ai_agent_oauth2_token_url'] ?? '')));
        update_option('ai_agent_oauth2_user_info_url', esc_url_raw((string) ($_POST['ai_agent_oauth2_user_info_url'] ?? '')));
        $scopes = array_filter(array_map('trim', explode(',', (string) ($_POST['ai_agent_oauth2_scopes'] ?? 'read,write'))));
        update_option('ai_agent_oauth2_scopes', $scopes);

		// OpenAI options
		update_option('ai_agent_openai_api_key', sanitize_text_field((string) ($_POST['ai_agent_openai_api_key'] ?? '')));
		update_option('ai_agent_openai_model', sanitize_text_field((string) ($_POST['ai_agent_openai_model'] ?? 'gpt-5-mini')));

        foreach ($settings as $key => $value) {
            update_option('ai_agent_' . $key, $value);
        }

        $this->logger->info('AI Agent settings updated', $settings);
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return [
            'mode' => get_option('ai_agent_mode', 'suggest'),
            'allowed_post_types' => get_option('ai_agent_allowed_post_types', ['post', 'page']),
            'rate_limit_hourly' => get_option('ai_agent_rate_limit_hourly', 20),
            'rate_limit_daily' => get_option('ai_agent_rate_limit_daily', 100),
            'require_approval' => get_option('ai_agent_require_approval', true),
            'auto_publish' => get_option('ai_agent_auto_publish', false),
            'blocked_terms' => get_option('ai_agent_blocked_terms', ['spam', 'scam']),
            'allowed_hours' => get_option('ai_agent_allowed_hours', [9, 10, 11, 12, 13, 14, 15, 16, 17]),
            'woocommerce_enabled' => get_option('ai_agent_woocommerce_enabled', false),
        ];
    }
}
