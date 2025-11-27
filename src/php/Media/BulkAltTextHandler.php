<?php
/**
 * Bulk Alt Text Handler - Add bulk action to media library.
 *
 * @package AiAltText\Media
 */

declare(strict_types=1);

namespace AiAltText\Media;

use AiAltText\Services\AltTextService;

/**
 * Handles bulk alt text generation in the media library.
 */
class BulkAltTextHandler {

	private AltTextService $alt_text_service;

	public function __construct() {
		$this->alt_text_service = new AltTextService();

		// Add bulk action to media library
		add_filter( 'bulk_actions-upload', [ $this, 'addBulkAction' ] );
		add_filter( 'handle_bulk_actions-upload', [ $this, 'handleBulkAction' ], 10, 3 );

		// Add admin notice for bulk action results
		add_action( 'admin_notices', [ $this, 'showBulkActionNotice' ] );

		// Add "Generate Alt Text" link to media row actions
		add_filter( 'media_row_actions', [ $this, 'addRowAction' ], 10, 2 );

		// Enqueue admin scripts for media library
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminScripts' ] );
	}

	/**
	 * Add bulk action to media library dropdown.
	 *
	 * @param array $actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function addBulkAction( array $actions ): array {
		$actions[ 'ai_generate_alt_text' ] = __( 'Generate AI Alt Text', 'ai-alt-text' );
		return $actions;
	}

	/**
	 * Handle the bulk action.
	 *
	 * @param string $redirect_url The redirect URL.
	 * @param string $action       The action being performed.
	 * @param array  $post_ids     The selected post IDs.
	 * @return string The redirect URL.
	 */
	public function handleBulkAction( string $redirect_url, string $action, array $post_ids ): string {
		if ( $action !== 'ai_generate_alt_text' ) {
			return $redirect_url;
		}

		$success_count = 0;
		$error_count   = 0;
		$skipped_count = 0;

		foreach ( $post_ids as $post_id ) {
			$post_id = absint( $post_id );

			// Skip non-images
			if ( ! wp_attachment_is_image( $post_id ) ) {
				$skipped_count++;
				continue;
			}

			try {
				$this->alt_text_service->generateAndSave( $post_id, false );
				$success_count++;
			} catch (\Exception $e) {
				$error_count++;
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'AI Alt Text bulk generation failed for ' . $post_id . ': ' . $e->getMessage() );
				}
			}
		}

		// Add query args to redirect URL for admin notice
		$redirect_url = add_query_arg( [
			'ai_alt_text_bulk'    => 1,
			'ai_alt_text_success' => $success_count,
			'ai_alt_text_errors'  => $error_count,
			'ai_alt_text_skipped' => $skipped_count,
		], $redirect_url );

		return $redirect_url;
	}

	/**
	 * Show admin notice after bulk action.
	 */
	public function showBulkActionNotice(): void {
		if ( ! isset( $_GET[ 'ai_alt_text_bulk' ] ) ) {
			return;
		}

		$success = isset( $_GET[ 'ai_alt_text_success' ] ) ? absint( $_GET[ 'ai_alt_text_success' ] ) : 0;
		$errors  = isset( $_GET[ 'ai_alt_text_errors' ] ) ? absint( $_GET[ 'ai_alt_text_errors' ] ) : 0;
		$skipped = isset( $_GET[ 'ai_alt_text_skipped' ] ) ? absint( $_GET[ 'ai_alt_text_skipped' ] ) : 0;

		$messages = [];

		if ( $success > 0 ) {
			$messages[] = sprintf(
				/* translators: %d: number of images */
				_n(
					'Generated alt text for %d image.',
					'Generated alt text for %d images.',
					$success,
					'ai-alt-text'
				),
				$success
			);
		}

		if ( $errors > 0 ) {
			$messages[] = sprintf(
				/* translators: %d: number of images */
				_n(
					'Failed to generate alt text for %d image.',
					'Failed to generate alt text for %d images.',
					$errors,
					'ai-alt-text'
				),
				$errors
			);
		}

		if ( $skipped > 0 ) {
			$messages[] = sprintf(
				/* translators: %d: number of items */
				_n(
					'Skipped %d non-image attachment.',
					'Skipped %d non-image attachments.',
					$skipped,
					'ai-alt-text'
				),
				$skipped
			);
		}

		if ( ! empty( $messages ) ) {
			$class = $errors > 0 ? 'notice-warning' : 'notice-success';
			printf(
				'<div class="notice %s is-dismissible"><p>%s</p></div>',
				esc_attr( $class ),
				esc_html( implode( ' ', $messages ) )
			);
		}
	}

	/**
	 * Add "Generate Alt Text" link to media row actions.
	 *
	 * @param array    $actions Existing row actions.
	 * @param \WP_Post $post    The post object.
	 * @return array Modified row actions.
	 */
	public function addRowAction( array $actions, \WP_Post $post ): array {
		if ( ! wp_attachment_is_image( $post->ID ) ) {
			return $actions;
		}

		$nonce = wp_create_nonce( 'ai_alt_text_generate' );
		$url   = admin_url( 'admin-ajax.php' ) . '?' . http_build_query( [
			'action'        => 'ai_alt_text_generate',
			'attachment_id' => $post->ID,
			'overwrite'     => 'true',
			'nonce'         => $nonce,
		] );

		$actions[ 'ai_generate_alt' ] = sprintf(
			'<a href="#" class="ai-generate-alt-text" data-id="%d" data-nonce="%s">%s</a>',
			esc_attr( $post->ID ),
			esc_attr( $nonce ),
			esc_html__( 'Generate AI Alt Text', 'ai-alt-text' )
		);

		return $actions;
	}

	/**
	 * Enqueue admin scripts for media library.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueueAdminScripts( string $hook ): void {
		if ( $hook !== 'upload.php' ) {
			return;
		}

		wp_add_inline_script( 'jquery', $this->getInlineScript() );
	}

	/**
	 * Get inline JavaScript for media library.
	 *
	 * @return string The JavaScript code.
	 */
	private function getInlineScript(): string {
		return <<<'JS'
jQuery(document).ready(function($) {
	$(document).on('click', '.ai-generate-alt-text', function(e) {
		e.preventDefault();
		
		var $link = $(this);
		var attachmentId = $link.data('id');
		var nonce = $link.data('nonce');
		var originalText = $link.text();
		
		$link.text('Generating...');
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'ai_alt_text_generate',
				attachment_id: attachmentId,
				overwrite: 'true',
				nonce: nonce
			},
			success: function(response) {
				if (response.success) {
					$link.text('Done!');
					setTimeout(function() {
						$link.text(originalText);
					}, 2000);
				} else {
					alert('Error: ' + (response.data.message || 'Unknown error'));
					$link.text(originalText);
				}
			},
			error: function() {
				alert('Failed to generate alt text. Please try again.');
				$link.text(originalText);
			}
		});
	});
});
JS;
	}
}
