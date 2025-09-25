# WP AI Agent — Plugin Definition & Architecture

## 1) Goal & Scope
Embed a controllable AI agent into a WordPress site that can **propose** and/or **perform CRUD** operations on site entities (Posts/Pages/CPTs, Media, Users—optional, WooCommerce Products/Categories/Attributes) under strict permissions, audit logs, and review workflows. Supports three modes:

1. **Suggest** (read-only): agent proposes diffs, humans apply.
2. **Review** (human-in-the-loop): agent prepares changes as drafts/revisions; editors approve.
3. **Autonomous** (guardrailed): agent executes within limits and policies.

## 2) High-Level Architecture
- **Frontend Chat Widget**: Shortcode + Block (`[ai_agent_chat]`) rendering a chat panel (React) that talks to WP REST routes with a **REST nonce** for logged-in users; public mode goes via a backend proxy with rate limiting.
- **Plugin Backend**: Registers:
  - Custom REST API: `/wp-json/ai-agent/v1/*`
  - Admin UI (Settings, Policies, Queues, Logs, Diff Review)
  - Roles/Capabilities (least-privilege service user)
  - Action Scheduler for async jobs
  - Webhooks (optional) for external LLM orchestration
- **Agent Orchestrator**: External LLM (OpenAI, etc.) with **function/tool-calling**. Tools map 1:1 to REST endpoints. All write actions are **capability-checked** in WP.
- **Data Stores**: Custom tables for actions, sessions, policies; use native **revisions** for content changes.

### Data Flow (typical)
1. User asks agent → frontend sends to `/chat` → orchestrator (server-side) calls LLM.
2. LLM selects a "tool" (e.g., `update_post`) → plugin REST validates policy & caps → executes safely → returns result + draft/revision ID.
3. For Review mode, an Editor sees diffs in Admin → Approve/Reject → applied via `wp_update_post()` or `wc_update_product()`.

## 3) Security & Governance
- **Identity**: Dedicated WP user `ai_agent_svc` with a custom role `ai_agent` and granular caps: `read`, `edit_posts`, `edit_others_posts` (optional), `publish_posts` (optional), `delete_posts` (optional), `manage_product` (WC), etc. Disable `edit_users` by default.
- **Auth**:
  - Browser → WP: REST Nonce (`wp_rest`).
  - Server → Server (or external orchestrator): **Application Passwords**, OAuth2 (via plugin), or signed HMAC headers with time-based nonce.
- **Policy Engine** (admin-defined):
  - Allowed post types & taxonomies
  - Max items per hour/day per operation
  - Time windows (e.g., no publish at night)
  - Disallowed terms/regexes, URL allow/deny lists
  - Approval rules (e.g., products require approval, blog drafts can be auto-published)
- **Review & Rollback**:
  - All edits as **revisions** unless explicitly allowed to publish
  - Side-by-side diffs (uses `wp_text_diff`)
  - One-click rollback to prior revision
- **Auditing**:
  - Custom table `wp_ai_agent_actions` logging actor, tool, payload (redacted), before/after hashes, status
  - Immutable append-only option
- **Rate Limiting**: IP + user + tool quotas; exponential backoff; delete ops require **two-man rule** (optional).

## 4) REST API Surface (Server Tools)
Base: `/wp-json/ai-agent/v1`

- `POST /chat` — runs prompt through orchestrator; returns assistant messages & suggested actions.
- `POST /dry-run` — validate a tool call and produce **diff preview** & policy verdicts.
- `GET /entities` — search entities: types, fields, status, pagination.
- `POST /posts.create` — create post/page/CPT (draft by default).
- `POST /posts.update` — update by ID (creates revision unless auto mode allowed).
- `POST /posts.delete` — trash by ID (hard delete behind policy flag).
- `POST /media.upload` — sideload via URL or file; validate mime/size.
- `POST /terms.update` — create/update taxonomies (whitelisted).
- `POST /wc.products.*` — `create|update|delete|get` (if Woo is active).
- `POST /workflow.approve` — approve a queued change (Admin/Editor only).
- `GET /logs` — paginated action logs with filters.

All endpoints enforce:
- Capability checks mapped to the entity & action
- Policy checks (limits, regex, schedules)
- CSRF/nonce or HMAC auth
- JSON Schema validation

## 5) LLM Tool Schema (examples)
**Tool: posts.update**
```json
{
  "name": "posts.update",
  "description": "Update a post/page/CPT by ID; creates a revision unless policy allows publish.",
  "parameters": {
    "type": "object",
    "properties": {
      "id": { "type": "integer" },
      "fields": {
        "type": "object",
        "properties": {
          "post_title": { "type": "string" },
          "post_content": { "type": "string" },
          "post_excerpt": { "type": "string" },
          "post_status": { "type": "string", "enum": ["draft", "pending", "publish"] },
          "meta": { "type": "object", "additionalProperties": {"type":"string"} },
          "terms": { "type": "object", "additionalProperties": {"type":"array","items":{"type":"integer"}} }
        },
        "additionalProperties": false
      }
    },
    "required": ["id", "fields"],
    "additionalProperties": false
  }
}
```

**Tool: wc.products.create**
```json
{
  "name": "wc.products.create",
  "description": "Create a WooCommerce product as draft.",
  "parameters": {
    "type": "object",
    "properties": {
      "name": {"type": "string"},
      "type": {"type": "string", "enum": ["simple","variable","grouped"]},
      "regular_price": {"type": "string"},
      "sale_price": {"type": "string"},
      "description": {"type": "string"},
      "short_description": {"type": "string"},
      "categories": {"type": "array", "items": {"type": "integer"}},
      "images": {"type": "array", "items": {"type": "string", "format": "uri"}}
    },
    "required": ["name","type"],
    "additionalProperties": false
  }
}
```

## 6) Plugin Settings & Policies
- **Modes**: Suggest / Review / Autonomous (per entity type)
- **Entity Access**: checkboxes for post types, Woo entities
- **Publishing Rules**: allowed statuses per type; “always draft” toggle
- **Limits**: per-tool/hour, daily caps, global off-switch
- **Safety**: blocked terms regex, outbound domain allowlist for images/links
- **Auth**: rotate keys, regenerate application password for service user
- **Webhooks**: outbound URLs for action events; retry policies

## 7) Database & Data Model
- `wp_ai_agent_actions` (PK id, ts, user_id, tool, entity_type, entity_id, mode, payload_redacted json, before_hash, after_hash, diff_html mediumtext, status, error, policy_verdict json)
- `wp_ai_agent_sessions` (chat/session transcripts; optional)
- `wp_ai_agent_policies` (versioned JSON policies)

Use **native revisions** for content history. For WooCommerce, log “before” via `get_post()` + product object snapshot.

## 8) File/Folder Structure
```
ai-agent/
├─ ai-agent.php                     # Plugin bootstrap
├─ uninstall.php
├─ composer.json
├─ readme.txt
├─ .gitignore
├─ .editorconfig
├─ .gitattributes
├─ PROJECT_STRUCTURE.md
├─ assets/
│  └─ .gitkeep
├─ languages/
│  └─ .gitkeep
├─ includes/
│  └─ .gitkeep
├─ tests/
│  └─ .gitkeep
├─ config/
│  └─ config.php
└─ src/
   ├─ Core/
   │  ├─ Autoloader.php
   │  └─ Plugin.php
   ├─ Infrastructure/
   │  ├─ ServiceContainer.php
   │  ├─ ServiceProviderInterface.php
   │  └─ Hooks/
   │     ├─ HookableInterface.php
   │     └─ HooksLoader.php
   ├─ Application/
   │  └─ Providers/
   │     ├─ AbstractServiceProvider.php
   │     ├─ AdminServiceProvider.php
   │     ├─ FrontendServiceProvider.php
   │     ├─ RestApiServiceProvider.php
   │     └─ CliServiceProvider.php
   ├─ Domain/
   │  ├─ Contracts/
   │  │  └─ RepositoryInterface.php
   │  └─ Entities/
   │     └─ ExampleEntity.php
   ├─ Admin/
   │  └─ AdminMenu.php
   ├─ Frontend/
   │  └─ Shortcodes.php
   ├─ REST/
   │  └─ Controllers/
   │     └─ BaseRestController.php
   └─ Support/
      └─ Logger.php
```

## 9) Key Implementation Snippets
### Register Role & Caps (on activation)
```php
register_activation_hook(__FILE__, function() {
  add_role('ai_agent', 'AI Agent', ['read' => true]);
  $role = get_role('ai_agent');
  foreach (['edit_posts','edit_pages'] as $cap) { $role->add_cap($cap); }
  // Do NOT grant publish/delete by default.
});
```

### Register REST Route (cap + policy check)
```php
add_action('rest_api_init', function() {
  register_rest_route('ai-agent/v1', '/posts.update', [
    'methods'  => 'POST',
    'permission_callback' => function(WP_REST_Request $req){
      return current_user_can('edit_post', (int)$req['id']);
    },
    'callback' => function(WP_REST_Request $req){
      $id = (int)$req['id'];
      $fields = $req->get_param('fields') ?: [];
      // Policy checks (limit windows/regex/etc.)
      if (!AI_Agent_Policy::allowed('posts.update', $id, $fields)) {
        return new WP_Error('policy_block', 'Operation not allowed by policy', ['status'=>403]);
      }
      // Create revision by default
      wp_save_post_revision($id);
      $fields['ID'] = $id;
      $r = wp_update_post($fields, true);
      if (is_wp_error($r)) return $r;
      AI_Agent_Audit::log('posts.update', ['id'=>$id,'fields'=>$fields], 'success');
      return ['ok'=>true,'id'=>$id];
    }
  ]);
});
```

### Diff Preview (dry-run)
```php
$before = get_post($id);
$after  = clone $before; $after->post_content = $proposed_content;
$diff = wp_text_diff($before->post_content, $after->post_content, ['show_split_view'=>true]);
```

## 10) Frontend Chat Embeds
- **Shortcode** `[ai_agent_chat mode="review" types="post,page,product"]`
- **Block**: Inspector controls for mode, types, max ops per session
- Nonce injection: `wp_create_nonce('wp_rest')` passed to JS
- Accessibility: keyboard nav, ARIA roles; server-driven rate limiting

## 11) WooCommerce Considerations
- Use `WC_Product_*` APIs; set products **draft** by default
- Validate prices, stock, tax class, attributes; block external image hotlinks unless on allowlist
- For variations, limit total created per op; require attribute existence

## 12) Multisite & Environments
- Network settings to share policies; per-site overrides
- Environment awareness: **Force Suggest/Review** on production; allow Autonomous only on staging with explicit flag

## 13) Observability
- Admin screens for Logs (filters by tool, status, user, entity)
- Export logs (CSV)
- Webhook events: `action.created`, `action.succeeded`, `action.failed`

## 14) Safety Defaults (recommended)
- Autonomous = OFF on production
- Delete = require approval
- Publish = limited to designated categories
- Image uploads = max size + mime allowlist
- Max 20 writes/hour; max 100 writes/day

## 15) Roadmap
- Fine-tuned guardrails (content policy, profanity, PII redaction)
- Prompt templates per content type
- Batch ops with dry-run diffs
- WP-CLI commands for backfills & replays
- Vector store (optional) for site knowledge

---
**Deliverables:**
- MVP plugin skeleton
- Settings UI (modes, caps, limits)
- REST tools + policy engine + audit logs
- Chat widget + review UI (diffs)
- WooCommerce product tools (optional module)

