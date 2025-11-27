=== AI Alt Text ===
Contributors: persoderlind
Tags: alt text, accessibility, ai, images, seo
Requires at least: 6.8
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 8.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate alt text for images using AI. Supports OpenAI, Claude, Gemini, Ollama, Azure OpenAI, and Grok.

== Description ==

AI Alt Text automatically generates descriptive alt text for your images using artificial intelligence. This improves accessibility for screen reader users and can boost your SEO.

= Features =

* **Auto-generate on Upload** - Automatically generates alt text when images are uploaded to the media library
* **Bulk Update** - Update alt text for multiple images at once from the media library
* **Block Editor Integration** - Generate alt text directly from the image block in the editor
* **Multiple AI Providers** - Choose from OpenAI (GPT-4o), Anthropic Claude, Google Gemini, Azure OpenAI, Ollama (local), or Grok
* **Language Detection** - Automatically uses WordPress language settings for generated alt text
* **Flexible Configuration** - Configure via constants, environment variables, or the admin settings page

= Supported AI Providers =

* **OpenAI** - GPT-4o with vision capabilities
* **Anthropic** - Claude Sonnet with vision
* **Google Gemini** - Gemini 2.0 Flash
* **Azure OpenAI** - GPT-4o via Azure
* **Ollama** - LLaVA model (runs locally, free)
* **Grok** - Grok 2 Vision from xAI

= Why Alt Text Matters =

Alt text (alternative text) describes images for:

* Screen reader users who are blind or visually impaired
* Users with slow internet connections where images don't load
* Search engines to understand image content for SEO

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/ai-alt-text` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings → AI Alt Text to configure your AI provider and API key.

= Configuration =

1. Select your preferred AI provider
2. Enter your API key (not needed for Ollama)
3. Optionally customize the model and other settings

You can also configure the plugin using constants in wp-config.php:

`
define( 'AI_ALT_TEXT_PROVIDER', 'openai' );
define( 'AI_ALT_TEXT_API_KEY', 'your-api-key-here' );
`

== Frequently Asked Questions ==

= Which AI provider should I use? =

* **OpenAI GPT-4o** - Best overall quality, requires paid API access
* **Anthropic Claude** - Excellent quality, requires paid API access
* **Google Gemini** - Good quality, generous free tier available
* **Ollama** - Free and runs locally on your server, requires local installation
* **Azure OpenAI** - Enterprise option for Azure customers
* **Grok** - Alternative option from xAI

= Does this work with existing images? =

Yes! Use the bulk action in the Media Library to generate alt text for existing images. Select the images, choose "Generate AI Alt Text" from the bulk actions dropdown, and click Apply.

= Can I edit the generated alt text? =

Yes, the generated alt text is saved to the image's alt text field and can be edited like any other alt text.

= Does it work with the block editor? =

Yes! When you select an image block, you'll see an "AI Alt Text" panel in the sidebar with a button to generate alt text.

= What languages are supported? =

The plugin uses your WordPress language setting to generate alt text in your preferred language.

= Is my data sent to external servers? =

Yes, image URLs are sent to your chosen AI provider for analysis. If you're concerned about privacy, consider using Ollama which runs entirely on your own server.

= Does it work with external images? =

Yes, the plugin can analyze images from external URLs, not just images in your media library.

== Screenshots ==

1. Settings page - Configure your AI provider and API key
2. Media Library bulk action - Generate alt text for multiple images
3. Block editor integration - Generate alt text from the image block sidebar

== Changelog ==

= 1.0.0 =
* Initial release
* Support for OpenAI, Anthropic Claude, Google Gemini, Azure OpenAI, Ollama, and Grok
* Auto-generate alt text on image upload
* Bulk update alt text in media library
* Block editor integration with sidebar panel
* Language detection from WordPress settings
* Flexible configuration via constants, environment variables, or admin settings

== Upgrade Notice ==

= 1.0.0 =
Initial release of AI Alt Text plugin.
