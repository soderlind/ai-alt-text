<?php
/**
 * Media Library Handler - Auto-generate alt text on image upload.
 *
 * @package AiAltText\Media
 */

declare(strict_types=1);

namespace AiAltText\Media;

use AiAltText\Admin\SettingsPage;
use AiAltText\Services\AltTextService;

/**
 * Handles automatic alt text generation when images are uploaded.
 */
class MediaLibraryHandler {

	private AltTextService $alt_text_service;

	public function __construct() {
		$this->alt_text_service = new AltTextService();

		// Hook into attachment upload
		add_action( 'add_attachment', [ $this, 'onAttachmentAdded' ] );

		// Add AJAX handler for manual generation from media library
		add_action( 'wp_ajax_ai_alt_text_generate', [ $this, 'ajaxGenerateAltText' ] );
	}

	/**
	 * Handle new attachment upload.
	 *
	 * @param int $attachment_id The attachment ID.
	 */
	public function onAttachmentAdded( int $attachment_id ): void {
		// Check if auto-generate is enabled
		if ( SettingsPage::get_auto_generate() !== '1' ) {
			return;
		}

		// Check if it's an image
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		// Generate alt text in a non-blocking way
		// Schedule for immediate execution to avoid blocking the upload
		if ( ! wp_next_scheduled( 'ai_alt_text_generate_async', [ $attachment_id ] ) ) {
			wp_schedule_single_event( time(), 'ai_alt_text_generate_async', [ $attachment_id ] );
		}

		// Also register the cron action
		add_action( 'ai_alt_text_generate_async', [ $this, 'generateAltTextAsync' ] );
	}

	/**
	 * Generate alt text asynchronously via WP Cron.
	 *
	 * @param int $attachment_id The attachment ID.
	 */
	public function generateAltTextAsync( int $attachment_id ): void {
		try {
			$this->alt_text_service->generateAndSave( $attachment_id, false );
		} catch (\Exception $e) {
			// Log error but don't fail
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AI Alt Text generation failed for attachment ' . $attachment_id . ': ' . $e->getMessage() );
			}
		}
	}

	/**
	 * AJAX handler for generating alt text from media library.
	 */
	public function ajaxGenerateAltText(): void {
		check_ajax_referer( 'ai_alt_text_generate', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'ai-alt-text' ) ] );
		}

		$attachment_id = isset( $_POST[ 'attachment_id' ] ) ? absint( $_POST[ 'attachment_id' ] ) : 0;
		$overwrite     = isset( $_POST[ 'overwrite' ] ) && $_POST[ 'overwrite' ] === 'true';

		if ( ! $attachment_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid attachment ID.', 'ai-alt-text' ) ] );
		}

		try {
			$alt_text = $this->alt_text_service->generateAndSave( $attachment_id, $overwrite );
			wp_send_json_success( [
				'alt_text'      => $alt_text,
				'attachment_id' => $attachment_id,
			] );
		} catch (\Exception $e) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}
}
