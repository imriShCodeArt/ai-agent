# AI Agent Plugin

A powerful WordPress plugin that provides AI-powered content management capabilities through a secure, policy-controlled interface.

## 🚀 Features

- **AI-Powered Content Management**: Create, edit, and manage content using AI assistance
- **Policy-Based Security**: Comprehensive security controls and access management
- **REST API**: Full REST API for external integrations
- **Audit Logging**: Complete audit trail of all actions
- **Role-Based Access Control**: Granular permissions system
- **Performance Optimized**: Built for high-performance environments

## 📋 Requirements

- WordPress 6.0 or higher
- PHP 8.1 or higher
- MySQL 5.7 or higher
- Composer (for development)

## 🛠️ Installation

### From WordPress Admin

1. Go to **Plugins > Add New**
2. Search for "AI Agent"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/ai-agent/`
3. Activate the plugin through the **Plugins** menu

### Development Installation

```bash
git clone https://github.com/your-org/ai-agent-plugin.git
cd ai-agent-plugin
composer install
```

## 🔧 Configuration

### Basic Setup

1. Go to **AI Agent > Settings** in your WordPress admin
2. Configure your AI service API key
3. Set up user permissions and policies
4. Configure audit logging settings

### API Configuration

The plugin provides REST API endpoints for external integrations:

- **Base URL**: `/wp-json/ai-agent/v1/`
- **Authentication**: WordPress user authentication + nonce
- **Documentation**: See `docs/api/openapi.yaml`

### Security Settings

Configure security policies in **AI Agent > Security**:

- Rate limiting
- Content restrictions
- Time windows
- User capabilities

## 📖 Usage

### Basic Chat Interface

```javascript
// Send a chat message to the AI agent
const response = await fetch('/wp-json/ai-agent/v1/chat', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        prompt: 'Create a new blog post about WordPress security',
        mode: 'suggest'
    })
});

const data = await response.json();
console.log(data.data.suggested_actions);
```

### Dry Run Preview

```javascript
// Preview changes before execution
const response = await fetch('/wp-json/ai-agent/v1/dry-run', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        tool: 'posts.create',
        fields: {
            post_title: 'New Post Title',
            post_content: 'New post content'
        }
    })
});
```

### WordPress Hooks

```php
// Customize AI agent behavior
add_filter('ai_agent_chat_response', function($response, $prompt) {
    // Modify the AI response
    return $response;
}, 10, 2);

// Add custom tools
add_action('ai_agent_register_tools', function($tools) {
    $tools->register('custom_tool', new CustomTool());
});
```

## 🔒 Security

### Built-in Security Features

- **Input Validation**: All inputs are sanitized and validated
- **Output Escaping**: All outputs are properly escaped
- **CSRF Protection**: Nonce verification on all requests
- **SQL Injection Prevention**: Prepared statements only
- **XSS Prevention**: Proper escaping and sanitization
- **Rate Limiting**: Configurable rate limits per user/action
- **Content Filtering**: Blocked terms and pattern matching

### Security Audit

Run the built-in security audit:

```bash
composer security-scan
```

### Best Practices

1. **Keep the plugin updated**
2. **Use strong passwords**
3. **Limit user permissions**
4. **Monitor audit logs**
5. **Regular security scans**

## 🧪 Testing

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run performance tests
composer test-performance
```

### Test Coverage

The plugin maintains 90%+ test coverage across:
- Unit tests
- Integration tests
- Performance tests
- Security tests

## 📚 Documentation

- **[Developer Guide](docs/DEVELOPER_GUIDE.md)**: Complete development documentation
- **[API Documentation](docs/api/openapi.yaml)**: OpenAPI specification
- **[Architecture Guide](docs/ARCHITECTURE.md)**: System architecture overview
- **[Security Guide](docs/SECURITY.md)**: Security best practices

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Run quality checks
6. Submit a pull request

### Code Quality

The plugin follows strict code quality standards:

- PHPStan level 8 (strictest)
- WordPress coding standards
- 90%+ test coverage
- Comprehensive documentation

## 📊 Performance

### Benchmarks

- **Database Operations**: < 100ms
- **API Response Time**: < 500ms
- **Memory Usage**: < 10MB per request
- **Test Suite**: < 30 seconds

### Optimization Features

- Lazy loading
- Caching
- Database query optimization
- Background processing

## 🐛 Troubleshooting

### Common Issues

#### Plugin Not Activating
- Check PHP version (8.1+ required)
- Verify WordPress version (6.0+ required)
- Check for plugin conflicts

#### API Errors
- Verify user authentication
- Check nonce validity
- Ensure proper permissions

#### Performance Issues
- Check server resources
- Review database queries
- Monitor memory usage

### Getting Help

- Check the [FAQ](docs/FAQ.md)
- Review [GitHub Issues](https://github.com/your-org/ai-agent-plugin/issues)
- Contact support: support@aiagent-plugin.com

## 📈 Roadmap

### Version 1.1
- [ ] Advanced AI model integration
- [ ] Custom tool development
- [ ] Enhanced analytics
- [ ] Multi-language support

### Version 1.2
- [ ] Mobile app integration
- [ ] Advanced security features
- [ ] Performance optimizations
- [ ] Enterprise features

## 📄 License

This plugin is licensed under the GPL-2.0-or-later license. See the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- WordPress community
- Open source contributors
- Beta testers
- Security researchers

## 📞 Support

- **Documentation**: [docs/](docs/)
- **Issues**: [GitHub Issues](https://github.com/your-org/ai-agent-plugin/issues)
- **Email**: support@aiagent-plugin.com
- **Discord**: [Join our community](https://discord.gg/ai-agent)

---

**Made with ❤️ for the WordPress community**
