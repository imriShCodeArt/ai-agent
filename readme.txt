# AI Agent

Foundational OOP architecture for a robust WordPress plugin.

## Branching Strategy

This project uses a **Git Flow** branching model:

- **`main`** - Production-ready releases only
- **`development`** - Integration branch for features
- **`feature/*`** - Feature branches (merge to `development`)
- **`hotfix/*`** - Critical fixes (merge to `main` and `development`)

### Workflow

1. Create feature branch from `development`
2. Develop and test your feature
3. Create PR to merge into `development`
4. After review and CI passes, merge to `development`
5. When ready for release, create PR from `development` to `main`
6. Auto-tagging occurs on merge to `main`

### Branch Protection

- `development` requires CI checks and 1 approval
- `main` requires CI checks and 1 approval
- Direct pushes to `main` are disabled
