# Phase 4: WooCommerce Integration TODOs

**Timeline**: Weeks 7-8  
**Status**: Planned  
**Priority**: High

## Overview
Integrate WooCommerce to enable AI-assisted product management, catalog operations, and storefront experiences. Build secure REST endpoints and admin tools, and extend Phase 3 security policies to commerce actions (rate limits, approvals, and auditability).

---

## 🛍️ 4.1 WooCommerce Foundations
**Owner**: Backend Team  
**Timeline**: Week 7

### Core Tasks
- [x] Ensure WooCommerce dependency detection and graceful disable if missing
- [x] Service bindings for WC repositories (products, orders, customers)
- [x] Data mappers for WC objects → domain DTOs (typed, sanitized)
- [x] Capability map for commerce actions (manage_woocommerce, edit_products, etc.)

### Implementation Notes
- [x] Feature flag `ai_agent_wc_enabled` (option + filter)
- [x] Nonce + capability checks around all admin actions
- [x] Strict sanitization/escaping for product inputs

### Testing
- [x] Unit: mappers (WC_Product, WC_Order) happy/error paths
- [x] Unit: capability gates for each action

---

## 📦 4.2 Product Management Tools
**Owner**: Backend Team  
**Timeline**: Week 7

### Tools
- [x] `products.create` (title, description, price, stock, categories, images)
- [x] `products.update` (partial updates)
- [x] `products.bulkUpdate` (batch operations with per-item validation)
- [x] `products.search` (filters: sku, category, price range, stock status)

### Implementation Notes
- [x] JSON schema for each tool (required, enums, ranges)
- [x] Reuse Validator with stricter commerce rules (currency, price, stock)
- [x] Media handling for images (URLs → sideload with validation)

### Testing
- [x] Unit: each tool happy/error paths (missing fields, invalid price)
- [x] Unit: schema validation failures return structured errors

---

## 🧾 4.3 Orders & Customers (Read/Assist)
**Owner**: Backend Team  
**Timeline**: Week 8

### Core Tasks
- [x] `orders.get` (by id), `orders.search` (status/date/customer)
- [x] `customers.get` (by id/email), `customers.search`
- [x] Summarization helpers (order issue summary, customer profile snapshot)

### Implementation Notes
- [x] Read-only for Phase 4; mutations deferred to Phase 5
- [x] Pagination & limits with policy-backed rate limits

### Testing
- [x] Unit: search parameter validation and pagination bounds
- [x] Unit: redaction of PII fields in summaries/logs

---

## 🌐 4.4 REST API & Admin UI Wiring
**Owner**: Backend + Frontend  
**Timeline**: Week 8

### REST
- [x] Namespaced routes under `/ai-agent/v1/wc/*`
- [x] Read-only products search endpoint (`/wc/products`)
- [x] Security middleware (HMAC/OAuth2/app passwords) applied
- [x] Input validation, capability checks, audit logging

### Admin UI (New)
- [x] Add top-level menu: "AI Agent"
- [x] Subpages: Dashboard, Tools, Policies, Logs, Settings
- [x] Settings screen: feature flags, API keys, HMAC/OAuth2, WooCommerce enable
- [x] Register options (sanitize callbacks), capability checks, nonces
- [x] Wire existing classes `Admin\AdminMenu` and `Admin\Settings` (or add if missing)
- [x] Hook registration via service provider so menus load consistently
- [x] Minimal dev utility to dry-run/execute tools from admin for testing

### Testing
- [x] Unit: permission_callbacks and schema validators
- [x] Smoke tests: route registration and responses
- [x] Smoke tests: admin menu items and screens render without notices

---

## 🛡️ 4.5 Security & Policy Extensions
**Owner**: Security Team  
**Timeline**: Week 8

### Core Tasks
- [x] Policy rules for commerce actions (product create/update, bulk ops)
- [x] Approval workflows for sensitive changes (price > threshold, stock to zero)
- [x] Rate limits (per-user, per-IP, per-tool) tuned for commerce

### Implementation Notes
- [x] Extend `EnhancedPolicy` to include WC operation categories
- [x] Add audit fields specific to commerce (product_id, sku, price_change)

### Testing
- [x] Unit: policy allow/deny with boundary cases (price thresholds)
- [x] Unit: audit payload includes commerce metadata

---

## 📈 4.6 Performance & Reliability
**Owner**: Backend Team  
**Timeline**: Week 8

### Core Tasks
- [x] Caching for product/category lookups (object cache if available)
- [x] Batch pagination safeguards (max page/size)
- [x] Timeout and retry strategies for media sideloading

### Testing
- [x] Unit: cache key building and invalidation
- [x] Unit: batch guards enforce limits

---

## ✅ Definition of Done
- [x] Product tools implemented with validation and security controls
- [x] Read-only order/customer endpoints delivered
- [x] REST routes secured with middleware and capability checks
- [x] Admin menu + settings screens available and functional
- [x] Policies extended to commerce actions with tests
- [ ] PHPUnit green, PHPStan level 8 clean
- [x] Documentation updated (README, Developer Guide)

---

_Last Updated: <?= date('Y-m-d') ?>_
