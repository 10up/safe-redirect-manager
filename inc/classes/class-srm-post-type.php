<?php
/**
 * Setup SRM post type
 */

class SRM_Post_Type {

	public $status_code_labels = array(); // Defined later to allow i18n

	private $whitelist_hosts = array();

	/**
	 * Sets up redirect manager
	 *
	 * @since 1.8
	 */
	public function setup() {
		$this->status_code_labels = array(
			301 => esc_html__( 'Moved Permanently', 'safe-redirect-manager' ),
			302 => esc_html__( 'Found', 'safe-redirect-manager' ),
			303 => esc_html__( 'See Other', 'safe-redirect-manager' ),
			307 => esc_html__( 'Temporary Redirect', 'safe-redirect-manager' ),
			403 => esc_html__( 'Forbidden', 'safe-redirect-manager' ),
			404 => esc_html__( 'Not Found', 'safe-redirect-manager' ),
		);

		add_action( 'init', array( $this, 'action_register_post_types' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		add_filter( 'manage_redirect_rule_posts_columns', array( $this, 'filter_redirect_columns' ) );
		add_action( 'manage_redirect_rule_posts_custom_column', array( $this, 'action_custom_redirect_columns' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'action_transition_post_status' ), 10, 3 );
		add_filter( 'post_updated_messages', array( $this, 'filter_redirect_updated_messages' ) );
		add_action( 'admin_notices', array( $this, 'action_redirect_chain_alert' ) );
		add_filter( 'the_title', array( $this, 'filter_admin_title' ), 100, 2 );
		add_filter( 'bulk_actions-edit-redirect_rule', array( $this, 'filter_bulk_actions' ) );
		add_action( 'admin_print_styles-edit.php', array( $this, 'action_print_logo_css' ), 10, 1 );
		add_action( 'admin_print_styles-post.php', array( $this, 'action_print_logo_css' ), 10, 1 );
		add_action( 'admin_print_styles-post-new.php', array( $this, 'action_print_logo_css' ), 10, 1 );
		add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 10, 2 );

		// Search filters
		add_filter( 'posts_join', array( $this, 'filter_search_join' ) );
		add_filter( 'posts_where', array( $this, 'filter_search_where' ) );
		add_filter( 'posts_distinct', array( $this, 'filter_search_distinct' ) );
		add_filter( 'post_row_actions', array( $this, 'filter_disable_quick_edit' ), 10, 2 );
	}

	/**
	 * Remove quick edit
	 *
	 * @param  array   $actions
	 * @param  WP_Post $post
	 * @since  1.8
	 * @return array
	 */
	public function filter_disable_quick_edit( $actions = array(), $post ) {
		if ( 'redirect_rule' === get_post_type( $post ) && isset( $actions['inline hide-if-no-js'] ) ) {
			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['view'] );
		}

		return $actions;
	}

	/**
	 * Join posts table with postmeta table on search
	 *
	 * @since 1.2
	 * @param string $join
	 * @uses get_query_var
	 * @return string
	 */
	public function filter_search_join( $join ) {
		global $wp_query;

		if ( empty( $wp_query ) || 'redirect_rule' !== get_query_var( 'post_type' ) ) {
			return $join;
		}

		global $wpdb;

		$s = get_query_var( 's' );
		if ( ! empty( $s ) ) {
			$join .= " LEFT JOIN $wpdb->postmeta AS m ON ($wpdb->posts.ID = m.post_id) ";
		}
		return $join;
	}

	/**
	 * Return distinct search results
	 *
	 * @since 1.2
	 * @param string $distinct
	 * @uses get_query_var
	 * @return string
	 */
	public function filter_search_distinct( $distinct ) {
		global $wp_query;

		if ( empty( $wp_query ) || 'redirect_rule' !== get_query_var( 'post_type' ) ) {
			return $distinct;
		}

		return 'DISTINCT';
	}

	/**
	 * Join posts table with postmeta table on search
	 *
	 * @since 1.2
	 * @param string $where
	 * @uses is_search, get_query_var
	 * @return string
	 */
	public function filter_search_where( $where ) {
		global $wp_query;

		if ( empty( $wp_query ) || 'redirect_rule' !== get_query_var( 'post_type' ) || ! is_search() || empty( $where ) ) {
			return $where;
		}

		$terms = $this->get_search_terms();

		if ( empty( $terms ) ) {
			return $where;
		}

		$exact = get_query_var( 'exact' );
		$n     = ( ! empty( $exact ) ) ? '' : '%';

		$search    = '';
		$seperator = '';
		$search   .= '(';

		// we check the meta values against each term in the search
		foreach ( $terms as $term ) {
			$search .= $seperator;
			// Used esc_sql instead of wpdb->prepare since wpdb->prepare wraps things in quotes
			$search .= sprintf( "( ( m.meta_value LIKE '%s%s%s' AND m.meta_key = '%s') OR ( m.meta_value LIKE '%s%s%s' AND m.meta_key = '%s') )", $n, esc_sql( $term ), $n, esc_sql( '_redirect_rule_from' ), $n, esc_sql( $term ), $n, esc_sql( '_redirect_rule_to' ) );

			$seperator = ' OR ';
		}

		$search .= ')';

		$where = preg_replace( '/\(\(\(.*?\)\)\)/is', '((' . $search . '))', $where );

		return $where;
	}

	/**
	 * Get an array of search terms
	 *
	 * @since 1.2
	 * @uses get_query_var
	 * @return array
	 */
	private function get_search_terms() {
		$s = get_query_var( 's' );

		if ( ! empty( $s ) ) {
			preg_match_all( '/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', stripslashes( $s ), $matches );
			$search_terms = array_map( create_function( '$a', 'return trim( $a, "\\"\'\\n\\r " );' ), $matches[0] );
		}
		return $search_terms;
	}

	/**
	 * Swap tools logo for plugin logo
	 *
	 * @since 1.1
	 * @uses plugins_url
	 * @return void
	 */
	public function action_print_logo_css() {
		if ( $this->is_plugin_page() ) {
			?>
			<style type="text/css">
				#visibility, .view-switch, .posts .inline-edit-col-left .inline-edit-group, #preview-action {
					display: none;
				}
				#srm_redirect_rule_from {
					width: 60%;
				}
			</style>
		<?php
		}
	}

	/**
	 * Limit the bulk actions available in the Manage Redirects view
	 *
	 * @since 1.0
	 * @return array
	 */
	public function filter_bulk_actions( $actions ) {

		// No bulk editing at this time
		unset( $actions['edit'] );

		return $actions;
	}

	/**
	 * Whether or not this is an admin page specific to the plugin
	 *
	 * @since 1.1
	 * @uses get_post_type
	 * @return bool
	 */
	private function is_plugin_page() {
		return (bool) ( get_post_type() === 'redirect_rule' || ( isset( $_GET['post_type'] ) && 'redirect_rule' === $_GET['post_type'] ) );
	}

	/**
	 * Echoes admin message if redirect chains exist
	 *
	 * @since 1.0
	 * @uses apply_filters
	 * @return void
	 */
	public function action_redirect_chain_alert() {
		global $hook_suffix;
		if ( $this->is_plugin_page() ) {

			/**
			 * check_for_possible_redirect_loops() runs in best case Theta(n^2) so if you have 100 redirects, this method
			 * will be running slow. Let's disable it by default.
			 */
			if ( apply_filters( 'srm_check_for_possible_redirect_loops', false ) ) {
				if ( srm_check_for_possible_redirect_loops() ) {
					?>
					<div class="updated">
						<p><?php esc_html_e( 'Safe Redirect Manager Warning: Possible redirect loops and/or chains have been created.', 'safe-redirect-manager' ); ?></p>
					</div>
				<?php
				}
			} if ( srm_max_redirects_reached() ) {
				?>
				<?php
				if ( 'post-new.php' === $hook_suffix ) :
?>
<style type="text/css">#post { display: none; }</style><?php endif; ?>
				<div class="error">
					<p><?php esc_html_e( 'Safe Redirect Manager Error: You have reached the maximum allowable number of redirects', 'safe-redirect-manager' ); ?></p>
				</div>
			<?php
			}
		}
	}

	/**
	 * Filters title out for redirect from in post manager
	 *
	 * @since 1.0
	 * @param string $title
	 * @param int    $post_id
	 * @uses is_admin, get_post_meta
	 * @return string
	 */
	public function filter_admin_title( $title, $post_id = 0 ) {
		if ( ! is_admin() ) {
			return $title;
		}

		$redirect = get_post( $post_id );
		if ( empty( $redirect ) ) {
			return $title;
		}

		if ( $redirect->post_type !== 'redirect_rule' ) {
			return $title;
		}

		$redirect_from = get_post_meta( $post_id, '_redirect_rule_from', true );
		if ( ! empty( $redirect_from ) ) {
			return $redirect_from;
		}

		return $title;
	}

	/**
	 * Customizes updated messages for redirects
	 *
	 * @since 1.0
	 * @param array $messages
	 * @uses esc_url, get_permalink, add_query_var, wp_post_revision_title
	 * @return array
	 */
	public function filter_redirect_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['redirect_rule'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( esc_html__( 'Redirect rule updated.', 'safe-redirect-manager' ), esc_url( get_permalink( $post_ID ) ) ),
			2  => esc_html__( 'Custom field updated.', 'safe-redirect-manager' ),
			3  => esc_html__( 'Custom field deleted.', 'safe-redirect-manager' ),
			4  => esc_html__( 'Redirect rule updated.', 'safe-redirect-manager' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Redirect rule restored to revision from %s', 'safe-redirect-manager' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( esc_html__( 'Redirect rule published.', 'safe-redirect-manager' ), esc_url( get_permalink( $post_ID ) ) ),
			7  => esc_html__( 'Redirect rule saved.', 'safe-redirect-manager' ),
			8  => sprintf( esc_html__( 'Redirect rule submitted.', 'safe-redirect-manager' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9  => sprintf(
				esc_html__( 'Redirect rule scheduled for: %1$s.', 'safe-redirect-manager' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( esc_html__( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) )
			),
			10 => sprintf( esc_html__( 'Redirect rule draft updated.', 'safe-redirect-manager' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

	/**
	 * Clear redirect cache if appropriate post type is transitioned
	 *
	 * @since 1.0
	 * @param string $new_status
	 * @param string $old_status
	 * @param object $post
	 * @return void
	 */
	public function action_transition_post_status( $new_status, $old_status, $post ) {
		if ( ! is_object( $post ) ) {
			return;
		}

		// recreate redirect cache
		if ( 'redirect_rule' === $post->post_type ) {
			srm_flush_cache();
		}
	}

	/**
	 * Displays custom columns on redirect manager screen
	 *
	 * @since 1.0
	 * @param string $column
	 * @param int    $post_id
	 * @uses get_post_meta, esc_html, admin_url
	 * @return void
	 */
	public function action_custom_redirect_columns( $column, $post_id ) {
		if ( 'srm_redirect_rule_to' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_redirect_rule_to', true ) );
		} elseif ( 'srm_redirect_rule_status_code' === $column ) {
			echo absint( get_post_meta( $post_id, '_redirect_rule_status_code', true ) );
		}
	}

	/**
	 * Add new columns to manage redirect screen
	 *
	 * @since 1.0
	 * @param array $columns
	 * @return array
	 */
	public function filter_redirect_columns( $columns ) {
		$columns['srm_redirect_rule_to']          = esc_html__( 'Redirect To', 'safe-redirect-manager' );
		$columns['srm_redirect_rule_status_code'] = esc_html__( 'HTTP Status Code', 'safe-redirect-manager' );

		// Change the title column
		$columns['title'] = esc_html__( 'Redirect From', 'safe-redirect-manager' );

		// Move date column to the back
		unset( $columns['date'] );
		$columns['date'] = esc_html__( 'Date', 'safe-redirect-manager' );

		return $columns;
	}

	/**
	 * Saves meta info for redirect rules
	 *
	 * @since 1.0
	 * @param int $post_id
	 * @uses current_user_can, get_post_type, wp_verify_nonce, update_post_meta, delete_post_meta
	 * @return void
	 */
	public function action_save_post( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' === get_post_type( $post_id ) ) {
			return;
		}

		// Update post meta for redirect rules
		if ( ! empty( $_POST['srm_redirect_nonce'] ) && wp_verify_nonce( $_POST['srm_redirect_nonce'], 'srm-save-redirect-meta' ) ) {

			if ( ! empty( $_POST['srm_redirect_rule_from_regex'] ) ) {
				$allow_regex = (bool) $_POST['srm_redirect_rule_from_regex'];
				update_post_meta( $post_id, '_redirect_rule_from_regex', $allow_regex );
			} else {
				$allow_regex = false;
				delete_post_meta( $post_id, '_redirect_rule_from_regex' );
			}

			if ( ! empty( $_POST['srm_redirect_rule_from'] ) ) {
				update_post_meta( $post_id, '_redirect_rule_from', srm_sanitize_redirect_from( $_POST['srm_redirect_rule_from'], $allow_regex ) );
			} else {
				delete_post_meta( $post_id, '_redirect_rule_from' );
			}

			if ( ! empty( $_POST['srm_redirect_rule_to'] ) ) {
				update_post_meta( $post_id, '_redirect_rule_to', srm_sanitize_redirect_to( $_POST['srm_redirect_rule_to'] ) );
			} else {
				delete_post_meta( $post_id, '_redirect_rule_to' );
			}

			if ( ! empty( $_POST['srm_redirect_rule_status_code'] ) ) {
				update_post_meta( $post_id, '_redirect_rule_status_code', absint( $_POST['srm_redirect_rule_status_code'] ) );
			} else {
				delete_post_meta( $post_id, '_redirect_rule_status_code' );
			}

			/**
			 * This fixes an important bug where the redirect cache was not up-to-date. Previously the cache was only being
			 * updated on transition_post_status which gets called BEFORE save post. But since save_post is where all the important
			 * redirect info is saved, updating the cache before it is not sufficient.
			 */
			srm_flush_cache();
		}
	}

	/**
	 * Registers post types for plugin
	 *
	 * @since 1.0
	 * @uses register_post_type, _x, plugins_url, apply_filters
	 * @return void
	 */
	public function action_register_post_types() {
		$redirect_labels     = array(
			'name'               => esc_html_x( 'Safe Redirect Manager', 'post type general name', 'safe-redirect-manager' ),
			'singular_name'      => esc_html_x( 'Redirect', 'post type singular name', 'safe-redirect-manager' ),
			'add_new'            => _x( 'Create Redirect Rule', 'redirect rule', 'safe-redirect-manager' ),
			'add_new_item'       => esc_html__( 'Safe Redirect Manager', 'safe-redirect-manager' ),
			'edit_item'          => esc_html__( 'Edit Redirect Rule', 'safe-redirect-manager' ),
			'new_item'           => esc_html__( 'New Redirect Rule', 'safe-redirect-manager' ),
			'all_items'          => esc_html__( 'Safe Redirect Manager', 'safe-redirect-manager' ),
			'view_item'          => esc_html__( 'View Redirect Rule', 'safe-redirect-manager' ),
			'search_items'       => esc_html__( 'Search Redirects', 'safe-redirect-manager' ),
			'not_found'          => esc_html__( 'No redirect rules found.', 'safe-redirect-manager' ),
			'not_found_in_trash' => esc_html__( 'No redirect rules found in trash.', 'safe-redirect-manager' ),
			'parent_item_colon'  => '',
			'menu_name'          => esc_html__( 'Safe Redirect Manager', 'safe-redirect-manager' ),
		);

		$redirect_capability = 'srm_manage_redirects';

        $roles = array( 'administrator' );

        foreach ( $roles as $role ) {
			$role = get_role( $role );

			if ( empty( $role ) || $role->has_cap( $redirect_capability ) ) {
				continue;
			}

            $role->add_cap( $redirect_capability );
        }

		$redirect_capability = apply_filters( 'srm_restrict_to_capability', $redirect_capability );

		$capabilities        = array(
			'edit_post'          => $redirect_capability,
			'read_post'          => $redirect_capability,
			'delete_post'        => $redirect_capability,
			'delete_posts'       => $redirect_capability,
			'edit_posts'         => $redirect_capability,
			'edit_others_posts'  => $redirect_capability,
			'publish_posts'      => $redirect_capability,
			'read_private_posts' => $redirect_capability,
		);

		$redirect_args = array(
			'labels'               => $redirect_labels,
			'public'               => false,
			'publicly_queryable'   => true,
			'show_ui'              => true,
			'show_in_menu'         => 'tools.php',
			'query_var'            => false,
			'rewrite'              => false,
			'capability_type'      => 'post',
			'capabilities'         => $capabilities,
			'has_archive'          => false,
			'hierarchical'         => false,
			'register_meta_box_cb' => array( $this, 'action_redirect_rule_metabox' ),
			'menu_position'        => 80,
			'supports'             => array( '' ),
		);
		register_post_type( 'redirect_rule', $redirect_args );
	}

	/**
	 * Registers meta boxes for redirect rule post type
	 *
	 * @since 1.0
	 * @uses add_meta_box
	 * @return void
	 */
	public function action_redirect_rule_metabox() {
		add_meta_box( 'redirect_settings', esc_html__( 'Redirect Settings', 'safe-redirect-manager' ), array( $this, 'redirect_rule_metabox' ), 'redirect_rule', 'normal', 'core' );
	}

	/**
	 * Echoes HTML for redirect rule meta box
	 *
	 * @since 1.0
	 * @param object $post
	 * @uses wp_nonce_field, get_post_meta, esc_attr, selected
	 * @return void
	 */
	public function redirect_rule_metabox( $post ) {
		wp_nonce_field( 'srm-save-redirect-meta', 'srm_redirect_nonce' );

		$redirect_from = get_post_meta( $post->ID, '_redirect_rule_from', true );
		$redirect_to   = get_post_meta( $post->ID, '_redirect_rule_to', true );
		$status_code   = get_post_meta( $post->ID, '_redirect_rule_status_code', true );
		$enable_regex  = get_post_meta( $post->ID, '_redirect_rule_from_regex', true );

		if ( empty( $status_code ) ) {
			$status_code = apply_filters( 'srm_default_direct_status', 302 );
		}
		?>
		<p>
			<label for="srm_redirect_rule_from"><?php esc_html_e( 'Redirect From:', 'safe-redirect-manager' ); ?></label><br />
			<input type="text" name="srm_redirect_rule_from" id="srm_redirect_rule_from" value="<?php echo esc_attr( $redirect_from ); ?>" />
			<input type="checkbox" name="srm_redirect_rule_from_regex" id="srm_redirect_rule_from_regex" <?php checked( true, (bool) $enable_regex ); ?> value="1" />
			<label for="srm_redirect_rule_from_regex"><?php esc_html_e( 'Enable Regular Expressions (advanced)', 'safe-redirect-manager' ); ?></label>
		</p>
		<p class="description"><?php esc_html_e( 'This path should be relative to the root of this WordPress installation (or the sub-site, if you are running a multi-site). Appending a (*) wildcard character will match all requests with the base. Warning: Enabling regular expressions will disable wildcards and completely change the way the * symbol is interpretted.', 'safe-redirect-manager' ); ?></p>

		<p>
			<label for="srm_redirect_rule_to"><?php esc_html_e( 'Redirect To:', 'safe-redirect-manager' ); ?></label><br />
			<input class="widefat" type="text" name="srm_redirect_rule_to" id="srm_redirect_rule_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
		</p>
		<p class="description"><?php esc_html_e( 'This can be a URL or a path relative to the root of your website (not your WordPress installation). Ending with a (*) wildcard character will append the request match to the redirect.', 'safe-redirect-manager' ); ?></p>

		<p>
			<label for="srm_redirect_rule_status_code"><?php esc_html_e( 'HTTP Status Code:', 'safe-redirect-manager' ); ?></label>
			<select name="srm_redirect_rule_status_code" id="srm_redirect_rule_status_code">
				<?php foreach ( srm_get_valid_status_codes() as $code ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $status_code, $code ); ?>><?php echo esc_html( $code . ' ' . $this->status_code_labels[ $code ] ); ?></option>
				<?php endforeach; ?>
			</select>
			<em><?php esc_html_e( "If you don't know what this is, leave it as is.", 'safe-redirect-manager' ); ?></em>
		</p>
	<?php
	}

	/**
	 * Return a permalink for a redirect post, which is the "redirect from"
	 * URL for that redirect.
	 *
	 * @since 1.7
	 * @param string $permalink The permalink
	 * @param object $post A Post object
	 * @uses home_url, get_post_meta
	 * @return string The permalink
	 */
	public function filter_post_type_link( $permalink, $post ) {
		if ( 'redirect_rule' !== $post->post_type ) {
			return $permalink;
		}

		// We can't do anything to provide a permalink
		// for regex enabled redirects.
		if ( get_post_meta( $post->ID, '_redirect_rule_from_regex', true ) ) {
			return $permalink;
		}

		// We can't do anything if there is a wildcard in the redirect from
		$redirect_from = get_post_meta( $post->ID, '_redirect_rule_from', true );
		if ( false !== strpos( $redirect_from, '*' ) ) {
			return $permalink;
		}

		// Use default permalink if no $redirect_from exists - this prevents duplicate GUIDs
		if ( empty( $redirect_from ) ) {
			return $permalink;
		}

		return home_url( $redirect_from );
	}

	/**
	 * Return singleton instance of class
	 *
	 * @return object
	 * @since 1.8
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}

SRM_Post_Type::factory();
