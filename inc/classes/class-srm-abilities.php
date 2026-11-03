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
	 * Ability category slug.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const CATEGORY = 'safe-redirect-manager';

	/**
	 * Default values for optional create-redirect input.
	 *
	 * @since x.x.x
	 * @var array
	 */
	const CREATE_DEFAULTS = array(
		'status_code'  => 302,
		'enable_regex' => false,
		'post_status'  => 'publish',
		'notes'        => '',
	);

	/**
	 * Sets up ability hooks.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function setup() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

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
			$instance->setup();
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
			self::CATEGORY,
			array(
				'label'       => __( 'Redirects', 'safe-redirect-manager' ),
				'description' => __( 'Abilities that list, create or delete redirects.', 'safe-redirect-manager' ),
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
		wp_register_ability(
			'safe-redirect-manager/list-redirects',
			array(
				'label'               => __( 'List Redirects', 'safe-redirect-manager' ),
				'description'         => __( 'Returns one page of up to 100 redirect rules (any status except trash), each with its ID, from path, destination, status code, message, regex and force-https flags, notes and post status. Request the next page until an empty list is returned.', 'safe-redirect-manager' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'page' => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 1,
						),
					),
					'additionalProperties' => false,
					'default'              => array(),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'ID'            => array( 'type' => 'integer' ),
							'redirect_from' => array( 'type' => 'string' ),
							'redirect_to'   => array( 'type' => 'string' ),
							'status_code'   => array( 'type' => 'integer' ),
							'message'       => array( 'type' => 'string' ),
							'enable_regex'  => array( 'type' => 'boolean' ),
							'force_https'   => array( 'type' => 'boolean' ),
							'notes'         => array( 'type' => 'string' ),
							'post_status'   => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'list_redirects' ),
				'permission_callback' => array( $this, 'can_manage_redirects' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'safe-redirect-manager/create-redirect',
			array(
				'label'               => __( 'Create Redirect', 'safe-redirect-manager' ),
				'description'         => __( 'Creates a redirect rule. If a rule with the same "redirect from" path already exists, it is overwritten instead: its destination, status code, regex flag, post status and notes are replaced (omitted fields use their defaults), its priority is reset, and its message, force-https setting and author are kept. A trashed rule with that path is restored and overwritten. Publishing is refused once the site\'s maximum number of published redirects is reached, unless it overwrites a rule that is already published.', 'safe-redirect-manager' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'redirect_from' => array(
							'type'        => 'string',
							'description' => __( 'Relative path to redirect from, e.g. /old-page/.', 'safe-redirect-manager' ),
						),
						'redirect_to'   => array(
							'type'        => 'string',
							'description' => __( 'Relative path or absolute URL to redirect to.', 'safe-redirect-manager' ),
						),
						'status_code'   => array(
							'type'        => 'integer',
							'default'     => self::CREATE_DEFAULTS['status_code'],
							'description' => __( 'HTTP status code, e.g. 301 or 302. Must be one of the site\'s valid redirect status codes.', 'safe-redirect-manager' ),
						),
						'enable_regex'  => array(
							'type'        => 'boolean',
							'default'     => self::CREATE_DEFAULTS['enable_regex'],
							'description' => __( 'Treat "redirect from" as a regular expression matched against the request path. Start the pattern with "/" and do not use "^", e.g. /blog/(.*)$ with redirect_to /news/$1.', 'safe-redirect-manager' ),
						),
						'post_status'   => array(
							'type'        => 'string',
							'enum'        => array( 'publish', 'draft' ),
							'default'     => self::CREATE_DEFAULTS['post_status'],
							'description' => __( 'Only published redirects are active.', 'safe-redirect-manager' ),
						),
						'notes'         => array(
							'type'    => 'string',
							'default' => self::CREATE_DEFAULTS['notes'],
						),
					),
					'required'             => array( 'redirect_from', 'redirect_to' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'create_redirect' ),
				'permission_callback' => array( $this, 'can_manage_redirects' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'safe-redirect-manager/delete-redirect',
			array(
				'label'               => __( 'Delete Redirect', 'safe-redirect-manager' ),
				'description'         => __( 'Moves a redirect rule to the trash by its ID, so it stops redirecting. If the site has the trash disabled (EMPTY_TRASH_DAYS is 0), the rule is deleted permanently instead.', 'safe-redirect-manager' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id' => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'delete_redirect' ),
				'permission_callback' => array( $this, 'can_manage_redirects' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
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
	 * Lists one page of redirects.
	 *
	 * @since x.x.x
	 * @param array $input Ability input.
	 * @return array
	 */
	public function list_redirects( $input = array() ) {
		$query = srm_query_redirect_page( $input['page'] ?? 1 );

		return array_map(
			function ( $redirect_id ) {
				$redirect = srm_get_redirect_data( $redirect_id, array( 'post_status', 'notes' ) );

				// Stored as "1" or not at all.
				$redirect['force_https'] = (bool) $redirect['force_https'];

				return $redirect;
			},
			$query->posts
		);
	}

	/**
	 * Creates or updates a redirect.
	 *
	 * @since x.x.x
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public function create_redirect( $input ) {
		global $wpdb;

		$input         = wp_parse_args( $input, self::CREATE_DEFAULTS );
		$redirect_from = srm_sanitize_redirect_from( $input['redirect_from'] );
		// Input validation accepts "false" and "0" as booleans without converting them.
		$enable_regex = rest_sanitize_boolean( $input['enable_regex'] );

		// srm_sanitize_redirect_to() turns an empty destination into "/", the site root.
		if ( '' === $redirect_from || '' === trim( $input['redirect_to'] ) ) {
			return new WP_Error( 'invalid-argument', __( 'Redirect from and/or redirect to arguments are invalid.', 'safe-redirect-manager' ), array( 'status' => 400 ) );
		}

		// Same lookup srm_create_redirect() uses to decide whether it overwrites a rule.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Must match srm_create_redirect(), which is uncached.
		$existing_id     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1", '_redirect_rule_from', $redirect_from ) );
		$existing_status = $existing_id ? get_post_status( $existing_id ) : false;

		// Compiled as SRM_Redirect::match_redirect() does, from the value srm_create_redirect() stores.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Generic.PHP.NoSilencedErrors.Forbidden -- an invalid pattern is reported as WP_Error below, not a PHP warning.
		if ( $enable_regex && false === @preg_match( '@' . untrailingslashit( $redirect_from ) . '@', '' ) ) {
			return new WP_Error( 'invalid-regex', __( 'Redirect from is not a valid regular expression.', 'safe-redirect-manager' ), array( 'status' => 400 ) );
		}

		// Checked before a trashed rule can be restored below, and without srm_create_redirect()'s absint() so negative codes fail.
		if ( ! in_array( (int) $input['status_code'], srm_get_valid_status_codes(), true ) ) {
			return new WP_Error( 'invalid-argument', __( 'Invalid status code.', 'safe-redirect-manager' ), array( 'status' => 400 ) );
		}

		// Only a call that adds a published rule can push one past the limit, where it would stop being applied.
		if ( 'publish' === $input['post_status'] && 'publish' !== $existing_status && (int) wp_count_posts( 'redirect_rule' )->publish >= srm_get_max_redirects() ) {
			return new WP_Error( 'max-redirects-reached', __( 'The maximum number of redirects has been reached.', 'safe-redirect-manager' ), array( 'status' => 400 ) );
		}

		// srm_create_redirect() would republish a trashed rule directly, leaving its trash slug and meta behind.
		if ( 'trash' === $existing_status && ! wp_untrash_post( $existing_id ) ) {
			return new WP_Error( 'error-updating', __( 'An error occurred restoring the redirect from the trash.', 'safe-redirect-manager' ) );
		}

		$redirect_id = srm_create_redirect(
			$input['redirect_from'],
			$input['redirect_to'],
			$input['status_code'],
			$enable_regex,
			$input['post_status'],
			0,
			$input['notes'],
			$existing_id ? (int) get_post_field( 'post_author', $existing_id ) : get_current_user_id()
		);

		if ( is_wp_error( $redirect_id ) ) {
			// Don't leave a rule restored by a call that failed.
			if ( 'trash' === $existing_status ) {
				wp_trash_post( $existing_id );
			}

			return $redirect_id;
		}

		return array( 'id' => (int) $redirect_id );
	}

	/**
	 * Trashes a redirect.
	 *
	 * @since x.x.x
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public function delete_redirect( $input ) {
		$redirect = get_post( $input['id'] );

		if ( ! $redirect || 'redirect_rule' !== $redirect->post_type ) {
			return new WP_Error( 'invalid-redirect', __( 'Invalid redirect ID.', 'safe-redirect-manager' ), array( 'status' => 404 ) );
		}

		if ( 'trash' !== $redirect->post_status && ! wp_trash_post( $redirect->ID ) ) {
			return new WP_Error( 'error-deleting', __( 'An error occurred deleting the redirect.', 'safe-redirect-manager' ) );
		}

		// With the trash disabled the post is deleted outright, which doesn't flush the cache via a status change.
		srm_flush_cache();

		return array( 'id' => $redirect->ID );
	}
}
