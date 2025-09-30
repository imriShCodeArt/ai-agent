<?php

namespace AIAgent\Admin;

final class AdminMenu
{
    public function addMenuPage(): void
    {
        add_menu_page(
            'AI Agent',
            'AI Agent',
            'manage_options',
            'ai-agent',
            [$this, 'renderDashboardPage'],
            'dashicons-robot',
            30
        );

        add_submenu_page(
            'ai-agent',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ai-agent',
            [$this, 'renderDashboardPage']
        );

        add_submenu_page(
            'ai-agent',
            'Settings',
            'Settings',
            'manage_options',
            'ai-agent-settings',
            [$this, 'renderSettingsPage']
        );

		add_submenu_page(
			'ai-agent',
			'Tools',
			'Tools',
			'ai_agent_execute_tool',
			'ai-agent-tools',
			[$this, 'renderToolsPage']
		);

		add_submenu_page(
			'ai-agent',
			'Policies',
			'Policies',
			'ai_agent_manage_policies',
			'ai-agent-policies',
			[$this, 'renderPoliciesPage']
		);

		add_submenu_page(
			'ai-agent',
			'Reviews',
			'Reviews',
			'ai_agent_approve_changes',
			'ai-agent-reviews',
			[$this, 'renderReviewsPage']
		);

        add_submenu_page(
            'ai-agent',
            'Audit Logs',
            'Audit Logs',
            'ai_agent_view_logs',
            'ai-agent-logs',
            [$this, 'renderLogsPage']
        );
    }

    public function renderDashboardPage(): void
    {
        global $wpdb;
        
        // Get statistics
        $actionsTable = $wpdb->prefix . 'ai_agent_actions';
        $totalActions = $wpdb->get_var("SELECT COUNT(*) FROM $actionsTable");
        $recentActions = $wpdb->get_results(
            "SELECT * FROM $actionsTable ORDER BY ts DESC LIMIT 10",
            ARRAY_A
        );
        ?>
        <div class="wrap">
            <h1>AI Agent Dashboard</h1>
            
            <div class="ai-agent-dashboard">
                <div class="ai-agent-stats">
                    <div class="ai-agent-stat-box">
                        <h3>Total Actions</h3>
                        <p class="ai-agent-stat-number"><?php echo esc_html($totalActions); ?></p>
                    </div>
                    
                    <div class="ai-agent-stat-box">
                        <h3>Mode</h3>
                        <p class="ai-agent-stat-text"><?php echo esc_html(ucfirst(get_option('ai_agent_mode', 'suggest'))); ?></p>
                    </div>
                    
                    <div class="ai-agent-stat-box">
                        <h3>Status</h3>
                        <p class="ai-agent-stat-text"><?php echo $this->getSystemStatus(); ?></p>
                    </div>
                </div>

                <div class="ai-agent-recent-actions">
                    <h2>Recent Actions</h2>
                    <?php if (empty($recentActions)): ?>
                        <p>No actions recorded yet.</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Tool</th>
                                    <th>User</th>
                                    <th>Entity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActions as $action): ?>
                                    <tr>
                                        <td><?php echo esc_html($action['ts']); ?></td>
                                        <td><?php echo esc_html($action['tool']); ?></td>
                                        <td><?php echo esc_html(get_user_by('id', $action['user_id'])->display_name ?? 'Unknown'); ?></td>
                                        <td><?php echo esc_html($action['entity_type'] . ' #' . $action['entity_id']); ?></td>
                                        <td>
                                            <span class="ai-agent-status ai-agent-status-<?php echo esc_attr($action['status']); ?>">
                                                <?php echo esc_html(ucfirst($action['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <style>
        .ai-agent-dashboard {
            margin-top: 20px;
        }
        
        .ai-agent-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .ai-agent-stat-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            flex: 1;
            text-align: center;
        }
        
        .ai-agent-stat-box h3 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        
        .ai-agent-stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #0073aa;
            margin: 0;
        }
        
        .ai-agent-stat-text {
            font-size: 1.2em;
            color: #666;
            margin: 0;
        }
        
        .ai-agent-status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .ai-agent-status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .ai-agent-status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .ai-agent-status-pending {
            background: #fff3cd;
            color: #856404;
        }
        </style>
        <?php
    }

    public function renderSettingsPage(): void
    {
        // Redirect to WordPress settings page
        wp_redirect(admin_url('options-general.php?page=ai-agent-settings'));
        exit;
    }

    public function renderLogsPage(): void
    {
        if (!current_user_can('ai_agent_view_logs')) {
            wp_die('Insufficient permissions');
        }

        global $wpdb;
        $actionsTable = $wpdb->prefix . 'ai_agent_actions';
        
        // Get filters
        $filters = [
            'user_id' => $_GET['user_id'] ?? '',
            'tool' => $_GET['tool'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];
        
        // Build query
        $where = ['1=1'];
        $values = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = (int) $filters['user_id'];
        }
        
        if (!empty($filters['tool'])) {
            $where[] = 'tool = %s';
            $values[] = $filters['tool'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $values[] = $filters['status'];
        }
        
        $whereClause = implode(' AND ', $where);
        $sql = "SELECT * FROM $actionsTable WHERE $whereClause ORDER BY ts DESC LIMIT 50";
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        $logs = $wpdb->get_results($sql, ARRAY_A);
        ?>
        <div class="wrap">
            <h1>Audit Logs</h1>
            
            <div class="ai-agent-filters">
                <form method="get">
                    <input type="hidden" name="page" value="ai-agent-logs">
                    
                    <select name="user_id">
                        <option value="">All Users</option>
                        <?php
                        $users = get_users();
                        foreach ($users as $user) {
                            $selected = $filters['user_id'] == $user->ID ? 'selected' : '';
                            echo '<option value="' . $user->ID . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <select name="tool">
                        <option value="">All Tools</option>
                        <option value="posts.create" <?php selected($filters['tool'], 'posts.create'); ?>>Create Post</option>
                        <option value="posts.update" <?php selected($filters['tool'], 'posts.update'); ?>>Update Post</option>
                        <option value="posts.delete" <?php selected($filters['tool'], 'posts.delete'); ?>>Delete Post</option>
                        <option value="chat.request" <?php selected($filters['tool'], 'chat.request'); ?>>Chat Request</option>
                    </select>
                    
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="success" <?php selected($filters['status'], 'success'); ?>>Success</option>
                        <option value="error" <?php selected($filters['status'], 'error'); ?>>Error</option>
                        <option value="pending" <?php selected($filters['status'], 'pending'); ?>>Pending</option>
                    </select>
                    
                    <input type="submit" class="button" value="Filter">
                </form>
            </div>
            
            <?php if (empty($logs)): ?>
                <p>No logs found matching your criteria.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Time</th>
                            <th>User</th>
                            <th>Tool</th>
                            <th>Entity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log['id']); ?></td>
                                <td><?php echo esc_html($log['ts']); ?></td>
                                <td><?php echo esc_html(get_user_by('id', $log['user_id'])->display_name ?? 'Unknown'); ?></td>
                                <td><?php echo esc_html($log['tool']); ?></td>
                                <td><?php echo esc_html($log['entity_type'] . ' #' . $log['entity_id']); ?></td>
                                <td>
                                    <span class="ai-agent-status ai-agent-status-<?php echo esc_attr($log['status']); ?>">
                                        <?php echo esc_html(ucfirst($log['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=ai-agent-logs&action=view&id=' . $log['id']); ?>" class="button button-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

	public function renderToolsPage(): void
	{
		if (!current_user_can('ai_agent_execute_tool')) { wp_die('Insufficient permissions'); }
		?>
		<div class="wrap">
			<h1>AI Agent Tools</h1>
			<p>Use this utility to dry-run or execute registered tools for testing.</p>
			<form method="post">
				<?php wp_nonce_field('ai_agent_tools', 'ai_agent_tools_nonce'); ?>
				<p><label>Tool Name <input type="text" name="tool" required></label></p>
				<p><label>Payload (JSON) <textarea name="payload" rows="6" cols="80"></textarea></label></p>
				<p><button class="button button-primary" name="action" value="dry_run">Dry Run</button>
				<button class="button" name="action" value="execute">Execute</button></p>
			</form>
		</div>
		<?php
	}

	public function renderPoliciesPage(): void
	{
		if (!current_user_can('ai_agent_manage_policies')) { wp_die('Insufficient permissions'); }
		?>
		<div class="wrap">
			<h1>AI Agent Policies</h1>
			<p>View and manage policy documents governing tool execution.</p>
			<p>Policy management UI is minimal for Phase 4; use CLI or DB for advanced edits.</p>
		</div>
		<?php
	}

	public function renderReviewsPage(): void
	{
		if (!current_user_can('ai_agent_approve_changes')) { wp_die('Insufficient permissions'); }
		global $wpdb;
		$table = $wpdb->prefix . 'ai_agent_actions';
		$rows = $wpdb->get_results("SELECT * FROM $table WHERE status = 'pending' ORDER BY ts DESC LIMIT 50", ARRAY_A);
		?>
		<div class="wrap">
			<h1>AI Agent Reviews</h1>
			<?php if (empty($rows)): ?>
				<p>No pending reviews.</p>
			<?php else: ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>ID</th>
							<th>Time</th>
							<th>User</th>
							<th>Tool</th>
							<th>Entity</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $r): ?>
						<tr>
							<td><?php echo esc_html($r['id']); ?></td>
							<td><?php echo esc_html($r['ts']); ?></td>
							<td><?php echo esc_html(get_user_by('id', $r['user_id'])->display_name ?? 'Unknown'); ?></td>
							<td><?php echo esc_html($r['tool']); ?></td>
							<td><?php echo esc_html($r['entity_type'] . ' #' . $r['entity_id']); ?></td>
							<td>
								<button class="button button-primary" data-id="<?php echo esc_attr((string) $r['id']); ?>" onclick="aiAgentApprove(this)">Approve</button>
								<button class="button" data-id="<?php echo esc_attr((string) $r['id']); ?>" onclick="aiAgentReject(this)">Reject</button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<script>
		async function aiAgentApprove(btn){
		  const id = btn.getAttribute('data-id');
		  await fetch('<?php echo esc_url_raw( rest_url('ai-agent/v1/reviews/') ); ?>'+id+'/approve', { method: 'POST', credentials: 'include' });
		  location.reload();
		}
		async function aiAgentReject(btn){
		  const id = btn.getAttribute('data-id');
		  await fetch('<?php echo esc_url_raw( rest_url('ai-agent/v1/reviews/') ); ?>'+id+'/reject', { method: 'POST', credentials: 'include' });
		  location.reload();
		}
		</script>
		<?php
	}

    private function getSystemStatus(): string
    {
        $role = get_role('ai_agent');
        $user = get_user_by('login', 'ai_agent_svc');
        
        if ($role && $user) {
            return '<span style="color: green;">✅ Active</span>';
        }
        
        return '<span style="color: red;">❌ Inactive</span>';
    }
}