# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-11-27

### Added

- "Generate with AI" button on single media edit page (left-aligned below alt text field)
- "Generate with AI" button in media library grid view modal (right-aligned below alt text field)

### Changed

- Improved button placement using WordPress native `copy-to-clipboard-container` styling for grid modal

## [1.0.0] - 2025-11-27

### Added

- Initial release
- **AI Provider Support**
  - OpenAI GPT-4o with vision capabilities
  - Anthropic Claude Sonnet with vision
  - Google Gemini 2.0 Flash
  - Azure OpenAI with GPT-4o
  - Ollama with LLaVA model (local, free)
  - Grok 2 Vision from xAI
- **Auto-generation on Upload**
  - Automatically generates alt text when images are uploaded to the media library
  - Can be enabled/disabled in settings
- **Bulk Update**
  - Bulk action in Media Library to generate alt text for multiple images
  - Works in list view with checkbox selection
- **Block Editor Integration**
  - "AI Alt Text" panel in image block sidebar
  - One-click generation from the editor
- **Language Support**
  - Automatically detects WordPress language setting
  - Generates alt text in the site's configured language
- **Flexible Configuration**
  - Admin settings page under Settings → AI Alt Text
  - Configuration via PHP constants (wp-config.php)
  - Configuration via environment variables
  - Priority: constants > environment > database > defaults
- **REST API**
  - Endpoint at `/wp-json/ai-alt-text/v1/generate`
  - Supports both attachment ID and external image URL
- **Security**
  - Nonce verification for all requests
  - Capability checks for media management
  - CSP nonce support for script tags
- **Developer Features**
  - Filters for customizing generated alt text
  - Filters for customizing AI prompts
  - Filter to skip auto-generation for specific images
  - GitHub-based automatic updates

### Security

- All API keys are stored securely and never exposed to the frontend
- REST API endpoints require authentication and proper capabilities

[1.0.1]: https://github.com/soderlind/ai-alt-text/releases/tag/1.0.1
[1.0.0]: https://github.com/soderlind/ai-alt-text/releases/tag/1.0.0
