<?php
/**
 * Tests for AltTextController.
 *
 * @package AiAltText\Tests
 */

declare(strict_types=1);

namespace AiAltText\Tests;

use AiAltText\REST\AltTextController;
use MockWordPress;
use PHPUnit\Framework\TestCase;
use WP_Error;

// Mock WP_REST classes
if ( ! class_exists( 'WP_REST_Controller' ) ) {
	abstract class WP_REST_Controller {
		protected $namespace;
		protected $rest_base;
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		public const READABLE  = 'GET';
		public const CREATABLE = 'POST';
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private array $params = [];

		public function __construct( array $params = [] ) {
			$this->params = $params;
		}

		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}

		public function set_param( string $key, $value ): void {
			$this->params[ $key ] = $value;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		public array $data;
		public int $status;

		public function __construct( array $data, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		public function get_data(): array {
			return $this->data;
		}

		public function get_status(): int {
			return $this->status;
		}
	}
}

// Move these to global namespace for the controller to use
class_alias( '\AiAltText\Tests\WP_REST_Controller', '\WP_REST_Controller', false );
class_alias( '\AiAltText\Tests\WP_REST_Server', '\WP_REST_Server', false );
class_alias( '\AiAltText\Tests\WP_REST_Request', '\WP_REST_Request', false );
class_alias( '\AiAltText\Tests\WP_REST_Response', '\WP_REST_Response', false );

/**
 * Test case for AltTextController.
 */
class AltTextControllerTest extends TestCase {

	private AltTextController $controller;

	protected function setUp(): void {
		MockWordPress::reset();
		$this->controller = new AltTextController();
	}

	public function testCreateItemPermissionsCheckReturnsTrueWhenUserCanUpload(): void {
		MockWordPress::$current_user_can[ 'upload_files' ] = true;

		$request = new WP_REST_Request();
		$result  = $this->controller->create_item_permissions_check( $request );

		$this->assertTrue( $result );
	}

	public function testCreateItemPermissionsCheckReturnsErrorWhenUserCannotUpload(): void {
		MockWordPress::$current_user_can[ 'upload_files' ] = false;

		$request = new WP_REST_Request();
		$result  = $this->controller->create_item_permissions_check( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	public function testGetItemPermissionsCheckReturnsTrueWhenUserCanUpload(): void {
		MockWordPress::$current_user_can[ 'upload_files' ] = true;

		$request = new WP_REST_Request();
		$result  = $this->controller->get_item_permissions_check( $request );

		$this->assertTrue( $result );
	}

	public function testGetItemPermissionsCheckReturnsErrorWhenUserCannotUpload(): void {
		MockWordPress::$current_user_can[ 'upload_files' ] = false;

		$request = new WP_REST_Request();
		$result  = $this->controller->get_item_permissions_check( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	public function testGenerateAltTextReturnsErrorWhenNoParameters(): void {
		$request = new WP_REST_Request( [
			'attachment_id' => null,
			'image_url'     => null,
		] );

		$result = $this->controller->generate_alt_text( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_parameter', $result->get_error_code() );
	}

	public function testGetAltTextReturnsExistingAltText(): void {
		$attachment_id = 123;
		$alt_text      = 'A beautiful landscape';

		MockWordPress::$attachments[ $attachment_id ] = [
			'is_image' => true,
			'url'      => 'http://example.com/landscape.jpg',
		];

		MockWordPress::$post_meta[ $attachment_id ] = [
			'_wp_attachment_image_alt' => [ $alt_text ],
		];

		$request = new WP_REST_Request( [ 'id' => $attachment_id ] );
		$result  = $this->controller->get_alt_text( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$data = $result->get_data();

		$this->assertEquals( $attachment_id, $data[ 'attachment_id' ] );
		$this->assertEquals( $alt_text, $data[ 'alt_text' ] );
		$this->assertTrue( $data[ 'has_alt_text' ] );
	}

	public function testGetAltTextReturnsEmptyWhenNoAltText(): void {
		$attachment_id = 123;

		MockWordPress::$attachments[ $attachment_id ] = [
			'is_image' => true,
			'url'      => 'http://example.com/image.jpg',
		];

		MockWordPress::$post_meta[ $attachment_id ] = [];

		$request = new WP_REST_Request( [ 'id' => $attachment_id ] );
		$result  = $this->controller->get_alt_text( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$data = $result->get_data();

		$this->assertEquals( '', $data[ 'alt_text' ] );
		$this->assertFalse( $data[ 'has_alt_text' ] );
	}

	public function testGetAltTextReturnsErrorForNonImage(): void {
		$attachment_id = 123;

		MockWordPress::$attachments[ $attachment_id ] = [
			'is_image' => false,
			'url'      => 'http://example.com/document.pdf',
		];

		$request = new WP_REST_Request( [ 'id' => $attachment_id ] );
		$result  = $this->controller->get_alt_text( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'not_an_image', $result->get_error_code() );
	}
}
