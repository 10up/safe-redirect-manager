<?php
/**
 * Setup SRM post type
 *
 * @package safe-redirect-manager
 */

/**
 * Post type class
 */
class SRM_Post_Type {

	/**
	 * Status code lables for reuse
	 *
	 * @var array
	 */
	public $status_code_labels = array(); // Defined later to allow i18n

	/**
	 * We have to store the redirect search so we can grab it later
	 *
	 * @var string
	 */
	private $redirect_search_term;

	/**
	 * Sets up redirect manager
	 *
	 * @since 1.8
	 */
	public function setup() {
		add_action( 'init', array( $this, 'init_properties' ) );
		add_action( 'init', array( $this, 'action_register_post_types' ) );
		add_action( 'admin_init', array( $this, 'init_search_filters' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		add_filter( 'manage_redirect_rule_posts_columns', array( $this, 'filter_redirect_columns' ) );
		add_filter( 'manage_edit-redirect_rule_sortable_columns', array( $this, 'filter_redirect_sortable_columns' ) );
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
		add_filter( 'default_hidden_columns', array( $this, 'filter_hidden_columns' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_resources' ), 10, 0 );
		add_action( 'wp_ajax_srm_validate_from_url', array( $this, 'srm_validate_from_url' ), 10, 0 );
		add_action( 'wp_ajax_srm_autocomplete', array( $this, 'srm_autocomplete' ), 10, 0 );
	}

	/**
	 * Initialises class properties.
	 *
	 * @since 2.0.0
	 */
	public function init_properties() {
		$this->status_code_labels = srm_get_valid_status_codes_data();
	}

	/**
	 * Setup search filters
	 */
	public function init_search_filters() {
		$redirect_capability = $this->get_redirect_capability();

		if ( ! is_admin() ) {
			return;
		}

		if ( ! current_user_can( $redirect_capability ) ) {
			return;
		}

		add_action( 'pre_get_posts', array( $this, 'disable_core_search' ) );
		add_filter( 'posts_clauses', array( $this, 'filter_search_clauses' ), 10, 2 );

		add_filter( 'post_row_actions', array( $this, 'filter_disable_quick_edit' ), 10, 2 );
	}

	/**
	 * Hide order column by default
	 *
	 * @param  array $hidden Array of hidden post types
	 * @since  1.9
	 * @return array
	 */
	public function filter_hidden_columns( $hidden ) {
		if ( empty( $_GET['post_type'] ) || 'redirect_rule' !== $_GET['post_type'] ) {
			return $hidden;
		}

		$hidden[] = 'menu_order';

		return $hidden;
	}

	/**
	 * Remove quick edit
	 *
	 * @param  array            $actions Array of actions
	 * @param  int|WP_Post|null $post Post object
	 * @since  1.8
	 * @return array
	 */
	public function filter_disable_quick_edit( $actions, $post ) {
		if ( 'redirect_rule' === get_post_type( $post ) && isset( $actions['inline hide-if-no-js'] ) ) {
			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['view'] );
		}

		return $actions;
	}

	/**
	 * We don't need core's fancy search functionality since we provide our own.
	 *
	 * @param  \WP_Query $query WP Query object
	 */
	public function disable_core_search( $query ) {
		if ( $query->is_search() && 'redirect_rule' === $query->get( 'post_type' ) ) {
			// Store a reference to the search term for later use.
			$this->redirect_search_term = $query->get( 's' );
			// Don't let core build it's search clauses since we override them.
			$query->set( 's', '' );
		}
	}

	/**
	 * Build custom JOIN + WHERE clauses to do a more direct search through meta.
	 *
	 * @param  array    $clauses Array of SQL clauses
	 * @param  WP_Query $query WP_Query object
	 * @return array
	 */
	public function filter_search_clauses( $clauses, $query ) {
		global $wpdb;

		if ( $this->redirect_search_term ) {
			$search_term      = $this->redirect_search_term;
			$search_term_like = '%' . $wpdb->esc_like( $search_term ) . '%';

			$query->set( 's', $this->redirect_search_term );
			unset( $this->redirect_search_term );

			$clauses['distinct'] = 'DISTINCT';

			$clauses['join'] .= " LEFT JOIN $wpdb->postmeta AS pm ON ($wpdb->posts.ID = pm.post_id) ";

			$clauses['where'] = $wpdb->prepare(
				"AND (
					(
						pm.meta_value LIKE %s
						AND pm.meta_key = '_redirect_rule_from'
					) OR (
						pm.meta_value LIKE %s
						AND pm.meta_key = '_redirect_rule_to'
					)
				)
				AND $wpdb->posts.post_type = 'redirect_rule'
				AND $wpdb->posts.post_status IN ( 'publish', 'future', 'draft', 'pending' )
				",
				$search_term_like,
				$search_term_like
			);
		}

		return $clauses;
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
	 * @param  array $actions Array of actions
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
			 * Filter whether possible redirect loop checking is enabled or not.
			 *
			 * @hook srm_check_for_possible_redirect_loops
			 * @param {bool} $check_possible_loop Whether to check for redirect loops. Default true.
			 * @returns {bool} Bool to check for redirect loops.
			 */
			$possible_loop = apply_filters( 'srm_check_for_possible_redirect_loops', true );

			if ( $possible_loop ) {
				$cycle_source = SRM_Loop_Detection::detect_redirect_loops();
				$paths        = SRM_Loop_Detection::get_cycle_source( $cycle_source );

				if ( ! empty( $cycle_source ) ) {
					?>
					<div class="notice notice-error">
						<p><?php esc_html_e( 'Safe Redirect Manager Error: The following redirects with the "Redirect To" value have created redirect loops.', 'safe-redirect-manager' ); ?></p>
						<ul style="list-style: inside;">
							<?php foreach ( $paths as $path ) : ?>
								<li>
								<?php
									printf(
										'<a href="%s">%s</a>',
										esc_url( get_edit_post_link( esc_html( $path['id'] ) ) ),
										esc_html( $path['path'] )
									);
								?>
								</li>
							<?php endforeach; ?>
						</ul style>
					</div>
					<?php
				}
			}

			if ( srm_max_redirects_reached() ) {

				if ( 'post-new.php' === $hook_suffix ) {
					?>
					<style type="text/css">#post { display: none; }</style>
					<?php
				}
				?>
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
	 * @param string $title Admin title
	 * @param int    $post_id Post ID
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

		if ( 'redirect_rule' !== $redirect->post_type ) {
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
	 * @param array $messages Array of messages
	 * @uses esc_url, get_permalink, add_query_var, wp_post_revision_title
	 * @return array
	 */
	public function filter_redirect_updated_messages( $messages ) {
		global $post, $post_ID;

		$message_tpl = function( $message ) {
			return sprintf(
				/* translators: %1%s: message status, %2%s: URL to the list of redirect rules */
				__( 'Redirect rule %1$s. <a href="%2$s">&larr; Back to rules</a>', 'safe-redirect-manager' ),
				$message,
				esc_url( admin_url( 'edit.php?post_type=redirect_rule' ) )
			);
		};

		$messages['redirect_rule'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => $message_tpl( esc_html__( 'updated', 'safe-redirect-manager' ) ),
			2  => esc_html__( 'Custom field updated.', 'safe-redirect-manager' ),
			3  => esc_html__( 'Custom field deleted.', 'safe-redirect-manager' ),
			4  => $message_tpl( __( 'updated', 'safe-redirect-manager' ) ),
			5  => isset( $_GET['revision'] )
				? $message_tpl(
					sprintf(
						/* translators: %s: the revision title */
						esc_html__( 'restored to revision from %s', 'safe-redirect-manager' ),
						wp_post_revision_title( (int) $_GET['revision'], false )
					)
				)
				: false,
			6  => $message_tpl( esc_html__( 'published', 'safe-redirect-manager' ) ),
			7  => $message_tpl( esc_html__( 'saved', 'safe-redirect-manager' ) ),
			8  => $message_tpl( esc_html__( 'submitted', 'safe-redirect-manager' ) ),
			9  => $message_tpl(
				sprintf(
					/* translators: %s: publish box date format, see http://php.net/date */
					esc_html__( 'scheduled for %s', 'safe-redirect-manager' ),
					date_i18n( esc_html__( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
				)
			),
			10 => $message_tpl( esc_html__( 'draft updated', 'safe-redirect-manager' ) ),
		);

		return $messages;
	}

	/**
	 * Clear redirect cache if appropriate post type is transitioned
	 *
	 * @since 1.0
	 * @param string  $new_status New post status
	 * @param string  $old_status Old post status
	 * @param WP_Post $post Post object
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
	 * @param string $column Column name
	 * @param int    $post_id Post Id
	 * @uses get_post_meta, esc_html, admin_url
	 * @return void
	 */
	public function action_custom_redirect_columns( $column, $post_id ) {
		if ( 'srm_redirect_rule_to' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_redirect_rule_to', true ) );
		} elseif ( 'srm_redirect_rule_status_code' === $column ) {
			echo absint( get_post_meta( $post_id, '_redirect_rule_status_code', true ) );
		} elseif ( 'menu_order' === $column ) {
			global $post;
			echo esc_html( $post->menu_order );
		}
	}

	/**
	 * Add new columns to manage redirect screen
	 *
	 * @since 1.0
	 * @param array $columns Array columns
	 * @return array
	 */
	public function filter_redirect_columns( $columns ) {
		$columns['srm_redirect_rule_to']          = esc_html__( 'Redirect To', 'safe-redirect-manager' );
		$columns['srm_redirect_rule_status_code'] = esc_html__( 'HTTP Status Code', 'safe-redirect-manager' );
		$columns['menu_order']                    = esc_html__( 'Order', 'safe-redirect-manager' );

		// Change the title column
		$columns['title'] = esc_html__( 'Redirect From', 'safe-redirect-manager' );

		// Move date column to the back
		unset( $columns['date'] );
		$columns['date'] = esc_html__( 'Date', 'safe-redirect-manager' );

		return $columns;
	}

	/**
	 * Allow menu_order column to be sortable.
	 *
	 * @param array $columns Array of columns
	 * @since 1.9
	 * @return array
	 */
	public function filter_redirect_sortable_columns( $columns ) {
		$columns['menu_order'] = 'menu_order';
		return $columns;
	}

	/**
	 * Saves meta info for redirect rules
	 *
	 * @since 1.0
	 * @param int $post_id Post ID
	 * @uses current_user_can, get_post_type, wp_verify_nonce, update_post_meta, delete_post_meta
	 * @return void
	 */
	public function action_save_post( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'revision' === get_post_type( $post_id ) ) {
			return;
		}

		// Update post meta for redirect rules
		if ( ! empty( $_POST['srm_redirect_nonce'] ) && wp_verify_nonce( $_POST['srm_redirect_nonce'], 'srm-save-redirect-meta' ) && current_user_can( 'edit_post', $post_id ) ) {

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

			if ( ! empty( $_POST['srm_force_https'] ) ) {
				update_post_meta( $post_id, '_force_https', true );
			} else {
				delete_post_meta( $post_id, '_force_https' );
			}

			if ( ! empty( $_POST['srm_redirect_rule_message'] ) ) {
				update_post_meta( $post_id, '_redirect_rule_message', sanitize_text_field( $_POST['srm_redirect_rule_message'] ) );
			} else {
				delete_post_meta( $post_id, '_redirect_rule_message' );
			}

			if ( ! empty( $_POST['srm_redirect_rule_notes'] ) ) {
				update_post_meta( $post_id, '_redirect_rule_notes', sanitize_text_field( $_POST['srm_redirect_rule_notes'] ) );
			} else {
				delete_post_meta( $post_id, '_redirect_rule_notes' );
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
	 * Get required capability for managing redirects
	 *
	 * @return string
	 */
	protected function get_redirect_capability() {
		$redirect_capability = 'srm_manage_redirects';

		$roles = array( 'administrator' );

		foreach ( $roles as $role ) {
			$role = get_role( $role );

			if ( empty( $role ) || $role->has_cap( $redirect_capability ) ) {
				continue;
			}

			$role->add_cap( $redirect_capability );
		}

		/**
		 * Filter the capability required to manage redirects.
		 *
		 * @hook srm_restrict_to_capability
		 * @param {string} $redirect_capability The required capability. Default `srm_manage_redirects`.
		 * @returns {string} The required capability.
		 */
		return apply_filters( 'srm_restrict_to_capability', $redirect_capability );
	}

	/**
	 * Registers post types for plugin
	 *
	 * @since 1.0
	 * @uses register_post_type, _x, plugins_url, apply_filters
	 * @return void
	 */
	public function action_register_post_types() {
		$redirect_labels = array(
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

		$redirect_capability = $this->get_redirect_capability();

		$capabilities = array(
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
			'supports'             => array( 'page-attributes' ),
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
	 * @param WP_Post $post Post object
	 * @uses wp_nonce_field, get_post_meta, esc_attr, selected
	 * @return void
	 */
	public function redirect_rule_metabox( $post ) {
		wp_nonce_field( 'srm-save-redirect-meta', 'srm_redirect_nonce' );

		$redirect_from    = get_post_meta( $post->ID, '_redirect_rule_from', true );
		$redirect_to      = get_post_meta( $post->ID, '_redirect_rule_to', true );
		$redirect_notes   = get_post_meta( $post->ID, '_redirect_rule_notes', true );
		$status_code      = get_post_meta( $post->ID, '_redirect_rule_status_code', true );
		$enable_regex     = get_post_meta( $post->ID, '_redirect_rule_from_regex', true );
		$force_https      = get_post_meta( $post->ID, '_force_https', true );
		$redirect_message = get_post_meta( $post->ID, '_redirect_rule_message', true );

		if ( empty( $status_code ) ) {
			/**
			 * Filter the default HTTP status code to redirect with.
			 *
			 * Which HTTP redirect code safe redirect manager should default to. This can
			 * be overridden in the dashboard for each redirect.
			 *
			 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Redirections
			 *
			 * @hook srm_default_direct_status
			 * @param {int} $default_status_code Default redirect status. Default value `302`.
			 * @returns {int} Redirect status.
			 */
			$status_code = apply_filters( 'srm_default_direct_status', 302 );
		}

		?>
		<div class="notice notice-error" id="message" style="display: none;"></div>
		<p>
			<label for="srm_redirect_rule_from"><strong><?php esc_html_e( '* Redirect From:', 'safe-redirect-manager' ); ?></strong></label><br />
			<input type="text" name="srm_redirect_rule_from" id="srm_redirect_rule_from" value="<?php echo esc_attr( $redirect_from ); ?>" />
			<input type="checkbox" name="srm_redirect_rule_from_regex" id="srm_redirect_rule_from_regex" <?php checked( true, (bool) $enable_regex ); ?> value="1" />
			<label for="srm_redirect_rule_from_regex"><?php esc_html_e( 'Enable Regular Expressions (advanced)', 'safe-redirect-manager' ); ?></label>
		</p>
		<p class="description"><?php esc_html_e( 'This path should be relative to the root of this WordPress installation (or the sub-site, if you are running a multi-site). Appending a (*) wildcard character will match all requests with the base. Warning: Enabling regular expressions will disable wildcards and completely change the way the * symbol is interpreted.', 'safe-redirect-manager' ); ?></p>

		<p>
			<label for="srm_redirect_rule_to"><strong><?php esc_html_e( '* Redirect To:', 'safe-redirect-manager' ); ?></strong></label><br />
			<input class="widefat" type="text" name="srm_redirect_rule_to" id="srm_redirect_rule_to" value="<?php echo esc_attr( urldecode( $redirect_to ) ); ?>" />
		</p>
		<p class="description" id="srm_to_disabled_message" style="display:none;"><em><?php esc_html_e( 'The "Redirect to" value doesn\'t apply for 4xx error codes.', 'safe-redirect-manager' ); ?></em></p>
		<p class="description"><?php esc_html_e( 'This can be a URL or a path relative to the root of your website (not your WordPress installation). Ending with a (*) wildcard character will append the request match to the redirect.', 'safe-redirect-manager' ); ?></p>

		<p>
			<label for="srm_redirect_rule_status_code"><strong><?php esc_html_e( '* HTTP Status Code:', 'safe-redirect-manager' ); ?></strong></label><br/>
			<select name="srm_redirect_rule_status_code" id="srm_redirect_rule_status_code">
				<?php foreach ( srm_get_valid_status_codes() as $code ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $status_code, $code ); ?>><?php echo esc_html( $code . ' ' . $this->status_code_labels[ $code ] ); ?></option>
				<?php endforeach; ?>
			</select>
			<em><?php esc_html_e( "If you don't know what this is, leave it as is.", 'safe-redirect-manager' ); ?></em>
		</p>

		<p id="srm_redirect_rule_message_container">
			<label for="srm_redirect_rule_message"><strong><?php esc_html_e( 'Message:', 'safe-redirect-manager' ); ?></strong></label>
			<textarea name="srm_redirect_rule_message" id="srm_redirect_rule_message" class="widefat"><?php echo esc_textarea( $redirect_message ); ?></textarea>
			<em><?php esc_html_e( 'Optionally display a message to users when they navigate to a 403 or 410 endpoint.', 'safe-redirect-manager' ); ?></em>
		</p>

		<p>
			<label><strong><?php esc_html_e( 'Redirect Protocol:', 'safe-redirect-manager' ); ?></strong></label><br/>
			<label>
				<input type="checkbox" name="srm_force_https" value="1" <?php checked( $force_https, true ); ?>/> <?php esc_html_e( 'Force https', 'safe-redirect-manager' ); ?>
			</label>
		</p>

		<p>
			<label for="srm_redirect_rule_notes"><strong><?php esc_html_e( 'Notes:', 'safe-redirect-manager' ); ?></strong></label>
			<textarea name="srm_redirect_rule_notes" id="srm_redirect_rule_notes" class="widefat"><?php echo esc_textarea( $redirect_notes ); ?></textarea>
			<em><?php esc_html_e( 'Optionally leave notes on this redirect e.g. why was it created.', 'safe-redirect-manager' ); ?></em>
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

	/**
	 * Load scripts.
	 *
	 * @return void
	 */
	public function load_resources() {
		if ( 'redirect_rule' === get_post_type() ) {
			wp_enqueue_style( 'redirectjs', plugin_dir_url( 'safe-redirect-manager/safe-redirect-manager.php' ) . 'assets/css/redirect.css', array(), SRM_VERSION );
			wp_enqueue_script( 'redirectjs', plugin_dir_url( 'safe-redirect-manager/safe-redirect-manager.php' ) . 'assets/js/redirect.js', array( 'jquery' ), SRM_VERSION );
			wp_localize_script(
				'redirectjs',
				'redirectValidation',
				array(
					'urlError'   => __( 'There are some issues validating the URL. Please try again.', 'safe-redirect-manager' ),
					'fail'       => __( 'There is an existing redirect with the same Redirect From URL. You may <a href="%s">Edit</a> the redirect or try other `from` URL.', 'safe-redirect-manager' ),
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( 'srm_autocomplete_nonce' ),
				)
			);
		}
	}

	/**
	 * Fetches posts and pages based on the 'Redirect to:' field value.
	 * This function handles an AJAX request, returning a JSON array of posts
	 * and pages that match the search term.
	 *
	 * @return void
	 */
	public function srm_autocomplete() {
		check_ajax_referer( 'srm_autocomplete_nonce', 'security' );

		if ( ! current_user_can( 'srm_manage_redirects' ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}

		$search_term = isset( $_REQUEST['term'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['term'] ) ) : false;
		if ( ! $search_term ) {
			echo wp_json_encode( array() );
			wp_die();
		}

		// Remove the beginning / to prevent 0 results.
		$search_term = ltrim( $search_term, '/' );

		$query = get_posts(
			array(
				// Get publicly viewable post types, except for redirect_rule.
				'post_type'      => array_diff_key(
					array_filter(
						get_post_types(),
						'is_post_type_viewable'
					),
					array(
						'redirect_rule' => '',
					)
				),
				's'              => $search_term,
				'posts_per_page' => 5,
			)
		);

		if ( ! $query ) {
			echo wp_json_encode( array() );
			wp_die();
		}

		$suggestions = array();
		foreach ( $query as $key => $post ) {
			$suggestions[] = array(
				'relative_url' => wp_make_link_relative( get_the_permalink( $post->ID ) ),
				'post_title'   => $post->post_title,
				'post_type'    => $post->post_type,
			);
		}

		echo wp_json_encode( $suggestions );
		wp_die();
	}

	/**
	 * Validate whether the from URL already exists or not.
	 *
	 * @return void
	 */
	public function srm_validate_from_url() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! isset( $_GET['from'] ) ) {
			echo 0;
			die();
		}

		$_wpnonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
		if ( ! wp_verify_nonce( $_wpnonce, 'srm-save-redirect-meta' ) ) {
			echo 0;
			die();
		}

		$from = srm_sanitize_redirect_from( wp_unslash( $_GET['from'] ) );

		/**
		 * SRM treats '/sample-page' and 'sample-page' equally.
		 * If the $from value does not start with a forward slash,
		 * then we normalize it by adding one.
		 */
		$from = '/' === substr( $from, 0, 1 ) ? $from : '/' . $from;

		$existing_post_ids = new WP_Query(
			[
				'meta_key'               => '_redirect_rule_from',
				'meta_value'             => $from,
				'fields'                 => 'ids',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'post_type'              => 'redirect_rule',
				'post_status'            => 'publish',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		// If no posts found, then bail out.
		if ( empty( $existing_post_ids->posts ) ) {
			echo 1;
			die();
		}

		$existing_post_id = $existing_post_ids->posts[0];

		echo esc_url( get_edit_post_link( $existing_post_id ) );
		die();
	}
}
