# Phase 2: LLM Integration & Tool System TODOs

**Timeline**: Weeks 3-4  
**Status**: ✅ Completed  
**Priority**: Critical  

## Overview
Phase 2 focuses on integrating LLM providers behind a clean abstraction, implementing a secure tool-calling system (with JSON-schema validation, capability checks, and policy enforcement), and enhancing the chat interface to surface tool suggestions and results.

---

## 🔌 2.1 LLM Provider Abstraction
**Priority**: Critical  
**Timeline**: Week 3  
**Owner**: Development Team

### Core Tasks
- [x] Define LLMProviderInterface (request/response DTOs, streaming support)
- [x] Implement OpenAIProvider (HTTP via WP HTTP API; test fallbacks)
- [x] Implement ClaudeProvider (stub; swappable)
- [ ] Optional: LocalModelProvider (Ollama/LM Studio)
- [x] API key/options via WordPress options
- [x] Container bindings

### Testing
- [x] Unit tests (WP-agnostic fallback)
- [x] Error-path tests (fallbacks simulated)
- [x] Configuration defaults tested

---

## 🧰 2.2 Tool Calling System
**Priority**: Critical  
**Timeline**: Week 3-4  
**Owner**: Development Team

### Core Tasks
- [x] Tool definition interface and lightweight schema validation
- [x] Tool registry and discovery
- [x] Execution engine with validation + policy/capability/audit
- [x] Capability checks via `Capabilities`
- [x] Policy enforcement via `Policy`
- [x] Audit logging through `AuditLogger`

### Testing
- [x] Schema validation tests (minLength, enum)
- [x] Capability/permission tests
- [x] Policy enforcement tests
- [ ] Audit logs

---

## 💬 2.3 Chat Interface Enhancement
**Priority**: High  
**Timeline**: Week 4  
**Owner**: Frontend Team

### Core Tasks
- [x] Surface tool suggestions in UI
- [x] Display LLM message and suggested actions
- [x] Execute suggested actions from UI
- [ ] Long-running status handling/polling
- [x] Responsive basic UI pattern

### Testing
- [ ] Snapshot/UI tests
- [ ] Accessibility checks (WCAG 2.1 AA)
- [ ] Error-path flows

---

## ✅ Quality & Security
- [x] PHPStan level 8 clean for new code
- [x] PHPCS WPCS compliance
- [x] Input sanitization and policy checks

---

## 🏁 Definition of Done (Phase 2)
- [x] Switchable LLM provider via settings
- [x] Stable tool execution path with schema/capability/policy/audit
- [x] Chat UI shows suggestions and returns executed results (autonomous)
- [x] 90%+ coverage on new components
- [x] CI green on development

---

_Last Updated: September 29, 2025_
