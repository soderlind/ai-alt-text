<?php

declare(strict_types=1);

namespace AiAltText\Admin;

/**
 * Settings page for AI Alt Text plugin.
 * Provides configuration for AI providers (OpenAI, Azure, Anthropic, Gemini, Ollama, Grok).
 */
class SettingsPage {
	private string $option_group = 'ai_alt_text_options_group';
	private string $option_name = 'ai_alt_text_options';

	/**
	 * Map of setting keys to their environment variable and constant names.
	 *
	 * @var array<string, array{env: string, const: string, default: mixed}>
	 */
	private static array $config_map = [
		'ai_provider'       => [ 'env' => 'AI_ALT_TEXT_AI_PROVIDER', 'const' => 'AI_ALT_TEXT_AI_PROVIDER', 'default' => 'openai' ],
		'auto_generate'     => [ 'env' => 'AI_ALT_TEXT_AUTO_GENERATE', 'const' => 'AI_ALT_TEXT_AUTO_GENERATE', 'default' => '1' ],
		'openai_type'       => [ 'env' => 'AI_ALT_TEXT_OPENAI_TYPE', 'const' => 'AI_ALT_TEXT_OPENAI_TYPE', 'default' => 'openai' ],
		'openai_key'        => [ 'env' => 'AI_ALT_TEXT_OPENAI_KEY', 'const' => 'AI_ALT_TEXT_OPENAI_KEY', 'default' => '' ],
		'openai_model'      => [ 'env' => 'AI_ALT_TEXT_OPENAI_MODEL', 'const' => 'AI_ALT_TEXT_OPENAI_MODEL', 'default' => 'gpt-4o' ],
		'azure_endpoint'    => [ 'env' => 'AI_ALT_TEXT_AZURE_ENDPOINT', 'const' => 'AI_ALT_TEXT_AZURE_ENDPOINT', 'default' => '' ],
		'azure_api_version' => [ 'env' => 'AI_ALT_TEXT_AZURE_API_VERSION', 'const' => 'AI_ALT_TEXT_AZURE_API_VERSION', 'default' => '2024-02-15-preview' ],
		'anthropic_key'     => [ 'env' => 'AI_ALT_TEXT_ANTHROPIC_KEY', 'const' => 'AI_ALT_TEXT_ANTHROPIC_KEY', 'default' => '' ],
		'anthropic_model'   => [ 'env' => 'AI_ALT_TEXT_ANTHROPIC_MODEL', 'const' => 'AI_ALT_TEXT_ANTHROPIC_MODEL', 'default' => 'claude-3-5-sonnet-20241022' ],
		'gemini_key'        => [ 'env' => 'AI_ALT_TEXT_GEMINI_KEY', 'const' => 'AI_ALT_TEXT_GEMINI_KEY', 'default' => '' ],
		'gemini_model'      => [ 'env' => 'AI_ALT_TEXT_GEMINI_MODEL', 'const' => 'AI_ALT_TEXT_GEMINI_MODEL', 'default' => 'gemini-1.5-flash' ],
		'ollama_endpoint'   => [ 'env' => 'AI_ALT_TEXT_OLLAMA_ENDPOINT', 'const' => 'AI_ALT_TEXT_OLLAMA_ENDPOINT', 'default' => 'http://localhost:11434' ],
		'ollama_model'      => [ 'env' => 'AI_ALT_TEXT_OLLAMA_MODEL', 'const' => 'AI_ALT_TEXT_OLLAMA_MODEL', 'default' => 'llava' ],
		'grok_key'          => [ 'env' => 'AI_ALT_TEXT_GROK_KEY', 'const' => 'AI_ALT_TEXT_GROK_KEY', 'default' => '' ],
		'grok_model'        => [ 'env' => 'AI_ALT_TEXT_GROK_MODEL', 'const' => 'AI_ALT_TEXT_GROK_MODEL', 'default' => 'grok-2-vision-1212' ],
	];

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function add_settings_page(): void {
		add_options_page(
			__( 'AI Alt Text Settings', 'ai-alt-text' ),
			__( 'AI Alt Text', 'ai-alt-text' ),
			'manage_options',
			'ai-alt-text-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings(): void {
		register_setting( $this->option_group, $this->option_name, [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_settings' ],
			'default'           => [
				'ai_provider'       => 'openai',
				'auto_generate'     => '1',
				'openai_type'       => 'openai',
				'openai_key'        => '',
				'openai_model'      => 'gpt-4o',
				'azure_endpoint'    => '',
				'azure_api_version' => '2024-02-15-preview',
				'anthropic_key'     => '',
				'anthropic_model'   => 'claude-3-5-sonnet-20241022',
				'gemini_key'        => '',
				'gemini_model'      => 'gemini-1.5-flash',
				'ollama_endpoint'   => 'http://localhost:11434',
				'ollama_model'      => 'llava',
				'grok_key'          => '',
				'grok_model'        => 'grok-2-vision-1212',
			],
		] );

		add_settings_section(
			'ai_alt_text_general_section',
			__( 'General Settings', 'ai-alt-text' ),
			[ $this, 'render_general_section_description' ],
			'ai-alt-text-settings'
		);

		add_settings_field(
			'auto_generate',
			__( 'Auto-generate on Upload', 'ai-alt-text' ),
			[ $this, 'render_auto_generate_field' ],
			'ai-alt-text-settings',
			'ai_alt_text_general_section'
		);

		add_settings_section(
			'ai_alt_text_ai_section',
			__( 'AI Provider Settings', 'ai-alt-text' ),
			[ $this, 'render_ai_section_description' ],
			'ai-alt-text-settings'
		);

		add_settings_field(
			'ai_provider',
			__( 'AI Provider', 'ai-alt-text' ),
			[ $this, 'render_ai_provider_field' ],
			'ai-alt-text-settings',
			'ai_alt_text_ai_section'
		);

		add_settings_field(
			'openai_type',
			__( 'OpenAI Type', 'ai-alt-text' ),
			[ $this, 'render_openai_type_field' ],
			'ai-alt-text-settings',
			'ai_alt_text_ai_section'
		);

		add_settings_field(
			'openai_key',
			__( 'API Key', 'ai-alt-text' ),
			[ $this, 'render_openai_key_field' ],
			'ai-alt-text-settings',
			'ai_alt_text_ai_section'
		);

		add_settings_field(
			'openai_model',
			__( 'Model / Deployment', 'ai-alt-text' ),
			[ $this, 'render_openai_model_field' ],
			'ai-alt-text-settings',
			'ai_alt_text_ai_section'
		);

		add_settings_field(
			'azure_endpoint',
			__( 'Azure OpenAI Endpoint', 'ai-alt-text' ),
			[ $this, 'render_azure_endpoint_field' ],
			'ai-alt-text-settings',
			'ai_alt_text_ai_section'
		);

		add_settings_field(
			'azure_api_version',
			__( 'Azure API Version', 'ai-alt-text' ),
			[ $this, 'render_azure_api_version_field' ],
			'ai-alt-text-settings',
			'ai_alt_text_ai_section'
		);
	}

	public function sanitize_settings( $input ): array {
		$sanitized = [];

		$sanitized[ 'ai_provider' ] = isset( $input[ 'ai_provider' ] ) && in_array( $input[ 'ai_provider' ], [ 'openai', 'anthropic', 'gemini', 'ollama', 'grok' ], true )
			? $input[ 'ai_provider' ]
			: 'openai';

		$sanitized[ 'auto_generate' ] = isset( $input[ 'auto_generate' ] ) ? '1' : '0';

		$sanitized[ 'openai_type' ] = isset( $input[ 'openai_type' ] ) && in_array( $input[ 'openai_type' ], [ 'openai', 'azure' ], true )
			? $input[ 'openai_type' ]
			: 'openai';

		$sanitized[ 'openai_key' ]        = isset( $input[ 'openai_key' ] ) ? sanitize_text_field( $input[ 'openai_key' ] ) : '';
		$sanitized[ 'openai_model' ]      = isset( $input[ 'openai_model' ] ) ? sanitize_text_field( $input[ 'openai_model' ] ) : 'gpt-4o';
		$sanitized[ 'azure_endpoint' ]    = isset( $input[ 'azure_endpoint' ] ) ? esc_url_raw( $input[ 'azure_endpoint' ] ) : '';
		$sanitized[ 'azure_api_version' ] = isset( $input[ 'azure_api_version' ] ) ? sanitize_text_field( $input[ 'azure_api_version' ] ) : '2024-02-15-preview';
		$sanitized[ 'anthropic_key' ]     = isset( $input[ 'anthropic_key' ] ) ? sanitize_text_field( $input[ 'anthropic_key' ] ) : '';
		$sanitized[ 'anthropic_model' ]   = isset( $input[ 'anthropic_model' ] ) ? sanitize_text_field( $input[ 'anthropic_model' ] ) : 'claude-3-5-sonnet-20241022';
		$sanitized[ 'gemini_key' ]        = isset( $input[ 'gemini_key' ] ) ? sanitize_text_field( $input[ 'gemini_key' ] ) : '';
		$sanitized[ 'gemini_model' ]      = isset( $input[ 'gemini_model' ] ) ? sanitize_text_field( $input[ 'gemini_model' ] ) : 'gemini-1.5-flash';
		$sanitized[ 'ollama_endpoint' ]   = isset( $input[ 'ollama_endpoint' ] ) ? esc_url_raw( $input[ 'ollama_endpoint' ] ) : 'http://localhost:11434';
		$sanitized[ 'ollama_model' ]      = isset( $input[ 'ollama_model' ] ) ? sanitize_text_field( $input[ 'ollama_model' ] ) : 'llava';
		$sanitized[ 'grok_key' ]          = isset( $input[ 'grok_key' ] ) ? sanitize_text_field( $input[ 'grok_key' ] ) : '';
		$sanitized[ 'grok_model' ]        = isset( $input[ 'grok_model' ] ) ? sanitize_text_field( $input[ 'grok_model' ] ) : 'grok-2-vision-1212';

		// Validate AI provider configuration
		$this->validate_ai_configuration( $sanitized );

		return $sanitized;
	}

	/**
	 * Validate AI configuration by attempting a test request
	 */
	private function validate_ai_configuration( array $settings ): void {
		$provider = $settings[ 'ai_provider' ];
		$error    = null;

		switch ( $provider ) {
			case 'openai':
				$error = $this->test_openai( $settings );
				break;
			case 'anthropic':
				$error = $this->test_anthropic( $settings );
				break;
			case 'gemini':
				$error = $this->test_gemini( $settings );
				break;
			case 'ollama':
				$error = $this->test_ollama( $settings );
				break;
			case 'grok':
				$error = $this->test_grok( $settings );
				break;
		}

		if ( $error ) {
			$existing = get_settings_errors();
			foreach ( $existing as $ex ) {
				if ( isset( $ex[ 'code' ] ) && $ex[ 'code' ] === 'ai_validation_error' ) {
					return;
				}
			}
			add_settings_error(
				'ai_alt_text_messages',
				'ai_validation_error',
				/* translators: %s: Error message from AI provider validation */
				sprintf( __( 'AI Configuration Warning: %s', 'ai-alt-text' ), $error ),
				'error'
			);
		}
	}

	private function test_openai( array $settings ): ?string {
		$api_key = $settings[ 'openai_key' ];
		$model   = $settings[ 'openai_model' ];
		$type    = $settings[ 'openai_type' ];

		if ( empty( $api_key ) || empty( $model ) ) {
			return null;
		}

		if ( $type === 'azure' ) {
			$endpoint = $settings[ 'azure_endpoint' ];
			if ( empty( $endpoint ) ) {
				return null;
			}
			$url     = rtrim( $endpoint, '/' ) . '/openai/deployments/' . $model . '/chat/completions?api-version=' . $settings[ 'azure_api_version' ];
			$headers = [
				'Content-Type' => 'application/json',
				'api-key'      => $api_key,
			];
		} else {
			$url     = 'https://api.openai.com/v1/chat/completions';
			$headers = [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			];
		}

		$response = wp_remote_post( $url, [
			'headers' => $headers,
			'body'    => wp_json_encode( [
				'model'      => $model,
				'messages'   => [ [ 'role' => 'user', 'content' => 'test' ] ],
				'max_tokens' => 5,
			] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			return $data[ 'error' ][ 'message' ] ?? 'Unknown error';
		}

		return null;
	}

	private function test_anthropic( array $settings ): ?string {
		$api_key = $settings[ 'anthropic_key' ];
		$model   = $settings[ 'anthropic_model' ];

		if ( empty( $api_key ) || empty( $model ) ) {
			return null;
		}

		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
			'headers' => [
				'Content-Type'      => 'application/json',
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
			],
			'body'    => wp_json_encode( [
				'model'      => $model,
				'max_tokens' => 5,
				'messages'   => [ [ 'role' => 'user', 'content' => 'test' ] ],
			] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			return $data[ 'error' ][ 'message' ] ?? 'Unknown error';
		}

		return null;
	}

	private function test_gemini( array $settings ): ?string {
		$api_key = $settings[ 'gemini_key' ];
		$model   = $settings[ 'gemini_model' ];

		if ( empty( $api_key ) || empty( $model ) ) {
			return null;
		}

		$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;

		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'contents' => [ [ 'parts' => [ [ 'text' => 'test' ] ] ] ],
			] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			return $data[ 'error' ][ 'message' ] ?? 'Unknown error';
		}

		return null;
	}

	private function test_ollama( array $settings ): ?string {
		$endpoint = $settings[ 'ollama_endpoint' ];
		$model    = $settings[ 'ollama_model' ];

		if ( empty( $endpoint ) || empty( $model ) ) {
			return null;
		}

		$url = rtrim( $endpoint, '/' ) . '/api/generate';

		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'model'  => $model,
				'prompt' => 'test',
				'stream' => false,
			] ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			return is_string( $data[ 'error' ] ) ? $data[ 'error' ] : ( $data[ 'error' ][ 'message' ] ?? 'Unknown error' );
		}

		return null;
	}

	private function test_grok( array $settings ): ?string {
		$api_key = $settings[ 'grok_key' ] ?? '';
		$model   = $settings[ 'grok_model' ] ?? 'grok-2-vision-1212';

		if ( empty( $api_key ) || empty( $model ) ) {
			return null;
		}

		$response = wp_remote_post( 'https://api.x.ai/v1/chat/completions', [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			],
			'body'    => wp_json_encode( [
				'model'      => $model,
				'messages'   => [ [ 'role' => 'user', 'content' => 'test' ] ],
				'max_tokens' => 5,
			] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			return $data[ 'error' ][ 'message' ] ?? 'Unknown error';
		}

		return null;
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( $this->option_group );
				do_settings_sections( 'ai-alt-text-settings' );
				submit_button( __( 'Save Settings', 'ai-alt-text' ) );
				?>
			</form>
		</div>
		<?php
	}

	public function render_general_section_description(): void {
		?>
		<p><?php esc_html_e( 'Configure general behavior for AI Alt Text generation.', 'ai-alt-text' ); ?></p>
		<?php
	}

	public function render_auto_generate_field(): void {
		$current     = self::get_auto_generate();
		$is_external = self::is_externally_defined( 'auto_generate' );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[auto_generate]" id="auto_generate"
				value="1" <?php checked( $current, '1' ); ?> 		<?php echo $is_external ? 'disabled' : ''; ?> />
			<?php esc_html_e( 'Automatically generate alt text when images are uploaded to the media library', 'ai-alt-text' ); ?>
		</label>
		<?php if ( $is_external ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $this->option_name ); ?>[auto_generate]"
				value="<?php echo esc_attr( $current ); ?>" />
		<?php endif; ?>
		<?php $this->render_external_indicator( 'auto_generate' ); ?>
	<?php
	}

	public function render_ai_section_description(): void {
		?>
		<p><?php esc_html_e( 'Configure the AI service used for generating image alt text. Vision-capable models are required.', 'ai-alt-text' ); ?>
		</p>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				const providerSelect = document.getElementById('ai_provider');
				const typeSelect = document.getElementById('openai_type');
				const azureFields = document.querySelectorAll('#azure_endpoint, #azure_api_version');
				const apiKeyField = document.getElementById('openai_key');
				const modelField = document.getElementById('openai_model');
				const modelLabel = modelField ? modelField.closest('tr').querySelector('th label') : null;

				function updateFieldVisibility() {
					const provider = providerSelect.value;
					const isOpenAI = provider === 'openai';
					const isAzure = typeSelect.value === 'azure';
					const isAnthropic = provider === 'anthropic';
					const isGemini = provider === 'gemini';
					const isOllama = provider === 'ollama';
					const isGrok = provider === 'grok';

					// Show/hide OpenAI type selector
					if (typeSelect) {
						typeSelect.closest('tr').style.display = isOpenAI ? '' : 'none';
					}

					// Show/hide API Key field based on provider
					if (apiKeyField) {
						const keyLabel = apiKeyField.closest('tr').querySelector('th label');
						apiKeyField.closest('tr').style.display = (provider !== 'ollama') ? '' : 'none';

						if (keyLabel) {
							if (isAnthropic) {
								keyLabel.textContent = 'Anthropic API Key';
							} else if (isGemini) {
								keyLabel.textContent = 'Google AI API Key';
							} else if (isGrok) {
								keyLabel.textContent = 'xAI API Key';
							} else {
								keyLabel.textContent = 'API Key';
							}
						}
					}

					// Show/hide Model field
					if (modelField) {
						modelField.closest('tr').style.display = '';
					}

					// Show/hide Azure-specific fields
					azureFields.forEach(field => {
						field.closest('tr').style.display = (isOpenAI && isAzure) ? '' : 'none';
					});

					// Update model label based on provider
					if (modelLabel) {
						if (isAzure) {
							modelLabel.textContent = 'Deployment Name';
						} else if (isOllama) {
							modelLabel.textContent = 'Model Name';
						} else {
							modelLabel.textContent = 'Model';
						}
					}
				}

				providerSelect.addEventListener('change', updateFieldVisibility);
				if (typeSelect) {
					typeSelect.addEventListener('change', updateFieldVisibility);
				}
				updateFieldVisibility();
			});
		</script>
		<?php
	}

	public function render_ai_provider_field(): void {
		$current     = self::get_ai_provider();
		$is_external = self::is_externally_defined( 'ai_provider' );
		?>
		<select name="<?php echo esc_attr( $this->option_name ); ?>[ai_provider]" id="ai_provider" <?php echo $is_external ? 'disabled' : ''; ?>>
			<option value="openai" <?php selected( $current, 'openai' ); ?>>
				<?php esc_html_e( 'OpenAI (GPT-4o)', 'ai-alt-text' ); ?>
			</option>
			<option value="anthropic" <?php selected( $current, 'anthropic' ); ?>>
				<?php esc_html_e( 'Anthropic Claude', 'ai-alt-text' ); ?>
			</option>
			<option value="gemini" <?php selected( $current, 'gemini' ); ?>>
				<?php esc_html_e( 'Google Gemini', 'ai-alt-text' ); ?>
			</option>
			<option value="ollama" <?php selected( $current, 'ollama' ); ?>>
				<?php esc_html_e( 'Ollama (Self-Hosted)', 'ai-alt-text' ); ?>
			</option>
			<option value="grok" <?php selected( $current, 'grok' ); ?>>
				<?php esc_html_e( 'Grok (xAI)', 'ai-alt-text' ); ?>
			</option>
		</select>
		<?php if ( $is_external ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $this->option_name ); ?>[ai_provider]"
				value="<?php echo esc_attr( $current ); ?>" />
		<?php endif; ?>
		<?php $this->render_external_indicator( 'ai_provider' ); ?>
		<p class="description">
			<?php esc_html_e( 'Choose an AI provider with vision capabilities for image analysis.', 'ai-alt-text' ); ?>
		</p>
		<?php
	}

	public function render_openai_type_field(): void {
		$current     = self::get_openai_type();
		$is_external = self::is_externally_defined( 'openai_type' );
		?>
		<select name="<?php echo esc_attr( $this->option_name ); ?>[openai_type]" id="openai_type" <?php echo $is_external ? 'disabled' : ''; ?>>
			<option value="openai" <?php selected( $current, 'openai' ); ?>>
				<?php esc_html_e( 'OpenAI', 'ai-alt-text' ); ?>
			</option>
			<option value="azure" <?php selected( $current, 'azure' ); ?>>
				<?php esc_html_e( 'Azure OpenAI', 'ai-alt-text' ); ?>
			</option>
		</select>
		<?php if ( $is_external ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $this->option_name ); ?>[openai_type]"
				value="<?php echo esc_attr( $current ); ?>" />
		<?php endif; ?>
		<?php $this->render_external_indicator( 'openai_type' ); ?>
		<p class="description">
			<?php esc_html_e( 'Choose between standard OpenAI API or Azure OpenAI Service', 'ai-alt-text' ); ?>
		</p>
		<?php
	}

	public function render_openai_key_field(): void {
		$value       = self::get_openai_key();
		$is_external = self::is_externally_defined( 'openai_key' );
		$display_val = $is_external && $value !== '' ? '••••••••••••••••' : $value;
		?>
		<input type="password" name="<?php echo esc_attr( $this->option_name ); ?>[openai_key]" id="openai_key"
			value="<?php echo esc_attr( $is_external ? '' : $value ); ?>" class="regular-text" <?php echo $is_external ? 'readonly placeholder="' . esc_attr( $display_val ) . '"' : ''; ?> />
		<?php $this->render_external_indicator( 'openai_key' ); ?>
		<p class="description">
			<?php esc_html_e( 'API key from', 'ai-alt-text' ); ?>
			<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>,
			<a href="https://console.anthropic.com/" target="_blank">Anthropic</a>,
			<a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>,
			<?php esc_html_e( 'or', 'ai-alt-text' ); ?>
			<a href="https://console.x.ai/" target="_blank">xAI</a>
		</p>
		<?php
	}

	public function render_openai_model_field(): void {
		$value       = self::get_openai_model();
		$is_external = self::is_externally_defined( 'openai_model' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[openai_model]" id="openai_model"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="gpt-4o" <?php echo $is_external ? 'readonly' : ''; ?> />
		<?php $this->render_external_indicator( 'openai_model' ); ?>
		<p class="description">
			<?php esc_html_e( 'OpenAI: gpt-4o, gpt-4-vision-preview. Claude: claude-3-5-sonnet-20241022. Gemini: gemini-1.5-flash, gemini-1.5-pro. Ollama: llava, bakllava. Grok: grok-2-vision-1212.', 'ai-alt-text' ); ?>
		</p>
		<?php
	}

	public function render_azure_endpoint_field(): void {
		$value       = self::get_azure_endpoint();
		$is_external = self::is_externally_defined( 'azure_endpoint' );
		?>
		<input type="url" name="<?php echo esc_attr( $this->option_name ); ?>[azure_endpoint]" id="azure_endpoint"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="https://your-resource.openai.azure.com"
			<?php echo $is_external ? 'readonly' : ''; ?> />
		<?php $this->render_external_indicator( 'azure_endpoint' ); ?>
		<p class="description">
			<?php esc_html_e( 'Your Azure OpenAI resource endpoint URL', 'ai-alt-text' ); ?>
		</p>
		<?php
	}

	public function render_azure_api_version_field(): void {
		$value       = self::get_azure_api_version();
		$is_external = self::is_externally_defined( 'azure_api_version' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[azure_api_version]" id="azure_api_version"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="2024-02-15-preview" <?php echo $is_external ? 'readonly' : ''; ?> />
		<?php $this->render_external_indicator( 'azure_api_version' ); ?>
		<p class="description">
			<?php esc_html_e( 'Azure OpenAI API version (e.g., 2024-02-15-preview)', 'ai-alt-text' ); ?>
		</p>
		<?php
	}

	/**
	 * Resolve a configuration value with priority: constant > env var > database > default.
	 */
	private static function resolve_config( string $key ): string {
		$config = self::$config_map[ $key ] ?? null;
		if ( ! $config ) {
			return '';
		}

		if ( defined( $config[ 'const' ] ) ) {
			return (string) constant( $config[ 'const' ] );
		}

		$env_value = getenv( $config[ 'env' ] );
		if ( $env_value !== false && $env_value !== '' ) {
			return $env_value;
		}

		$options = get_option( 'ai_alt_text_options', [] );
		if ( isset( $options[ $key ] ) && $options[ $key ] !== '' ) {
			return (string) $options[ $key ];
		}

		return (string) $config[ 'default' ];
	}

	public static function is_externally_defined( string $key ): bool {
		$config = self::$config_map[ $key ] ?? null;
		if ( ! $config ) {
			return false;
		}

		if ( defined( $config[ 'const' ] ) ) {
			return true;
		}

		$env_value = getenv( $config[ 'env' ] );
		return $env_value !== false && $env_value !== '';
	}

	public static function get_config_source( string $key ): string {
		$config = self::$config_map[ $key ] ?? null;
		if ( ! $config ) {
			return 'default';
		}

		if ( defined( $config[ 'const' ] ) ) {
			return 'constant';
		}

		$env_value = getenv( $config[ 'env' ] );
		if ( $env_value !== false && $env_value !== '' ) {
			return 'env';
		}

		$options = get_option( 'ai_alt_text_options', [] );
		if ( isset( $options[ $key ] ) && $options[ $key ] !== '' ) {
			return 'database';
		}

		return 'default';
	}

	private function render_external_indicator( string $key ): void {
		if ( ! self::is_externally_defined( $key ) ) {
			return;
		}
		$source = self::get_config_source( $key );
		$label  = $source === 'constant'
			? __( 'wp-config.php constant', 'ai-alt-text' )
			: __( 'environment variable', 'ai-alt-text' );
		?>
		<span class="description" style="color: #2271b1; font-weight: 500; margin-left: 8px;">
			<?php
			/* translators: %s: Configuration source (wp-config.php constant or environment variable) */
			printf( esc_html__( '(Set via %s)', 'ai-alt-text' ), esc_html( $label ) );
			?>
		</span>
		<?php
	}

	public static function get_ai_provider(): string {
		return self::resolve_config( 'ai_provider' );
	}

	public static function get_auto_generate(): string {
		return self::resolve_config( 'auto_generate' );
	}

	public static function get_openai_type(): string {
		return self::resolve_config( 'openai_type' );
	}

	public static function get_openai_key(): string {
		return self::resolve_config( 'openai_key' );
	}

	public static function get_openai_model(): string {
		return self::resolve_config( 'openai_model' );
	}

	public static function get_azure_endpoint(): string {
		return self::resolve_config( 'azure_endpoint' );
	}

	public static function get_azure_api_version(): string {
		return self::resolve_config( 'azure_api_version' );
	}

	public static function get_anthropic_key(): string {
		return self::resolve_config( 'anthropic_key' );
	}

	public static function get_anthropic_model(): string {
		return self::resolve_config( 'anthropic_model' );
	}

	public static function get_gemini_key(): string {
		return self::resolve_config( 'gemini_key' );
	}

	public static function get_gemini_model(): string {
		return self::resolve_config( 'gemini_model' );
	}

	public static function get_ollama_endpoint(): string {
		return self::resolve_config( 'ollama_endpoint' );
	}

	public static function get_ollama_model(): string {
		return self::resolve_config( 'ollama_model' );
	}

	public static function get_grok_key(): string {
		return self::resolve_config( 'grok_key' );
	}

	public static function get_grok_model(): string {
		return self::resolve_config( 'grok_model' );
	}
}
