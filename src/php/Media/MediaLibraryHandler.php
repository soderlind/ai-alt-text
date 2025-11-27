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

		// Enqueue scripts for media edit screen
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueMediaEditScripts' ] );

		// Print inline script in admin footer
		add_action( 'admin_footer', [ $this, 'printMediaEditScript' ] );
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

	/**
	 * Check if we should load media scripts on this page.
	 *
	 * @return bool
	 */
	private function shouldLoadMediaScripts(): bool {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		// Load on media library grid view
		if ( $screen->base === 'upload' ) {
			return true;
		}

		// Load on single attachment edit page
		if ( $screen->base === 'post' && $screen->post_type === 'attachment' ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue styles for media edit screen.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueueMediaEditScripts( string $hook ): void {
		if ( ! $this->shouldLoadMediaScripts() ) {
			return;
		}

		wp_add_inline_style( 'wp-admin', $this->getMediaEditStyles() );
	}

	/**
	 * Print inline JavaScript in admin footer.
	 */
	public function printMediaEditScript(): void {
		if ( ! $this->shouldLoadMediaScripts() ) {
			return;
		}

		$data = [
			'nonce'      => wp_create_nonce( 'ai_alt_text_generate' ),
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'buttonText' => __( 'Generate with AI', 'ai-alt-text' ),
			'generating' => __( 'Generating...', 'ai-alt-text' ),
		];
		?>
		<script type="text/javascript">
			var aiAltText = <?php echo wp_json_encode( $data ); ?>;
			<?php echo $this->getMediaEditScript(); ?>
		</script>
		<?php
	}

	/**
	 * Get inline JavaScript for media edit screen.
	 *
	 * @return string The JavaScript code.
	 */
	private function getMediaEditScript(): string {
		return <<<'JS'
(function($) {
	var buttonHtml = '<button type="button" class="button button-small ai-generate-alt-text-btn">' + 
		aiAltText.buttonText + '</button><span class="ai-alt-text-status"></span>';
	
	// Function to inject button below alt text field on single edit page (left-aligned)
	function injectButtonSingleEdit($field, attachmentId) {
		if ($field.parent().find('.ai-generate-alt-text-btn').length) {
			return; // Already has button
		}
		
		var $wrapper = $('<div class="ai-alt-text-single-wrapper"></div>');
		$wrapper.html(buttonHtml);
		$wrapper.find('.ai-generate-alt-text-btn').attr('data-id', attachmentId);
		$field.after($wrapper);
	}
	
	// Single media edit page: inject next to alt text input
	function initSingleEditPage() {
		// Try the standard WP attachment edit field
		$('input[name^="attachments"][name$="[_wp_attachment_image_alt]"]').each(function() {
			var $input = $(this);
			var name = $input.attr('name');
			var match = name.match(/attachments\[(\d+)\]/);
			if (match) {
				injectButtonSingleEdit($input, match[1]);
			}
		});
		
		// Also try the post meta box alt text field (some setups)
		$('#attachment_alt').each(function() {
			var $input = $(this);
			var postId = $('#post_ID').val();
			if (postId) {
				injectButtonSingleEdit($input, postId);
			}
		});
	}
	
	// Media library grid modal: inject next to alt text input
	function initGridModal() {
		var $altSetting = $('.attachment-details .setting[data-setting="alt"]');
		if (!$altSetting.length) {
			return;
		}
		
		if ($altSetting.find('.ai-generate-alt-text-btn').length) {
			return; // Already has button
		}
		
		// Get attachment ID from the model or data attribute
		var attachmentId = null;
		var $details = $altSetting.closest('.attachment-details');
		
		// Try data-id on details
		if ($details.data('id')) {
			attachmentId = $details.data('id');
		}
		
		// Try to get from Backbone model
		if (!attachmentId && typeof wp !== 'undefined' && wp.media && wp.media.frame) {
			var state = wp.media.frame.state();
			if (state && state.get('selection')) {
				var selection = state.get('selection').first();
				if (selection) {
					attachmentId = selection.get('id');
				}
			}
		}
		
		// Fallback: try URL
		if (!attachmentId) {
			var match = window.location.href.match(/item=(\d+)/);
			if (match) {
				attachmentId = match[1];
			}
		}
		
		if (attachmentId) {
			var $btnWrapper = $('<div class="copy-to-clipboard-container ai-alt-text-grid-wrapper"></div>');
			$btnWrapper.html(buttonHtml);
			$btnWrapper.find('.ai-generate-alt-text-btn').attr('data-id', attachmentId);
			$altSetting.append($btnWrapper);
		}
	}
	
	// Initialize on document ready
	$(function() {
		initSingleEditPage();
		
		// For grid view, wait a bit for media library to initialize
		setTimeout(initGridModal, 500);
	});
	
	// Watch for media modal changes using MutationObserver
	var observer = new MutationObserver(function(mutations) {
		mutations.forEach(function(mutation) {
			if (mutation.addedNodes.length) {
				setTimeout(initGridModal, 100);
			}
		});
	});
	
	// Start observing when document is ready
	$(function() {
		var target = document.querySelector('.media-modal-content') || document.body;
		observer.observe(target, { childList: true, subtree: true });
	});
	
	// Also listen for wp.media events if available
	$(document).on('click', '.attachment-preview, .attachment, .thumbnail', function() {
		setTimeout(initGridModal, 200);
	});
	
	// Handle button click
	$(document).on('click', '.ai-generate-alt-text-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		var $button = $(this);
		var $status = $button.siblings('.ai-alt-text-status');
		if (!$status.length) {
			$status = $button.parent().find('.ai-alt-text-status');
		}
		var attachmentId = $button.data('id');
		var originalText = $button.text();
		
		if (!attachmentId) {
			$status.text('✗ No image').addClass('error');
			return;
		}
		
		$button.prop('disabled', true).text(aiAltText.generating);
		$status.text('').removeClass('success error');
		
		$.ajax({
			url: aiAltText.ajaxUrl,
			type: 'POST',
			data: {
				action: 'ai_alt_text_generate',
				attachment_id: attachmentId,
				overwrite: 'true',
				nonce: aiAltText.nonce
			},
			success: function(response) {
				if (response.success) {
					// Update single edit page alt text field
					var $altField = $('input[name="attachments[' + attachmentId + '][_wp_attachment_image_alt]"]');
					if ($altField.length) {
						$altField.val(response.data.alt_text);
					}
					
					// Try #attachment_alt field
					var $altMeta = $('#attachment_alt');
					if ($altMeta.length) {
						$altMeta.val(response.data.alt_text);
					}
					
					// Update grid modal alt field
					var $modalAlt = $('.attachment-details .setting[data-setting="alt"] input, .attachment-details .setting[data-setting="alt"] textarea');
					if ($modalAlt.length) {
						$modalAlt.val(response.data.alt_text).trigger('change');
						
						// Also update the Backbone model if available
						if (typeof wp !== 'undefined' && wp.media && wp.media.frame) {
							var state = wp.media.frame.state();
							if (state && state.get('selection')) {
								var selection = state.get('selection').first();
								if (selection) {
									selection.set('alt', response.data.alt_text);
								}
							}
						}
					}
					
					$status.text('✓').addClass('success');
					setTimeout(function() { $status.text('').removeClass('success'); }, 2000);
				} else {
					$status.text('✗ ' + (response.data.message || 'Error')).addClass('error');
				}
				$button.prop('disabled', false).text(originalText);
			},
			error: function(xhr, status, error) {
				$status.text('✗ ' + error).addClass('error');
				$button.prop('disabled', false).text(originalText);
			}
		});
	});
})(jQuery);
JS;
	}

	/**
	 * Get inline CSS for media edit screen.
	 *
	 * @return string The CSS code.
	 */
	private function getMediaEditStyles(): string {
		return <<<'CSS'
/* Single edit page styling - left aligned below field */
.ai-alt-text-single-wrapper {
	display: block;
	margin-top: 8px;
	text-align: left;
}
.ai-alt-text-single-wrapper .ai-generate-alt-text-btn {
	margin-right: 8px;
}
/* Common button and status styling */
.ai-generate-alt-text-btn {
	vertical-align: middle;
}
.ai-alt-text-status {
	display: inline-block;
	margin-left: 6px;
	font-weight: 500;
	vertical-align: middle;
}
.ai-alt-text-status.success {
	color: #00a32a;
}
.ai-alt-text-status.error {
	color: #d63638;
}
/* Grid modal - uses WP copy-to-clipboard-container for right alignment */
.ai-alt-text-grid-wrapper .ai-alt-text-status {
	margin-left: 8px;
}
CSS;
	}
}
