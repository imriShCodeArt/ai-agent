# Phase 2: LLM Integration & Tool System TODOs

**Timeline**: Weeks 3-4  
**Status**: In Progress  
**Priority**: Critical  

## Overview
Phase 2 focuses on integrating LLM providers behind a clean abstraction, implementing a secure tool-calling system (with JSON-schema validation, capability checks, and policy enforcement), and enhancing the chat interface to surface tool suggestions and results.

---

## ?? 2.1 LLM Provider Abstraction
**Priority**: Critical  
**Timeline**: Week 3  
**Owner**: Development Team

### Core Tasks
- [ ] Define LLMProviderInterface (request/response DTOs, streaming support)
- [ ] Implement OpenAIProvider
- [ ] Implement ClaudeProvider
- [ ] Optional: LocalModelProvider (Ollama/LM Studio)
- [ ] API key/options management via WordPress options
- [ ] Add provider factory + container bindings

### Testing
- [ ] Unit tests with mocked HTTP clients
- [ ] Error-path tests (timeouts, rate limits, 4xx/5xx)
- [ ] Configuration validation tests

---

## ?? 2.2 Tool Calling System
**Priority**: Critical  
**Timeline**: Week 3-4  
**Owner**: Development Team

### Core Tasks
- [ ] Tool definition schema (JSON Schema)
- [ ] Tool registry and discovery
- [ ] Execution engine with validation
- [ ] Capability checks per tool (Capabilities)
- [ ] Policy enforcement (Policy rules)
- [ ] Audit logging for all executions

### Testing
- [ ] Schema validation tests
- [ ] Capability/permission tests
- [ ] Policy enforcement tests
- [ ] Audit logs written with before/after

---

## ?? 2.3 Chat Interface Enhancement
**Priority**: High  
**Timeline**: Week 4  
**Owner**: Frontend Team

### Core Tasks
- [ ] Surface tool suggestions in UI
- [ ] Render tool results with status/errors
- [ ] Support long-running task status (polling)
- [ ] Responsive and accessible UI patterns

### Testing
- [ ] Snapshot/UI tests
- [ ] Accessibility checks (WCAG 2.1 AA)
- [ ] Error-path flows

---

## ?? Quality & Security
- [ ] PHPStan level 8 clean for new code
- [ ] Psalm clean for new code
- [ ] PHPCS WPCS compliance
- [ ] Security review for tool inputs/outputs

---

## ? Definition of Done (Phase 2)
- [ ] Switchable LLM providers via settings
- [ ] Stable tool execution path with schema/capability/policy/audit
- [ ] Chat UI supports tool suggestions and results
- [ ] 90%+ test coverage for new components
- [ ] CI pipeline passes across PHP versions matrix

---

_Last Updated: September 29, 2025_
