<?php
/**
 * LLM Client for vision-capable AI providers.
 *
 * Supports image analysis via OpenAI, Azure OpenAI, Anthropic Claude, Google Gemini, Ollama, and Grok.
 *
 * @package AiAltText\AI
 */

declare(strict_types=1);

namespace AiAltText\AI;

use AiAltText\Admin\SettingsPage;
use RuntimeException;

/**
 * Client for sending vision requests to various AI providers.
 */
class LLMClient {

	/**
	 * Analyze an image and generate alt text.
	 *
	 * @param string $image_url  The URL of the image to analyze.
	 * @param string $prompt     The prompt for generating alt text.
	 * @param array  $options    Optional settings (max_tokens, temperature).
	 *
	 * @return string The generated alt text.
	 * @throws RuntimeException If configuration is incomplete or API returns an error.
	 */
	public function analyzeImage( string $image_url, string $prompt, array $options = [] ): string {
		$provider = SettingsPage::get_ai_provider();

		return match ( $provider ) {
			'openai'    => $this->analyzeWithOpenAI( $image_url, $prompt, $options ),
			'anthropic' => $this->analyzeWithAnthropic( $image_url, $prompt, $options ),
			'gemini'    => $this->analyzeWithGemini( $image_url, $prompt, $options ),
			'ollama'    => $this->analyzeWithOllama( $image_url, $prompt, $options ),
			'grok'      => $this->analyzeWithGrok( $image_url, $prompt, $options ),
			default     => throw new RuntimeException( "Unknown AI provider: {$provider}" ),
		};
	}

	/**
	 * Analyze image with OpenAI or Azure OpenAI.
	 */
	private function analyzeWithOpenAI( string $image_url, string $prompt, array $options ): string {
		$type  = SettingsPage::get_openai_type();
		$model = $options[ 'model' ] ?? SettingsPage::get_openai_model();
		$key   = SettingsPage::get_openai_key();

		if ( empty( $key ) || empty( $model ) ) {
			throw new RuntimeException( 'OpenAI configuration is incomplete. Please set API key and model.' );
		}

		$messages = [
			[
				'role'    => 'user',
				'content' => [
					[
						'type' => 'text',
						'text' => $prompt,
					],
					[
						'type'      => 'image_url',
						'image_url' => [
							'url' => $image_url,
						],
					],
				],
			],
		];

		$body = [
			'model'      => $model,
			'messages'   => $messages,
			'max_tokens' => $options[ 'max_tokens' ] ?? 300,
		];

		if ( $type === 'azure' ) {
			$endpoint    = SettingsPage::get_azure_endpoint();
			$api_version = SettingsPage::get_azure_api_version();
			if ( empty( $endpoint ) ) {
				throw new RuntimeException( 'Azure OpenAI endpoint is not configured.' );
			}

			$url     = rtrim( $endpoint, '/' ) . '/openai/deployments/' . $model . '/chat/completions?api-version=' . $api_version;
			$headers = [
				'Content-Type' => 'application/json',
				'api-key'      => $key,
			];
		} else {
			$url     = 'https://api.openai.com/v1/chat/completions';
			$headers = [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $key,
			];
		}

		$response = wp_remote_post( $url, [
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		] );

		return $this->parseOpenAIResponse( $response );
	}

	/**
	 * Analyze image with Anthropic Claude.
	 */
	private function analyzeWithAnthropic( string $image_url, string $prompt, array $options ): string {
		$api_key = SettingsPage::get_anthropic_key();
		$model   = $options[ 'model' ] ?? SettingsPage::get_anthropic_model();

		if ( empty( $api_key ) || empty( $model ) ) {
			throw new RuntimeException( 'Anthropic configuration is incomplete. Please set API key and model.' );
		}

		// Claude requires base64 encoded images, so we need to fetch and encode
		$image_data = $this->getImageAsBase64( $image_url );

		$messages = [
			[
				'role'    => 'user',
				'content' => [
					[
						'type'   => 'image',
						'source' => [
							'type'       => 'base64',
							'media_type' => $image_data[ 'mime_type' ],
							'data'       => $image_data[ 'data' ],
						],
					],
					[
						'type' => 'text',
						'text' => $prompt,
					],
				],
			],
		];

		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
			'headers' => [
				'Content-Type'      => 'application/json',
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
			],
			'body'    => wp_json_encode( [
				'model'      => $model,
				'max_tokens' => $options[ 'max_tokens' ] ?? 300,
				'messages'   => $messages,
			] ),
			'timeout' => 30,
		] );

		return $this->parseAnthropicResponse( $response );
	}

	/**
	 * Analyze image with Google Gemini.
	 */
	private function analyzeWithGemini( string $image_url, string $prompt, array $options ): string {
		$api_key = SettingsPage::get_gemini_key();
		$model   = $options[ 'model' ] ?? SettingsPage::get_gemini_model();

		if ( empty( $api_key ) || empty( $model ) ) {
			throw new RuntimeException( 'Gemini configuration is incomplete. Please set API key and model.' );
		}

		// Gemini requires base64 encoded images
		$image_data = $this->getImageAsBase64( $image_url );

		$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;

		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'contents' => [
					[
						'parts' => [
							[
								'inline_data' => [
									'mime_type' => $image_data[ 'mime_type' ],
									'data'      => $image_data[ 'data' ],
								],
							],
							[
								'text' => $prompt,
							],
						],
					],
				],
			] ),
			'timeout' => 30,
		] );

		return $this->parseGeminiResponse( $response );
	}

	/**
	 * Analyze image with Ollama (local).
	 */
	private function analyzeWithOllama( string $image_url, string $prompt, array $options ): string {
		$endpoint = SettingsPage::get_ollama_endpoint();
		$model    = $options[ 'model' ] ?? SettingsPage::get_ollama_model();

		if ( empty( $endpoint ) || empty( $model ) ) {
			throw new RuntimeException( 'Ollama configuration is incomplete. Please set endpoint and model.' );
		}

		// Ollama requires base64 encoded images
		$image_data = $this->getImageAsBase64( $image_url );

		$url = rtrim( $endpoint, '/' ) . '/api/generate';

		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'model'  => $model,
				'prompt' => $prompt,
				'images' => [ $image_data[ 'data' ] ],
				'stream' => false,
			] ),
			'timeout' => 60,
		] );

		return $this->parseOllamaResponse( $response );
	}

	/**
	 * Analyze image with Grok (xAI).
	 */
	private function analyzeWithGrok( string $image_url, string $prompt, array $options ): string {
		$api_key = SettingsPage::get_grok_key();
		$model   = $options[ 'model' ] ?? SettingsPage::get_grok_model();

		if ( empty( $api_key ) || empty( $model ) ) {
			throw new RuntimeException( 'Grok configuration is incomplete. Please set API key and model.' );
		}

		$messages = [
			[
				'role'    => 'user',
				'content' => [
					[
						'type' => 'text',
						'text' => $prompt,
					],
					[
						'type'      => 'image_url',
						'image_url' => [
							'url' => $image_url,
						],
					],
				],
			],
		];

		$response = wp_remote_post( 'https://api.x.ai/v1/chat/completions', [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			],
			'body'    => wp_json_encode( [
				'model'      => $model,
				'messages'   => $messages,
				'max_tokens' => $options[ 'max_tokens' ] ?? 300,
			] ),
			'timeout' => 30,
		] );

		return $this->parseOpenAIResponse( $response ); // Grok uses OpenAI-compatible format
	}

	/**
	 * Fetch an image and return as base64 encoded data.
	 *
	 * @param string $image_url The URL of the image.
	 * @return array{data: string, mime_type: string}
	 * @throws RuntimeException If image cannot be fetched.
	 */
	private function getImageAsBase64( string $image_url ): array {
		// Check if it's a local file
		$upload_dir = wp_upload_dir();
		$upload_url = $upload_dir[ 'baseurl' ];

		if ( strpos( $image_url, $upload_url ) === 0 ) {
			// Local file - read directly
			$file_path = str_replace( $upload_url, $upload_dir[ 'basedir' ], $image_url );
			if ( file_exists( $file_path ) ) {
				$data      = file_get_contents( $file_path );
				$mime_type = mime_content_type( $file_path ) ?: 'image/jpeg';
				return [
					'data'      => base64_encode( $data ),
					'mime_type' => $mime_type,
				];
			}
		}

		// Remote file - fetch via HTTP
		$response = wp_remote_get( $image_url, [ 'timeout' => 30 ] );

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'Failed to fetch image: ' . $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			throw new RuntimeException( 'Empty response when fetching image.' );
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$mime_type    = $content_type ?: 'image/jpeg';

		return [
			'data'      => base64_encode( $body ),
			'mime_type' => $mime_type,
		];
	}

	/**
	 * Parse OpenAI/Grok API response.
	 */
	private function parseOpenAIResponse( $response ): string {
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			$message = $data[ 'error' ][ 'message' ] ?? 'Unknown API error';
			throw new RuntimeException( $message );
		}

		$content = $data[ 'choices' ][ 0 ][ 'message' ][ 'content' ] ?? '';
		if ( ! is_string( $content ) || $content === '' ) {
			throw new RuntimeException( 'Empty response from model.' );
		}

		return trim( $content );
	}

	/**
	 * Parse Anthropic API response.
	 */
	private function parseAnthropicResponse( $response ): string {
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			$message = $data[ 'error' ][ 'message' ] ?? 'Unknown API error';
			throw new RuntimeException( $message );
		}

		$content = $data[ 'content' ][ 0 ][ 'text' ] ?? '';
		if ( ! is_string( $content ) || $content === '' ) {
			throw new RuntimeException( 'Empty response from Claude.' );
		}

		return trim( $content );
	}

	/**
	 * Parse Gemini API response.
	 */
	private function parseGeminiResponse( $response ): string {
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			$message = $data[ 'error' ][ 'message' ] ?? 'Unknown API error';
			throw new RuntimeException( $message );
		}

		$content = $data[ 'candidates' ][ 0 ][ 'content' ][ 'parts' ][ 0 ][ 'text' ] ?? '';
		if ( ! is_string( $content ) || $content === '' ) {
			throw new RuntimeException( 'Empty response from Gemini.' );
		}

		return trim( $content );
	}

	/**
	 * Parse Ollama API response.
	 */
	private function parseOllamaResponse( $response ): string {
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			$error = is_string( $data[ 'error' ] ) ? $data[ 'error' ] : ( $data[ 'error' ][ 'message' ] ?? 'Unknown error' );
			throw new RuntimeException( $error );
		}

		$content = $data[ 'response' ] ?? '';
		if ( ! is_string( $content ) || $content === '' ) {
			throw new RuntimeException( 'Empty response from Ollama.' );
		}

		return trim( $content );
	}
}
