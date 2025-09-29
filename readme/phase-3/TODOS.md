# Phase 3: Advanced Security & Policy Engine TODOs

**Timeline**: Weeks 5-6  
**Status**: In Progress  
**Priority**: Critical

## Overview
Elevate security and governance: richer policy rules, stronger authentication and rate limiting, and comprehensive auditability. Build on Phase 2’s foundations (LLM + tools + policy checks).

---

## 🔐 3.1 Policy Engine Enhancement
**Owner**: Backend Team  
**Timeline**: Week 5

### Core Tasks
- [ ] Time-based restrictions (working hours, blackout windows)
- [ ] Content filtering (regex, blocked terms lists, severity levels)
- [ ] Rate limiting per user/IP/tool
- [ ] Approval workflows (multi-step, per tool/entity)
- [ ] Policy versioning (persisted snapshots + diff)

### Implementation Notes
- [ ] Extend `Policy` to evaluate new rule blocks
- [ ] Persist policy documents in DB with migration
- [ ] Expose admin UI endpoints (later wired in Phase 5)

### Testing
- [ ] Unit: each rule block with allow/deny cases
- [ ] Policy diff/version retrieval tests
- [ ] Rate-limit boundary tests (hour/day)

---

## 🛡️ 3.2 Security Hardening
**Owner**: Backend + DevOps  
**Timeline**: Week 6

### Core Tasks
- [ ] Application passwords for service accounts
- [ ] Optional OAuth2 integration (scoped tokens)
- [ ] HMAC signing for webhook/execute endpoints
- [ ] Rate limiting + DDoS protection (server/WAF notes)

### Implementation Notes
- [ ] Add signing/verification middleware for critical routes
- [ ] Store secrets via WP options with sanitization + capabilities
- [ ] Document rotation procedures

### Testing
- [ ] Unit: HMAC signature valid/invalid
- [ ] Unit: capability gates on setting mutations
- [ ] Integration (staging): OAuth2 happy-path

---

## ⚙️ Migrations & Data
- [ ] Create `ai_agent_policies` (id, version, doc, ts, author)
- [ ] Ensure indexes for audit/policy lookup

---

## 📊 Observability & Audit
- [ ] Expand `AuditLogger` to store policy version, verdict detail
- [ ] Add structured error taxonomy for policy denials

---

## ✅ Definition of Done
- [ ] Policy engine supports time/content/rate-limit/entity approval rules
- [ ] Versioned policies persisted with retrieval & diff
- [ ] HMAC signing and/or OAuth2 available and documented
- [ ] Unit/integration tests cover new rules and security paths
- [ ] CI green and static analysis clean (PHPStan L8)

---

_Last Updated: September 29, 2025_
