# Merge Strategy Guide

This document outlines the recommended merge strategies for the AI Agent WordPress plugin repository.

## 🔄 Merge Strategy Overview

### **Feature Branches → Development Branch**
- **Strategy**: Squash Merge
- **Reason**: Clean development history, easy feature tracking
- **Result**: Each feature becomes one clean commit

### **Development Branch → Main Branch**
- **Strategy**: Merge Commit
- **Reason**: Preserves complete development history, clear release boundaries
- **Result**: Complete audit trail of all development work

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

### **3. Development → Main (Merge Commit)**
- Create PR from development to main
- Use "Create a merge commit" button
- Preserves all development history

## ⚙️ GitHub Settings

### **Repository Settings**
- **Allow squash merging**: ✅ Enabled
- **Allow merge commits**: ✅ Enabled
- **Allow rebase merging**: ✅ Enabled (but not recommended)

### **Default Merge Method**
- **Feature → Development**: Squash merge
- **Development → Main**: Merge commit

## 🎯 Benefits

### **Squash Merge (Feature → Development)**
- ✅ Clean development branch history
- ✅ Easy to track features
- ✅ Simple rollbacks
- ✅ Professional commit messages

### **Merge Commit (Development → Main)**
- ✅ Complete development audit trail
- ✅ Clear release boundaries
- ✅ Easy to see what's in each release
- ✅ Safe rollbacks

## 📝 Commit Message Standards

### **Feature Commits (Squash Merged)**
```
feat: add user authentication system
fix: resolve database connection issue
docs: update API documentation
refactor: improve code structure
```

### **Release Commits (Merge Committed)**
```
Merge pull request #123 from imriShCodeArt/development
Release v1.2.0 - User Authentication & API Improvements
```

## 🔧 CI/CD Integration

Both development and main branches require:
- **CI Status Checks**: Must pass before merge
- **No Reviews Required**: Streamlined workflow
- **Linear History**: Clean, readable history

## 📚 Best Practices

1. **Always use PRs** for merging
2. **Squash feature branches** to development
3. **Merge commit** development to main
4. **Keep feature branches** until you decide to delete them
5. **Use conventional commits** for clear history
6. **Test before merging** (CI handles this)

---

*This guide ensures a clean, professional, and maintainable Git workflow for the AI Agent WordPress plugin project.*
