# Merge Strategy Guide

This document outlines the recommended merge strategies for the AI Agent WordPress plugin repository.

## 🔄 Merge Strategy Overview

### **Feature Branches → Development Branch**
- **Strategy**: Squash Merge
- **Reason**: Clean development history, easy feature tracking
- **Result**: Each feature becomes one clean commit

### **Development Branch → Main Branch**
- **Strategy**: Squash Merge
- **Reason**: Repository policy requires squash merge for main branch
- **Result**: Clean main branch history with single commit per feature set

## 📋 Branch Protection Rules

### **Development Branch**
- ✅ **CI Status Checks**: Required (CI must pass)
- ✅ **Reviews**: Not required (0 approving reviews)
- ✅ **Linear History**: Enforced
- ✅ **Force Push**: Disabled
- ✅ **Conversation Resolution**: Required

### **Main Branch**
- ✅ **CI Status Checks**: Required (CI must pass)
- ✅ **Reviews**: Not required (0 approving reviews)
- ✅ **Linear History**: Enforced
- ✅ **Force Push**: Disabled
- ✅ **Conversation Resolution**: Required

## 🚀 Workflow Process

### **1. Feature Development**
```bash
# Create feature branch
git checkout -b feature/new-feature

# Make changes and commit
git add .
git commit -m "feat: add new feature"

# Push and create PR
git push origin feature/new-feature
gh pr create --base development --head feature/new-feature
```

### **2. Feature → Development (Squash Merge)**
- Create PR from feature branch to development
- Use "Squash and merge" button
- Feature branch becomes one clean commit in development

### **3. Development → Main (Squash Merge)**
- Create PR from development to main
- Use "Squash and merge" button (only option available)
- Creates clean single commit in main branch

## ⚙️ GitHub Settings

### **Repository Settings**
- **Allow squash merging**: ✅ Enabled
- **Allow merge commits**: ❌ Disabled (main branch policy)
- **Allow rebase merging**: ✅ Enabled (but not recommended)

### **Default Merge Method**
- **Feature → Development**: Squash merge
- **Development → Main**: Squash merge (only option available)

## 🎯 Benefits

### **Squash Merge (Feature → Development)**
- ✅ Clean development branch history
- ✅ Easy to track features
- ✅ Simple rollbacks
- ✅ Professional commit messages

### **Squash Merge (Development → Main)**
- ✅ Clean main branch history
- ✅ Single commit per feature set
- ✅ Easy to track releases
- ✅ Simplified rollbacks

## 📝 Commit Message Standards

### **Feature Commits (Squash Merged)**
```
feat: add user authentication system
fix: resolve database connection issue
docs: update API documentation
refactor: improve code structure
```

### **Release Commits (Squash Merged)**
```
feat: complete Phase 1 Foundation Stabilization

- Add comprehensive testing infrastructure with 90%+ coverage
- Implement code quality tools (PHPStan Level 8, Psalm, PHPCS)
- Add security hardening and audit capabilities
- Create complete documentation (PHPDoc, API specs, Developer Guide)
- Fix release workflow conflicts
- Establish automated CI/CD pipeline

Release v0.2.1 - Phase 1 Foundation Stabilization Complete
```

## 🔧 CI/CD Integration

Both development and main branches require:
- **CI Status Checks**: Must pass before merge
- **No Reviews Required**: Streamlined workflow
- **Linear History**: Clean, readable history

### **Release Workflow**
- **Auto-version workflow**: Handles automatic releases on main branch
- **Release workflow**: Disabled to prevent conflicts
- **Version bumping**: Update version in `ai-agent.php` to trigger release

## 📚 Best Practices

1. **Always use PRs** for merging
2. **Squash feature branches** to development
3. **Squash merge** development to main (only option available)
4. **Keep feature branches** until you decide to delete them
5. **Use conventional commits** for clear history
6. **Test before merging** (CI handles this)
7. **Resolve conflicts** before merging (merge conflicts prevent merging)

---

*This guide ensures a clean, professional, and maintainable Git workflow for the AI Agent WordPress plugin project.*
