<?php
/**
 * Alt Text Service - Core service for generating alt text from images.
 *
 * @package AiAltText\Services
 */

declare(strict_types=1);

namespace AiAltText\Services;

use AiAltText\AI\LLMClient;
use RuntimeException;

/**
 * Service class for generating alt text for images using AI.
 */
class AltTextService {

	private LLMClient $llm_client;

	public function __construct() {
		$this->llm_client = new LLMClient();
	}

	/**
	 * Generate alt text for an attachment.
	 *
	 * @param int $attachment_id The attachment ID.
	 * @return string The generated alt text.
	 * @throws RuntimeException If generation fails.
	 */
	public function generateForAttachment( int $attachment_id ): string {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			throw new RuntimeException( 'Attachment is not an image.' );
		}

		$image_url = wp_get_attachment_url( $attachment_id );
		if ( ! $image_url ) {
			throw new RuntimeException( 'Could not get attachment URL.' );
		}

		return $this->generateFromUrl( $image_url );
	}

	/**
	 * Generate alt text from an image URL.
	 *
	 * @param string $image_url The image URL.
	 * @return string The generated alt text.
	 * @throws RuntimeException If generation fails.
	 */
	public function generateFromUrl( string $image_url ): string {
		$language = $this->getLanguage();
		$prompt   = $this->buildPrompt( $language );

		return $this->llm_client->analyzeImage( $image_url, $prompt );
	}

	/**
	 * Update the alt text for an attachment.
	 *
	 * @param int    $attachment_id The attachment ID.
	 * @param string $alt_text      The alt text to set.
	 * @return bool True on success.
	 */
	public function updateAttachmentAltText( int $attachment_id, string $alt_text ): bool {
		return (bool) update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt_text ) );
	}

	/**
	 * Generate and save alt text for an attachment.
	 *
	 * @param int  $attachment_id The attachment ID.
	 * @param bool $overwrite     Whether to overwrite existing alt text.
	 * @return string The generated alt text.
	 * @throws RuntimeException If generation fails.
	 */
	public function generateAndSave( int $attachment_id, bool $overwrite = false ): string {
		// Check if alt text already exists
		if ( ! $overwrite ) {
			$existing = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			if ( ! empty( $existing ) ) {
				return $existing;
			}
		}

		$alt_text = $this->generateForAttachment( $attachment_id );
		$this->updateAttachmentAltText( $attachment_id, $alt_text );

		return $alt_text;
	}

	/**
	 * Get the language for alt text generation based on WordPress settings.
	 *
	 * @return string The language name (e.g., "English", "Norwegian").
	 */
	private function getLanguage(): string {
		$locale = get_locale();

		// Map common locales to language names
		$language_map = [
			'en_US' => 'English',
			'en_GB' => 'English',
			'en_AU' => 'English',
			'en_CA' => 'English',
			'nb_NO' => 'Norwegian',
			'nn_NO' => 'Norwegian Nynorsk',
			'sv_SE' => 'Swedish',
			'da_DK' => 'Danish',
			'fi'    => 'Finnish',
			'de_DE' => 'German',
			'de_AT' => 'German',
			'de_CH' => 'German',
			'fr_FR' => 'French',
			'fr_CA' => 'French',
			'es_ES' => 'Spanish',
			'es_MX' => 'Spanish',
			'it_IT' => 'Italian',
			'pt_BR' => 'Portuguese',
			'pt_PT' => 'Portuguese',
			'nl_NL' => 'Dutch',
			'pl_PL' => 'Polish',
			'ru_RU' => 'Russian',
			'ja'    => 'Japanese',
			'ko_KR' => 'Korean',
			'zh_CN' => 'Chinese (Simplified)',
			'zh_TW' => 'Chinese (Traditional)',
			'ar'    => 'Arabic',
			'he_IL' => 'Hebrew',
			'tr_TR' => 'Turkish',
			'cs_CZ' => 'Czech',
			'el'    => 'Greek',
			'hu_HU' => 'Hungarian',
			'ro_RO' => 'Romanian',
			'uk'    => 'Ukrainian',
			'vi'    => 'Vietnamese',
			'th'    => 'Thai',
			'id_ID' => 'Indonesian',
		];

		// Try exact match first
		if ( isset( $language_map[ $locale ] ) ) {
			return $language_map[ $locale ];
		}

		// Try language code only (first two characters)
		$lang_code = substr( $locale, 0, 2 );
		foreach ( $language_map as $key => $name ) {
			if ( strpos( $key, $lang_code ) === 0 ) {
				return $name;
			}
		}

		// Default to English
		return 'English';
	}

	/**
	 * Build the prompt for alt text generation.
	 *
	 * @param string $language The target language.
	 * @return string The prompt.
	 */
	private function buildPrompt( string $language ): string {
		return sprintf(
			'Generate a concise, descriptive alt text for this image in %s. ' .
			'The alt text should:\n' .
			'- Be 1-2 sentences maximum\n' .
			'- Describe the main subject and important details\n' .
			'- Be useful for screen readers\n' .
			'- Not start with "Image of" or "Picture of"\n' .
			'- Not include decorative descriptions unless relevant\n\n' .
			'Return only the alt text, nothing else.',
			$language
		);
	}
}
