# AI Provider Configuration

AI Alt Text supports multiple AI providers with vision capabilities. This document provides detailed configuration instructions for each provider.

## Table of Contents

- [Configuration Priority](#configuration-priority)
- [OpenAI](#openai)
- [Azure OpenAI](#azure-openai)
- [Anthropic Claude](#anthropic-claude)
- [Google Gemini](#google-gemini)
- [Ollama (Self-Hosted)](#ollama-self-hosted)
- [Grok (xAI)](#grok-xai)
- [Environment Variables Reference](#environment-variables-reference)
- [Constants Reference](#constants-reference)
- [Troubleshooting](#troubleshooting)

---

## Configuration Priority

Settings are resolved in the following priority order:

1. **PHP Constants** (wp-config.php) - Highest priority
2. **Environment Variables** - Second priority
3. **Database Settings** (Admin UI) - Third priority
4. **Default Values** - Fallback

When a setting is defined via constant or environment variable, the admin UI field becomes read-only and shows the source.

---

## OpenAI

### Requirements
- OpenAI API account with billing enabled
- API key from [OpenAI Platform](https://platform.openai.com/api-keys)

### Recommended Models
| Model | Description | Cost |
|-------|-------------|------|
| `gpt-4o` | Best quality, multimodal (recommended) | $$$ |
| `gpt-4o-mini` | Good quality, lower cost | $$ |
| `gpt-4-turbo` | High quality, vision capable | $$$ |

### Configuration via wp-config.php

```php
define( 'AI_ALT_TEXT_AI_PROVIDER', 'openai' );
define( 'AI_ALT_TEXT_OPENAI_KEY', 'sk-proj-xxxxxxxxxxxxxxxxxxxx' );
define( 'AI_ALT_TEXT_OPENAI_MODEL', 'gpt-4o' );
define( 'AI_ALT_TEXT_OPENAI_TYPE', 'openai' ); // 'openai' or 'azure'
```

### Configuration via Environment Variables

```bash
AI_ALT_TEXT_AI_PROVIDER=openai
AI_ALT_TEXT_OPENAI_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxx
AI_ALT_TEXT_OPENAI_MODEL=gpt-4o
AI_ALT_TEXT_OPENAI_TYPE=openai
```

### API Endpoint
```
POST https://api.openai.com/v1/chat/completions
```

---

## Azure OpenAI

### Requirements
- Azure subscription
- Azure OpenAI Service resource
- Deployed GPT-4o or GPT-4 Vision model

### Setup Steps
1. Create an Azure OpenAI resource in the Azure Portal
2. Deploy a vision-capable model (e.g., gpt-4o)
3. Copy the endpoint URL and API key

### Configuration via wp-config.php

```php
define( 'AI_ALT_TEXT_AI_PROVIDER', 'openai' );
define( 'AI_ALT_TEXT_OPENAI_TYPE', 'azure' );
define( 'AI_ALT_TEXT_OPENAI_KEY', 'your-azure-api-key' );
define( 'AI_ALT_TEXT_OPENAI_MODEL', 'your-deployment-name' ); // Deployment name, not model name
define( 'AI_ALT_TEXT_AZURE_ENDPOINT', 'https://your-resource.openai.azure.com' );
define( 'AI_ALT_TEXT_AZURE_API_VERSION', '2024-02-15-preview' );
```

### Configuration via Environment Variables

```bash
AI_ALT_TEXT_AI_PROVIDER=openai
AI_ALT_TEXT_OPENAI_TYPE=azure
AI_ALT_TEXT_OPENAI_KEY=your-azure-api-key
AI_ALT_TEXT_OPENAI_MODEL=your-deployment-name
AI_ALT_TEXT_AZURE_ENDPOINT=https://your-resource.openai.azure.com
AI_ALT_TEXT_AZURE_API_VERSION=2024-02-15-preview
```

### API Endpoint Format
```
POST {endpoint}/openai/deployments/{deployment}/chat/completions?api-version={api-version}
```

---

## Anthropic Claude

### Requirements
- Anthropic Console account
- API key from [Anthropic Console](https://console.anthropic.com/)

### Recommended Models
| Model | Description | Cost |
|-------|-------------|------|
| `claude-sonnet-4-20250514` | Best balance of quality and speed | $$$ |
| `claude-3-5-sonnet-20241022` | Previous generation, still excellent | $$ |
| `claude-3-haiku-20240307` | Fastest, lower cost | $ |

### Configuration via wp-config.php

```php
define( 'AI_ALT_TEXT_AI_PROVIDER', 'anthropic' );
define( 'AI_ALT_TEXT_ANTHROPIC_KEY', 'sk-ant-xxxxxxxxxxxxxxxxxxxx' );
define( 'AI_ALT_TEXT_ANTHROPIC_MODEL', 'claude-sonnet-4-20250514' );
```

### Configuration via Environment Variables

```bash
AI_ALT_TEXT_AI_PROVIDER=anthropic
AI_ALT_TEXT_ANTHROPIC_KEY=sk-ant-xxxxxxxxxxxxxxxxxxxx
AI_ALT_TEXT_ANTHROPIC_MODEL=claude-sonnet-4-20250514
```

### API Endpoint
```
POST https://api.anthropic.com/v1/messages
```

### Notes
- Claude requires images to be sent as base64-encoded data
- The plugin automatically handles image encoding

---

## Google Gemini

### Requirements
- Google account
- API key from [Google AI Studio](https://aistudio.google.com/app/apikey)

### Recommended Models
| Model | Description | Cost |
|-------|-------------|------|
| `gemini-2.0-flash` | Latest, fastest multimodal | Free tier available |
| `gemini-1.5-flash` | Fast, good quality | Free tier available |
| `gemini-1.5-pro` | Highest quality | $$ |

### Configuration via wp-config.php

```php
define( 'AI_ALT_TEXT_AI_PROVIDER', 'gemini' );
define( 'AI_ALT_TEXT_GEMINI_KEY', 'AIzaSyxxxxxxxxxxxxxxxxxxxx' );
define( 'AI_ALT_TEXT_GEMINI_MODEL', 'gemini-2.0-flash' );
```

### Configuration via Environment Variables

```bash
AI_ALT_TEXT_AI_PROVIDER=gemini
AI_ALT_TEXT_GEMINI_KEY=AIzaSyxxxxxxxxxxxxxxxxxxxx
AI_ALT_TEXT_GEMINI_MODEL=gemini-2.0-flash
```

### API Endpoint Format
```
POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={api_key}
```

### Notes
- Gemini has a generous free tier (60 requests per minute)
- Images are sent as base64-encoded data

---

## Ollama (Self-Hosted)

### Requirements
- Ollama installed locally or on a server
- A vision-capable model pulled (e.g., llava)

### Installation

```bash
# macOS
brew install ollama

# Linux
curl -fsSL https://ollama.ai/install.sh | sh

# Start Ollama server
ollama serve

# Pull a vision model
ollama pull llava
```

### Recommended Models
| Model | Description | Size |
|-------|-------------|------|
| `llava` | LLaVA 7B, good quality | 4.7 GB |
| `llava:13b` | LLaVA 13B, better quality | 8.0 GB |
| `bakllava` | BakLLaVA, alternative | 4.7 GB |
| `llava-llama3` | LLaVA with Llama 3 | 5.5 GB |

### Configuration via wp-config.php

```php
define( 'AI_ALT_TEXT_AI_PROVIDER', 'ollama' );
define( 'AI_ALT_TEXT_OLLAMA_ENDPOINT', 'http://localhost:11434' );
define( 'AI_ALT_TEXT_OLLAMA_MODEL', 'llava' );
```

### Configuration via Environment Variables

```bash
AI_ALT_TEXT_AI_PROVIDER=ollama
AI_ALT_TEXT_OLLAMA_ENDPOINT=http://localhost:11434
AI_ALT_TEXT_OLLAMA_MODEL=llava
```

### API Endpoint Format
```
POST {endpoint}/api/generate
```

### Notes
- **Free** - No API costs, runs entirely locally
- Requires local installation of Ollama
- Processing speed depends on your hardware (GPU recommended)
- For Docker deployments, use `http://host.docker.internal:11434`

### Running Ollama on a Remote Server

If WordPress runs on a different server than Ollama:

```php
define( 'AI_ALT_TEXT_OLLAMA_ENDPOINT', 'http://your-server-ip:11434' );
```

Make sure the Ollama server is accessible from your WordPress server.

---

## Grok (xAI)

### Requirements
- xAI account
- API key from [xAI Console](https://console.x.ai/)

### Recommended Models
| Model | Description | Cost |
|-------|-------------|------|
| `grok-2-vision-1212` | Vision-capable Grok 2 | $$ |
| `grok-vision-beta` | Beta vision model | $$ |

### Configuration via wp-config.php

```php
define( 'AI_ALT_TEXT_AI_PROVIDER', 'grok' );
define( 'AI_ALT_TEXT_GROK_KEY', 'xai-xxxxxxxxxxxxxxxxxxxx' );
define( 'AI_ALT_TEXT_GROK_MODEL', 'grok-2-vision-1212' );
```

### Configuration via Environment Variables

```bash
AI_ALT_TEXT_AI_PROVIDER=grok
AI_ALT_TEXT_GROK_KEY=xai-xxxxxxxxxxxxxxxxxxxx
AI_ALT_TEXT_GROK_MODEL=grok-2-vision-1212
```

### API Endpoint
```
POST https://api.x.ai/v1/chat/completions
```

### Notes
- Grok uses an OpenAI-compatible API format
- Vision capabilities are available in Grok 2

---

## Environment Variables Reference

| Variable | Description | Default |
|----------|-------------|---------|
| `AI_ALT_TEXT_AI_PROVIDER` | Provider: openai, anthropic, gemini, ollama, grok | `openai` |
| `AI_ALT_TEXT_AUTO_GENERATE` | Auto-generate on upload: 1 or 0 | `1` |
| `AI_ALT_TEXT_OPENAI_TYPE` | OpenAI type: openai or azure | `openai` |
| `AI_ALT_TEXT_OPENAI_KEY` | OpenAI/Azure API key | (none) |
| `AI_ALT_TEXT_OPENAI_MODEL` | OpenAI model or Azure deployment name | `gpt-4o` |
| `AI_ALT_TEXT_AZURE_ENDPOINT` | Azure OpenAI endpoint URL | (none) |
| `AI_ALT_TEXT_AZURE_API_VERSION` | Azure API version | `2024-02-15-preview` |
| `AI_ALT_TEXT_ANTHROPIC_KEY` | Anthropic API key | (none) |
| `AI_ALT_TEXT_ANTHROPIC_MODEL` | Anthropic model name | `claude-3-5-sonnet-20241022` |
| `AI_ALT_TEXT_GEMINI_KEY` | Google AI API key | (none) |
| `AI_ALT_TEXT_GEMINI_MODEL` | Gemini model name | `gemini-1.5-flash` |
| `AI_ALT_TEXT_OLLAMA_ENDPOINT` | Ollama server URL | `http://localhost:11434` |
| `AI_ALT_TEXT_OLLAMA_MODEL` | Ollama model name | `llava` |
| `AI_ALT_TEXT_GROK_KEY` | xAI API key | (none) |
| `AI_ALT_TEXT_GROK_MODEL` | Grok model name | `grok-2-vision-1212` |

---

## Constants Reference

All environment variables can also be defined as PHP constants in `wp-config.php` using the same names:

```php
// Example: Full configuration for OpenAI
define( 'AI_ALT_TEXT_AI_PROVIDER', 'openai' );
define( 'AI_ALT_TEXT_AUTO_GENERATE', '1' );
define( 'AI_ALT_TEXT_OPENAI_KEY', 'sk-proj-xxxx' );
define( 'AI_ALT_TEXT_OPENAI_MODEL', 'gpt-4o' );
```

---

## Troubleshooting

### Common Issues

#### "Configuration is incomplete"
- Ensure you've set both the API key and model for your chosen provider
- Check that constants/environment variables are spelled correctly

#### "Unknown AI provider"
- Valid providers: `openai`, `anthropic`, `gemini`, `ollama`, `grok`
- Check for typos in the provider name

#### "Failed to fetch image"
- Ensure the image URL is accessible
- For local images, check file permissions
- For remote images, ensure the server allows external requests

#### Azure: "Resource not found"
- Verify the endpoint URL is correct (no trailing slash)
- Ensure the deployment name matches exactly
- Check the API version is supported

#### Ollama: "Connection refused"
- Ensure Ollama is running: `ollama serve`
- Check the endpoint URL (default: `http://localhost:11434`)
- Verify the model is pulled: `ollama list`

### Enable Debug Logging

Add to `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Logs are written to `wp-content/debug.log`.

### Testing API Connection

The plugin validates your configuration when you save settings. If there's an error, you'll see a warning message with details.

You can also test manually with curl:

```bash
# Test OpenAI
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer sk-proj-xxxx"

# Test Anthropic
curl https://api.anthropic.com/v1/messages \
  -H "x-api-key: sk-ant-xxxx" \
  -H "anthropic-version: 2023-06-01"

# Test Ollama
curl http://localhost:11434/api/tags

# Test Gemini
curl "https://generativelanguage.googleapis.com/v1beta/models?key=AIzaSy-xxxx"

# Test Grok
curl https://api.x.ai/v1/models \
  -H "Authorization: Bearer xai-xxxx"
```
