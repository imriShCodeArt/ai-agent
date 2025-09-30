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
- [ ] Ensure WooCommerce dependency detection and graceful disable if missing
- [ ] Service bindings for WC repositories (products, orders, customers)
- [ ] Data mappers for WC objects → domain DTOs (typed, sanitized)
- [ ] Capability map for commerce actions (manage_woocommerce, edit_products, etc.)

### Implementation Notes
- [ ] Feature flag `ai_agent_wc_enabled` (option + filter)
- [ ] Nonce + capability checks around all admin actions
- [ ] Strict sanitization/escaping for product inputs

### Testing
- [ ] Unit: mappers (WC_Product, WC_Order) happy/error paths
- [ ] Unit: capability gates for each action

---

## 📦 4.2 Product Management Tools
**Owner**: Backend Team  
**Timeline**: Week 7

### Tools
- [ ] `products.create` (title, description, price, stock, categories, images)
- [ ] `products.update` (partial updates)
- [ ] `products.bulkUpdate` (batch operations with per-item validation)
- [ ] `products.search` (filters: sku, category, price range, stock status)

### Implementation Notes
- [ ] JSON schema for each tool (required, enums, ranges)
- [ ] Reuse Validator with stricter commerce rules (currency, price, stock)
- [ ] Media handling for images (URLs → sideload with validation)

### Testing
- [ ] Unit: each tool happy/error paths (missing fields, invalid price)
- [ ] Unit: schema validation failures return structured errors

---

## 🧾 4.3 Orders & Customers (Read/Assist)
**Owner**: Backend Team  
**Timeline**: Week 8

### Core Tasks
- [ ] `orders.get` (by id), `orders.search` (status/date/customer)
- [ ] `customers.get` (by id/email), `customers.search`
- [ ] Summarization helpers (order issue summary, customer profile snapshot)

### Implementation Notes
- [ ] Read-only for Phase 4; mutations deferred to Phase 5
- [ ] Pagination & limits with policy-backed rate limits

### Testing
- [ ] Unit: search parameter validation and pagination bounds
- [ ] Unit: redaction of PII fields in summaries/logs

---

## 🌐 4.4 REST API & Admin UI Wiring
**Owner**: Backend + Frontend  
**Timeline**: Week 8

### REST
- [ ] Namespaced routes under `/ai-agent/v1/wc/*`
- [ ] Security middleware (HMAC/OAuth2/app passwords) applied
- [ ] Input validation, capability checks, audit logging

### Admin UI
- [ ] Settings: enable/disable WC features, rate limits, role gates
- [ ] Minimal screens for tool dry-run + execute (dev utility)

### Testing
- [ ] Unit: permission_callbacks and schema validators
- [ ] Smoke tests: route registration and responses

---

## 🛡️ 4.5 Security & Policy Extensions
**Owner**: Security Team  
**Timeline**: Week 8

### Core Tasks
- [ ] Policy rules for commerce actions (product create/update, bulk ops)
- [ ] Approval workflows for sensitive changes (price > threshold, stock to zero)
- [ ] Rate limits (per-user, per-IP, per-tool) tuned for commerce

### Implementation Notes
- [ ] Extend `EnhancedPolicy` to include WC operation categories
- [ ] Add audit fields specific to commerce (product_id, sku, price_change)

### Testing
- [ ] Unit: policy allow/deny with boundary cases (price thresholds)
- [ ] Unit: audit payload includes commerce metadata

---

## 📈 4.6 Performance & Reliability
**Owner**: Backend Team  
**Timeline**: Week 8

### Core Tasks
- [ ] Caching for product/category lookups (object cache if available)
- [ ] Batch pagination safeguards (max page/size)
- [ ] Timeout and retry strategies for media sideloading

### Testing
- [ ] Unit: cache key building and invalidation
- [ ] Unit: batch guards enforce limits

---

## ✅ Definition of Done
- [ ] Product tools implemented with validation and security controls
- [ ] Read-only order/customer endpoints delivered
- [ ] REST routes secured with middleware and capability checks
- [ ] Policies extended to commerce actions with tests
- [ ] PHPUnit green, PHPStan level 8 clean
- [ ] Documentation updated (README, Developer Guide)

---

_Last Updated: (set on completion)_
