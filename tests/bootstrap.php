<?php
/**
 * PHPUnit Bootstrap file.
 *
 * Mocks WordPress functions for unit testing.
 *
 * @package AiAltText\Tests
 */

declare(strict_types=1);

// Composer autoloader
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Mock WordPress functions that are used in the plugin.
 */

// Translation functions
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( string $url ): string {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ): int {
		return abs( (int) $maybeint );
	}
}

// WordPress locale
if ( ! function_exists( 'get_locale' ) ) {
	function get_locale(): string {
		return MockWordPress::$locale ?? 'en_US';
	}
}

// Attachment functions
if ( ! function_exists( 'wp_attachment_is_image' ) ) {
	function wp_attachment_is_image( int $attachment_id ): bool {
		return isset( MockWordPress::$attachments[ $attachment_id ] )
			&& MockWordPress::$attachments[ $attachment_id ][ 'is_image' ];
	}
}

if ( ! function_exists( 'wp_get_attachment_url' ) ) {
	function wp_get_attachment_url( int $attachment_id ): string|false {
		return MockWordPress::$attachments[ $attachment_id ][ 'url' ] ?? false;
	}
}

// Post meta
if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( int $post_id, string $key = '', bool $single = false ) {
		$meta = MockWordPress::$post_meta[ $post_id ] ?? [];
		if ( $key === '' ) {
			return $meta;
		}
		$value = $meta[ $key ] ?? [];
		return $single ? ( $value[ 0 ] ?? '' ) : $value;
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( int $post_id, string $meta_key, $meta_value ): int|bool {
		if ( ! isset( MockWordPress::$post_meta[ $post_id ] ) ) {
			MockWordPress::$post_meta[ $post_id ] = [];
		}
		MockWordPress::$post_meta[ $post_id ][ $meta_key ] = [ $meta_value ];
		return true;
	}
}

// Options
if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, $default = false ) {
		return MockWordPress::$options[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, $value ): bool {
		MockWordPress::$options[ $option ] = $value;
		return true;
	}
}

// Upload dir
if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir(): array {
		return [
			'basedir' => '/var/www/html/wp-content/uploads',
			'baseurl' => 'http://example.com/wp-content/uploads',
			'path'    => '/var/www/html/wp-content/uploads/' . date( 'Y/m' ),
			'url'     => 'http://example.com/wp-content/uploads/' . date( 'Y/m' ),
		];
	}
}

// HTTP functions
if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( string $url, array $args = [] ): array|\WP_Error {
		return MockWordPress::mockHttpRequest( 'POST', $url, $args );
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( string $url, array $args = [] ): array|\WP_Error {
		return MockWordPress::mockHttpRequest( 'GET', $url, $args );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ): string {
		if ( is_wp_error( $response ) ) {
			return '';
		}
		return $response[ 'body' ] ?? '';
	}
}

if ( ! function_exists( 'wp_remote_retrieve_header' ) ) {
	function wp_remote_retrieve_header( $response, string $header ): string {
		if ( is_wp_error( $response ) ) {
			return '';
		}
		return $response[ 'headers' ][ strtolower( $header ) ] ?? '';
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool {
		return $thing instanceof \WP_Error;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, int $options = 0, int $depth = 512 ): string|false {
		return json_encode( $data, $options, $depth );
	}
}

// Hooks (no-op for unit tests)
if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $tag, $callback, int $priority = 10, int $args = 1 ): bool {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $tag, $callback, int $priority = 10, int $args = 1 ): bool {
		return true;
	}
}

// User capabilities
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $capability ): bool {
		return MockWordPress::$current_user_can[ $capability ] ?? false;
	}
}

if ( ! function_exists( 'rest_authorization_required_code' ) ) {
	function rest_authorization_required_code(): int {
		return 401;
	}
}

if ( ! function_exists( 'rest_sanitize_boolean' ) ) {
	function rest_sanitize_boolean( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}

/**
 * WP_Error mock class.
 */
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private string $code;
		private string $message;
		private array $data;

		public function __construct( string $code = '', string $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = is_array( $data ) ? $data : [ $data ];
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		public function get_error_data(): array {
			return $this->data;
		}
	}
}

/**
 * Mock WordPress state container.
 */
class MockWordPress {
	public static string $locale = 'en_US';
	public static array $attachments = [];
	public static array $post_meta = [];
	public static array $options = [];
	public static array $current_user_can = [];
	public static array $http_responses = [];

	/**
	 * Reset all mocked state.
	 */
	public static function reset(): void {
		self::$locale           = 'en_US';
		self::$attachments      = [];
		self::$post_meta        = [];
		self::$options          = [];
		self::$current_user_can = [];
		self::$http_responses   = [];
	}

	/**
	 * Mock an HTTP request.
	 *
	 * @param string $method The HTTP method.
	 * @param string $url    The URL.
	 * @param array  $args   The request arguments.
	 * @return array|\WP_Error The mocked response.
	 */
	public static function mockHttpRequest( string $method, string $url, array $args ): array|\WP_Error {
		foreach ( self::$http_responses as $pattern => $response ) {
			if ( str_contains( $url, $pattern ) ) {
				if ( $response instanceof \WP_Error ) {
					return $response;
				}
				return [
					'body'    => is_string( $response ) ? $response : json_encode( $response ),
					'headers' => [ 'content-type' => 'application/json' ],
				];
			}
		}

		// Default: return empty success
		return [
			'body'    => '{}',
			'headers' => [],
		];
	}
}
