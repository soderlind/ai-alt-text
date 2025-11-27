<?php
/**
 * Image Block Extension - Add AI alt text button to image block.
 *
 * @package AiAltText\Blocks
 */

declare(strict_types=1);

namespace AiAltText\Blocks;

/**
 * Extends the core/image block with an AI alt text generation button.
 */
class ImageBlockExtension {

	public function __construct() {
		$this->debug( 'ImageBlockExtension initialized' );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueueEditorAssets' ] );
	}

	/**
	 * Log debug message if WP_DEBUG is enabled.
	 *
	 * @param string $message The message to log.
	 */
	private function debug( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[AI Alt Text] ' . $message );
		}
	}

	/**
	 * Enqueue block editor assets.
	 */
	public function enqueueEditorAssets(): void {
		$this->debug( 'enqueueEditorAssets called' );

		$plugin_dir = dirname( __DIR__, 3 ); // Goes up from src/php/Blocks to plugin root
		$asset_file = $plugin_dir . '/build/image-toolbar/index.asset.php';

		$this->debug( 'Plugin dir: ' . $plugin_dir );
		$this->debug( 'Asset file: ' . $asset_file );
		$this->debug( 'Asset file exists: ' . ( file_exists( $asset_file ) ? 'yes' : 'no' ) );

		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;
		} else {
			$asset = [
				'dependencies' => [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-hooks', 'wp-compose', 'wp-data' ],
				'version'      => '1.0.0',
			];
		}

		$script_path = $plugin_dir . '/build/image-toolbar/index.js';

		$this->debug( 'Script path: ' . $script_path );
		$this->debug( 'Script file exists: ' . ( file_exists( $script_path ) ? 'yes' : 'no' ) );

		if ( file_exists( $script_path ) ) {
			$script_url = plugins_url( 'build/image-toolbar/index.js', $plugin_dir . '/ai-alt-text.php' );
			$this->debug( 'Enqueueing script from: ' . $script_url );
			wp_enqueue_script(
				'ai-alt-text-image-toolbar',
				plugins_url( 'build/image-toolbar/index.js', $plugin_dir . '/ai-alt-text.php' ),
				$asset[ 'dependencies' ],
				$asset[ 'version' ],
				true
			);
			$this->debug( 'Script enqueued successfully' );
		} else {
			// Fallback: inline script if build doesn't exist
			$this->debug( 'Build not found, using inline script fallback' );
			wp_add_inline_script( 'wp-edit-post', $this->getInlineScript(), 'after' );
		}

		wp_localize_script( 'ai-alt-text-image-toolbar', 'aiAltTextConfig', [
			'restUrl' => rest_url( 'ai-alt-text/v1/generate' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'i18n'    => [
				'generateAltText' => __( 'Generate AI Alt Text', 'ai-alt-text' ),
				'generating'      => __( 'Generating...', 'ai-alt-text' ),
				'success'         => __( 'Alt text generated!', 'ai-alt-text' ),
				'error'           => __( 'Failed to generate alt text', 'ai-alt-text' ),
				'noImage'         => __( 'No image selected', 'ai-alt-text' ),
				'buttonLabel'     => __( 'AI Alt Text', 'ai-alt-text' ),
			],
		] );
	}

	/**
	 * Get inline script as fallback.
	 *
	 * @return string The JavaScript code.
	 */
	private function getInlineScript(): string {
		return <<<'JS'
(function() {
	const { addFilter } = wp.hooks;
	const { createHigherOrderComponent } = wp.compose;
	const { Fragment, createElement: el } = wp.element;
	const { BlockControls } = wp.blockEditor;
	const { ToolbarGroup, ToolbarButton, Spinner } = wp.components;
	const { useState } = wp.element;
	const { useDispatch } = wp.data;

	const withAIAltTextButton = createHigherOrderComponent((BlockEdit) => {
		return function(props) {
			const [isGenerating, setIsGenerating] = useState(false);
			const { updateBlockAttributes } = useDispatch('core/block-editor');

			if (props.name !== 'core/image') {
				return el(BlockEdit, props);
			}

			const { attributes, setAttributes, clientId } = props;
			const { id, url, alt } = attributes;

			const generateAltText = async () => {
				if (!url && !id) {
					alert(window.aiAltTextConfig?.i18n?.noImage || 'No image selected');
					return;
				}

				setIsGenerating(true);

				try {
					const response = await fetch(window.aiAltTextConfig?.restUrl || '/wp-json/ai-alt-text/v1/generate', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': window.aiAltTextConfig?.nonce || ''
						},
						body: JSON.stringify({
							attachment_id: id || null,
							image_url: id ? null : url,
							overwrite: true
						})
					});

					const data = await response.json();

					if (data.success && data.alt_text) {
						setAttributes({ alt: data.alt_text });
					} else {
						throw new Error(data.message || 'Failed to generate');
					}
				} catch (error) {
					console.error('AI Alt Text Error:', error);
					alert((window.aiAltTextConfig?.i18n?.error || 'Error') + ': ' + error.message);
				} finally {
					setIsGenerating(false);
				}
			};

			return el(
				Fragment,
				null,
				el(BlockEdit, props),
				el(
					BlockControls,
					{ group: 'other' },
					el(
						ToolbarGroup,
						null,
						el(
							ToolbarButton,
							{
								icon: isGenerating ? el(Spinner) : 'format-image',
								label: window.aiAltTextConfig?.i18n?.buttonLabel || 'AI Alt Text',
								onClick: generateAltText,
								disabled: isGenerating || (!url && !id)
							}
						)
					)
				)
			);
		};
	}, 'withAIAltTextButton');

	addFilter(
		'editor.BlockEdit',
		'ai-alt-text/image-toolbar',
		withAIAltTextButton
	);
})();
JS;
	}
}
