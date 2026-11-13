<?php
/**
 * Abilities API integration for Safe Redirect Manager.
 *
 * @package safe-redirect-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers redirect abilities with the WordPress Abilities API.
 */
class SRM_Abilities {

	/**
	 * Factory method.
	 *
	 * @since x.x.x
	 * @return self
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			add_action( 'wp_abilities_api_categories_init', array( $instance, 'register_category' ) );
			add_action( 'wp_abilities_api_init', array( $instance, 'register_abilities' ) );
		}

		return $instance;
	}

	/**
	 * Registers the redirect ability category.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register_category() {
		wp_register_ability_category(
			'safe-redirect-manager',
			array(
				'label'       => __( 'Redirects', 'safe-redirect-manager' ),
				'description' => __( 'Query, create, update and delete redirects.', 'safe-redirect-manager' ),
			)
		);
	}

	/**
	 * Registers the redirect abilities.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register_abilities() {
		$id     = array(
			'type'    => 'integer',
			'minimum' => 1,
		);
		$fields = array(
			'redirect_from' => array( 'type' => 'string' ),
			'redirect_to'   => array( 'type' => 'string' ),
			'status_code'   => array( 'type' => 'integer' ),
			'enable_regex'  => array( 'type' => 'boolean' ),
			'post_status'   => array(
				'type' => 'string',
				'enum' => array( 'publish', 'draft' ),
			),
			'notes'         => array( 'type' => 'string' ),
		);

		$abilities = array(
			'redirects-query' => array( __( 'Query Redirects', 'safe-redirect-manager' ), __( 'Returns one page of up to 100 redirects that are not in the trash.', 'safe-redirect-manager' ), array( 'page' => $id ), array() ),
			'redirect-create' => array( __( 'Create Redirect', 'safe-redirect-manager' ), __( 'Creates a redirect. A redirect with the same "redirect from" path is overwritten.', 'safe-redirect-manager' ), $fields, array( 'redirect_from', 'redirect_to' ) ),
			'redirect-update' => array( __( 'Update Redirect', 'safe-redirect-manager' ), __( 'Updates the given fields of a redirect.', 'safe-redirect-manager' ), array( 'id' => $id ) + $fields, array( 'id' ) ),
			'redirect-delete' => array( __( 'Delete Redirect', 'safe-redirect-manager' ), __( 'Moves a redirect to the trash.', 'safe-redirect-manager' ), array( 'id' => $id ), array( 'id' ) ),
		);

		foreach ( $abilities as $name => list( $label, $description, $properties, $required ) ) {
			wp_register_ability(
				'safe-redirect-manager/' . $name,
				array(
					'label'               => $label,
					'description'         => $description,
					'category'            => 'safe-redirect-manager',
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => $properties,
						'required'   => $required,
						'default'    => array(),
					),
					'execute_callback'    => array( $this, str_replace( '-', '_', $name ) ),
					'permission_callback' => array( $this, 'can_manage_redirects' ),
					'meta'                => array(
						'annotations'  => array(
							'readonly'    => 'redirects-query' === $name,
							'destructive' => 'redirect-delete' === $name,
							'idempotent'  => true,
						),
						'show_in_rest' => true,
					),
				)
			);
		}
	}

	/**
	 * Whether the current user can manage redirects.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function can_manage_redirects() {
		/** This filter is documented in inc/classes/class-srm-post-type.php */
		return current_user_can( apply_filters( 'srm_restrict_to_capability', 'srm_manage_redirects' ) );
	}

	/**
	 * Returns one page of redirects.
	 *
	 * @since x.x.x
	 * @param array $input Ability input.
	 * @return array
	 */
	public function redirects_query( $input ) {
		return array_map(
			function ( $redirect_id ) {
				return srm_get_redirect_data( $redirect_id, array( 'post_status', 'notes' ) );
			},
			srm_query_redirect_page( $input['page'] ?? 1 )->posts
		);
	}

	/**
	 * Creates a redirect.
	 *
	 * @since x.x.x
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public function redirect_create( $input ) {
		$id = srm_create_redirect( $input['redirect_from'], $input['redirect_to'], $input['status_code'] ?? 302, $input['enable_regex'] ?? false, $input['post_status'] ?? 'publish', 0, $input['notes'] ?? '', get_current_user_id(), true );

		return is_wp_error( $id ) ? $id : array( 'id' => $id );
	}

	/**
	 * Updates the given fields of a redirect.
	 *
	 * @since x.x.x
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public function redirect_update( $input ) {
		$id = $input['id'];

		if ( 'redirect_rule' !== get_post_type( $id ) ) {
			return new WP_Error( 'invalid-redirect', __( 'Invalid redirect ID.', 'safe-redirect-manager' ) );
		}

		if ( isset( $input['status_code'] ) && ! in_array( $input['status_code'], srm_get_valid_status_codes(), true ) ) {
			return new WP_Error( 'invalid-argument', __( 'Invalid status code.', 'safe-redirect-manager' ) );
		}

		$meta = array(
			'from'        => isset( $input['redirect_from'] ) ? srm_sanitize_redirect_from( $input['redirect_from'], $input['enable_regex'] ?? get_post_meta( $id, '_redirect_rule_from_regex', true ) ) : null,
			'to'          => isset( $input['redirect_to'] ) ? srm_sanitize_redirect_to( $input['redirect_to'] ) : null,
			'status_code' => $input['status_code'] ?? null,
			'from_regex'  => $input['enable_regex'] ?? null,
			'notes'       => isset( $input['notes'] ) ? sanitize_text_field( $input['notes'] ) : null,
		);

		if ( '' === $meta['from'] ) {
			return new WP_Error( 'invalid-argument', __( 'Redirect from is invalid.', 'safe-redirect-manager' ) );
		}

		foreach ( $meta as $key => $value ) {
			if ( null !== $value ) {
				update_post_meta( $id, '_redirect_rule_' . $key, wp_slash( $value ) );
			}
		}

		// Also fires transition_post_status, which flushes the redirect cache.
		$updated = wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => $input['post_status'] ?? get_post_status( $id ),
			),
			true
		);

		return is_wp_error( $updated ) ? $updated : array( 'id' => $id );
	}

	/**
	 * Moves a redirect to the trash.
	 *
	 * @since x.x.x
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public function redirect_delete( $input ) {
		$id = $input['id'];

		if ( 'redirect_rule' !== get_post_type( $id ) || ( 'trash' !== get_post_status( $id ) && ! wp_trash_post( $id ) ) ) {
			return new WP_Error( 'invalid-redirect', __( 'Invalid redirect ID.', 'safe-redirect-manager' ) );
		}

		return array( 'id' => $id );
	}
}
