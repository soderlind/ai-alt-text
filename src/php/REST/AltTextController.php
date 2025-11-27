<?php
/**
 * Alt Text REST Controller - REST API endpoint for generating alt text.
 *
 * @package AiAltText\REST
 */

declare(strict_types=1);

namespace AiAltText\REST;

use AiAltText\Services\AltTextService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST API controller for generating alt text.
 */
class AltTextController extends WP_REST_Controller {

	private AltTextService $alt_text_service;

	protected $namespace = 'ai-alt-text/v1';
	protected $rest_base = 'generate';

	public function __construct() {
		$this->alt_text_service = new AltTextService();
	}

	/**
	 * Register REST routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the routes for the controller.
	 */
	public function register_routes(): void {
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_alt_text' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
				'args'                => [
					'attachment_id' => [
						'description'       => __( 'The attachment ID to generate alt text for.', 'ai-alt-text' ),
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					],
					'image_url'     => [
						'description'       => __( 'The image URL to generate alt text for.', 'ai-alt-text' ),
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'esc_url_raw',
					],
					'overwrite'     => [
						'description'       => __( 'Whether to overwrite existing alt text.', 'ai-alt-text' ),
						'type'              => 'boolean',
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					],
				],
			],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_alt_text' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'args'                => [
					'id' => [
						'description'       => __( 'The attachment ID.', 'ai-alt-text' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			],
		] );
	}

	/**
	 * Check if the current user can create items.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to generate alt text.', 'ai-alt-text' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}
		return true;
	}

	/**
	 * Check if the current user can read items.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view alt text.', 'ai-alt-text' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}
		return true;
	}

	/**
	 * Generate alt text for an image.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object.
	 */
	public function generate_alt_text( $request ) {
		$attachment_id = $request->get_param( 'attachment_id' );
		$image_url     = $request->get_param( 'image_url' );
		$overwrite     = $request->get_param( 'overwrite' );

		if ( ! $attachment_id && ! $image_url ) {
			return new WP_Error(
				'missing_parameter',
				__( 'Either attachment_id or image_url is required.', 'ai-alt-text' ),
				[ 'status' => 400 ]
			);
		}

		try {
			if ( $attachment_id ) {
				$alt_text = $this->alt_text_service->generateAndSave( $attachment_id, $overwrite );
				return new WP_REST_Response( [
					'success'       => true,
					'alt_text'      => $alt_text,
					'attachment_id' => $attachment_id,
					'saved'         => true,
				], 200 );
			} else {
				$alt_text = $this->alt_text_service->generateFromUrl( $image_url );
				return new WP_REST_Response( [
					'success'  => true,
					'alt_text' => $alt_text,
					'saved'    => false,
				], 200 );
			}
		} catch (\Exception $e) {
			return new WP_Error(
				'generation_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Get existing alt text for an attachment.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object.
	 */
	public function get_alt_text( $request ) {
		$attachment_id = $request->get_param( 'id' );

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error(
				'not_an_image',
				__( 'The specified attachment is not an image.', 'ai-alt-text' ),
				[ 'status' => 400 ]
			);
		}

		$alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		return new WP_REST_Response( [
			'attachment_id' => $attachment_id,
			'alt_text'      => $alt_text ?: '',
			'has_alt_text'  => ! empty( $alt_text ),
		], 200 );
	}
}
