<?php
/**
 * Plugin Name: AI Alt Text
 * Description: Automatically generate alt text for images using AI. Supports OpenAI, Claude, Gemini, Ollama, Azure OpenAI, and Grok. Auto-generates on upload, bulk update in media library, and generate from image block.
 * Version: 1.0.3
 * Author: Per Soderlind
 * Author URI: https://soderlind.no
 * Plugin URI: https://github.com/soderlind/ai-alt-text
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Text Domain: ai-alt-text
 */

declare(strict_types=1);

define( 'AI_ALT_TEXT_VERSION', '1.0.3' );
define( 'AI_ALT_TEXT_PATH', plugin_dir_path( __FILE__ ) );
define( 'AI_ALT_TEXT_URL', plugin_dir_url( __FILE__ ) );

use AiAltText\Update\GitHubPluginUpdater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer autoload.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

// Inject CSP nonce attribute into enqueued script tags if a server-provided nonce is available.
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
	// Skip if nonce already present.
	if ( strpos( $tag, ' nonce=' ) !== false ) {
		return $tag;
	}
	$csp_nonce = '';
	if ( isset( $_SERVER[ 'CONTENT_SECURITY_POLICY_NONCE' ] ) ) {
		$csp_nonce = (string) $_SERVER[ 'CONTENT_SECURITY_POLICY_NONCE' ];
	}
	if ( ! $csp_nonce && defined( 'AI_ALT_TEXT_CSP_NONCE' ) ) {
		$csp_nonce = (string) AI_ALT_TEXT_CSP_NONCE;
	}
	$csp_nonce = apply_filters( 'ai_alt_text_csp_nonce', $csp_nonce, $handle, $src );
	if ( $csp_nonce ) {
		$escaped = esc_attr( $csp_nonce );
		$tag     = preg_replace( '/<script\b/', '<script nonce="' . $escaped . '"', $tag, 1 );
	}
	return $tag;
}, 10, 3 );

add_action( 'plugins_loaded', function () {
	// Load translations.
	load_plugin_textdomain( 'ai-alt-text', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Update checker via GitHub releases.
	GitHubPluginUpdater::create_with_assets(
		'https://github.com/soderlind/ai-alt-text',
		__FILE__,
		'ai-alt-text',
		'/ai-alt-text\.zip/',
		'main'
	);

	// Register REST endpoint for generating alt text.
	( new \AiAltText\REST\AltTextController() )->register();

	// Register admin settings page.
	new \AiAltText\Admin\SettingsPage();

	// Register media library handlers.
	new \AiAltText\Media\MediaLibraryHandler();
	new \AiAltText\Media\BulkAltTextHandler();

	// Register block editor integration.
	new \AiAltText\Blocks\ImageBlockExtension();
} );
