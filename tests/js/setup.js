/**
 * Vitest setup file.
 *
 * Sets up global configuration for tests.
 */

// Global window mock for WordPress config
global.aiAltTextConfig = {
	restUrl: '/wp-json/ai-alt-text/v1/generate',
	nonce: 'test-nonce-123',
	i18n: {
		generateAltText: 'Generate AI Alt Text',
		generating: 'Generating...',
		success: 'Alt text generated!',
		error: 'Failed to generate alt text',
		noImage: 'No image selected',
		buttonLabel: 'AI Alt Text',
	},
};
