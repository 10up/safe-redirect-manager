<?php
/*
Plugin Name: Safe Redirect Manager
Plugin URI: http://www.10up.com
Description: Easily and safely manage HTTP redirects.
Author: Taylor Lovett (10up LLC), VentureBeat
Version: 1.4.3-working
Author URI: http://www.10up.com

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

class SRM_Safe_Redirect_Manager {

	public $redirect_post_type = 'redirect_rule';
	private $redirect_nonce_name = 'srm_redirect_nonce';
	private $redirect_nonce_action = 'srm-save-redirect-meta';

	public $meta_key_redirect_from = '_redirect_rule_from';
	public $meta_key_redirect_to = '_redirect_rule_to';
	public $meta_key_redirect_status_code = '_redirect_rule_status_code';

	public $cache_key_redirects = '_srm_redirects';

	public $valid_status_codes = array( 301, 302, 303, 403, 404 );

	private $whitelist_hosts = array();

	public $default_max_redirects = 150;

	/**
	 * Sets up redirect manager
	 *
	 * @since 1.0
	 * @uses add_action, add_filter
	 * @return object
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'init', array( $this, 'action_register_post_types' ) );
		add_action( 'parse_request', array( $this, 'action_parse_request' ), 0 );
		add_action( 'after_theme_setup', array( $this, 'action_load_texthost' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		add_filter( 'manage_' . $this->redirect_post_type . '_posts_columns' , array( $this, 'filter_redirect_columns' ) );
		add_action( 'manage_' . $this->redirect_post_type . '_posts_custom_column' , array( $this, 'action_custom_redirect_columns' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'action_transition_post_status' ), 10, 3 );
		add_filter( 'post_updated_messages', array( $this, 'filter_redirect_updated_messages' ) );
		add_action( 'admin_notices', array( $this, 'action_redirect_chain_alert' ) );
		add_filter( 'the_title', array( $this, 'filter_admin_title' ), 100, 2 );
		add_filter( 'bulk_actions-' . 'edit-redirect_rule', array( $this, 'filter_bulk_actions' ) );
		add_action( 'admin_print_styles-edit.php', array( $this, 'action_print_logo_css' ), 10, 1 );
		add_action( 'admin_print_styles-post.php', array( $this, 'action_print_logo_css' ), 10, 1 );
		add_action( 'admin_print_styles-post-new.php', array( $this, 'action_print_logo_css' ), 10, 1 );

		// Search filters
		add_filter( 'posts_join', array( $this, 'filter_search_join' ) );
		add_filter( 'posts_where', array( $this, 'filter_search_where' ) );
		add_filter( 'posts_distinct', array( $this, 'filter_search_distinct' ) );
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
		if ( $this->redirect_post_type != get_query_var( 'post_type' ) )
			return $join;

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
		if ( $this->redirect_post_type != get_query_var( 'post_type' ) )
			return $distinct;

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
		if ( $this->redirect_post_type != get_query_var( 'post_type' ) || ! is_search() || empty( $where ) )
			return $where;

		$exact = get_query_var( 'exact' );
		$n = ( ! empty( $exact ) ) ? '' : '%';

		$search = '';
		$seperator = '';
		$terms = $this->get_search_terms();
		$search .= '(';

		// we check the meta values against each term in the search
		foreach ( $terms as $term ) {
			$search .= $seperator;
			$search .= sprintf( "( ( m.meta_value LIKE '%s%s%s' AND m.meta_key = '%s') OR ( m.meta_value LIKE '%s%s%s' AND m.meta_key = '%s') )", $n, $term, $n, $this->meta_key_redirect_from, $n, $term, $n, $this->meta_key_redirect_to );
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
				#icon-tools {
					background: url("<?php echo plugins_url( 'images/icon32x32.png', __FILE__ ); ?>") no-repeat top left !important;
					margin-right: 0;
				}
				#visibility, .view-switch, .posts .inline-edit-col-left .inline-edit-group {
					display: none;
				}
			</style>
		<?php
		}
	}

	/**
	 * Removes bulk actions from post manager
	 *
	 * @since 1.0
	 * @return array
	 */
	public function filter_bulk_actions() {
		return array();
	}

	/**
	 * Creates a redirect post, this function will be useful for import/exports scripts
	 *
	 * @param string $redirect_from
	 * @param string $redirect_to
	 * @param int $status_code
	 * @since 1.3
	 * @uses wp_insert_post, update_post_meta
	 * @return int
	 */
	public function create_redirect( $redirect_from, $redirect_to, $status_code ) {
		$sanitized_redirect_from = $this->sanitize_redirect_from( $redirect_from );
		$sanitized_redirect_to = $this->sanitize_redirect_to( $redirect_to );
		$sanitized_status_code = absint( $status_code );

		// check and make sure no parameters are empty or invalid after sanitation
		if ( empty( $sanitized_redirect_from ) || empty( $sanitized_redirect_to ) || ! in_array( $sanitized_status_code, $this->valid_status_codes ) )
			return 0;

		// create the post
		$post_args = array(
			'post_type' => $this->redirect_post_type,
			'post_status' => 'publish',
			'post_author' => 1
		);

		$post_id = wp_insert_post(  $post_args );

		if ( 0 >= $post_id )
			return 0;

		// update the posts meta info
		update_post_meta( $post_id, $this->meta_key_redirect_from, $sanitized_redirect_from );
		update_post_meta( $post_id, $this->meta_key_redirect_to, $sanitized_redirect_to );
		update_post_meta( $post_id, $this->meta_key_redirect_status_code, $sanitized_status_code );

		// We need to update the cache after creating this redirect
		$this->update_redirect_cache();

		return $post_id;
	}

	/**
	 * Whether or not this is an admin page specific to the plugin
	 *
	 * @since 1.1
	 * @uses get_post_type
	 * @return bool
 	 */
	private function is_plugin_page() {
		return (bool) ( get_post_type() == $this->redirect_post_type || ( isset( $_GET['post_type'] ) && $this->redirect_post_type == $_GET['post_type'] ) );
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
			if ( $this->check_for_possible_redirect_loops() ) {
				?>
					<div class="updated">
						<p><?php _e( 'Safe Redirect Manager Warning: Possible redirect loops and/or chains have been created.', 'safe-redirect-manager' ); ?></p>
					</div>
				<?php
				}
		} if ( $this->max_redirects_reached() ) {
			?>
				<?php if ( 'post-new.php' == $hook_suffix ) : ?><style type="text/css">#post { display: none; }</style><?php endif; ?>
				<div class="error">
					<p><?php _e( 'Safe Redirect Manager Error: You have reached the maximum allowable number of redirects', 'safe-redirect-manager' ); ?></p>
				</div>
			<?php
			}
		}
	}

	/**
	 * Returns true if max redirects have been reached
	 *
	 * @since 1.0
	 * @uses apply_filters, get_transient
	 * @return bool
	 */
	public function max_redirects_reached() {
		if ( false === ( $redirects = get_transient( $this->cache_key_redirects ) ) ) {
			$redirects = $this->update_redirect_cache();
		}

		$max_redirects = apply_filters( 'srm_max_redirects', $this->default_max_redirects );

		return ( count( $redirects ) >= $max_redirects );
	}

	/**
	 * Check for potential redirect loops or chains
	 *
	 * @since 1.0
	 * @uses home_url, get_transient
	 * @return boolean
	 */
	public function check_for_possible_redirect_loops() {
		if ( false === ( $redirects = get_transient( $this->cache_key_redirects ) ) ) {
			$redirects = $this->update_redirect_cache();
		}

		$current_url = parse_url( home_url() );
		$this_host = ( is_array( $current_url ) && ! empty( $current_url['host'] ) ) ? $current_url['host'] : '';

		foreach ( $redirects as $redirect ) {
			$redirect_from = $redirect['redirect_from'];

			// check redirect from against all redirect to's
			foreach ( $redirects as $compare_redirect ) {
				$redirect_to = $compare_redirect['redirect_to'];

				$redirect_url = parse_url( $redirect_to );
				$redirect_host = ( is_array( $redirect_url ) && ! empty( $redirect_url['host'] ) ) ? $redirect_url['host'] : '';

				// check if we are redirecting locally
				if ( empty( $redirect_host ) || $redirect_host == $this_host ) {
					$redirect_from_url = preg_replace( '/(http:\/\/|https:\/\/|www\.)/i', '', home_url() . $redirect_from );
					$redirect_to_url = $redirect_to;
					if ( ! preg_match( '/https?:\/\//i', $redirect_to_url ) )
						$redirect_to_url = $this_host . $redirect_to_url;
					else
						$redirect_to_url = preg_replace( '/(http:\/\/|https:\/\/|www\.)/i', '', $redirect_to_url );

					// possible loop/chain found
					if ( $redirect_to_url == $redirect_from_url )
						return true;
				}
			}
		}

		return false;
	}

	/**
	 * Filters title out for redirect from in post manager
	 *
	 * @since 1.0
	 * @param string $title
	 * @param int $post_id
	 * @uses is_admin, get_post_meta
	 * @return string
	 */
	public function filter_admin_title( $title, $post_id = 0 ) {
		if ( ! is_admin() || false === ( $redirect = get_post( $post_id ) ) || $redirect->post_type != $this->redirect_post_type )
			return $title;

		$redirect_from = get_post_meta( $post_id, $this->meta_key_redirect_from, true );
		if ( ! empty( $redirect_from ) )
			return $redirect_from;

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

		$messages[$this->redirect_post_type] = array(
		  0 => '', // Unused. Messages start at index 1.
		  1 => sprintf( __( 'Redirect rule updated.', 'safe-redirect-manager' ), esc_url( get_permalink( $post_ID ) ) ),
		  2 => __( 'Custom field updated.', 'safe-redirect-manager' ),
		  3 => __( 'Custom field deleted.', 'safe-redirect-manager' ),
		  4 => __( 'Redirect rule updated.', 'safe-redirect-manager' ),
		  /* translators: %s: date and time of the revision */
		  5 => isset( $_GET['revision'] ) ? sprintf( __('Redirect rule restored to revision from %s', 'safe-redirect-manager' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		  6 => sprintf( __( 'Redirect rule published.', 'safe-redirect-manager' ), esc_url( get_permalink( $post_ID ) ) ),
		  7 => __( 'Redirect rule saved.', 'safe-redirect-manager' ),
		  8 => sprintf( __( 'Redirect rule submitted.', 'safe-redirect-manager' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		  9 => sprintf( __( 'Redirect rule scheduled for: <strong>%1$s</strong>.', 'safe-redirect-manager' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		  10 => sprintf( __( 'Redirect rule draft updated.', 'safe-redirect-manager' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
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
	 * @uses delete_transient
	 * @return void
	 */
	public function action_transition_post_status( $new_status, $old_status, $post ) {
		if ( ! is_object( $post ) )
			return;

		// recreate redirect cache
		if ( $this->redirect_post_type == $post->post_type ) {
			delete_transient( $this->cache_key_redirects );
			$this->update_redirect_cache();
		}
	}

	/**
	 * Displays custom columns on redirect manager screen
	 *
	 * @since 1.0
	 * @param string $column
	 * @param int $post_id
	 * @uses get_post_meta, esc_html, admin_url
	 * @return void
	 */
	public function action_custom_redirect_columns( $column, $post_id ) {
		if ( 'srm' . $this->meta_key_redirect_to == $column ) {
			echo esc_html( get_post_meta( $post_id, $this->meta_key_redirect_to, true ) );
		} elseif ( 'srm' . $this->meta_key_redirect_status_code == $column ) {
			echo absint( get_post_meta( $post_id, $this->meta_key_redirect_status_code, true ) );
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
		$columns['srm' . $this->meta_key_redirect_to] = __( 'Redirect To', 'safe-redirect-manager' );
		$columns['srm'. $this->meta_key_redirect_status_code] = __( 'HTTP Status Code', 'safe-redirect-manager' );

		// Change the title column
		$columns['title'] = __( 'Redirect From', 'safe-redirect-manager' );

		// Move date column to the back
		unset( $columns['date'] );
		$columns['date'] = __( 'Date', 'safe-redirect-manager' );

		// get rid of checkboxes
		unset( $columns['cb'] );

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
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' == get_post_type( $post_id ) )
			return;

		// Update post meta for redirect rules
		if ( ! empty( $_POST[$this->redirect_nonce_name] ) && wp_verify_nonce( $_POST[$this->redirect_nonce_name], $this->redirect_nonce_action ) ) {

			if ( ! empty( $_POST['srm' . $this->meta_key_redirect_from] ) ) {
				update_post_meta( $post_id, $this->meta_key_redirect_from, $this->sanitize_redirect_from( $_POST['srm' . $this->meta_key_redirect_from] ) );
			} else {
				delete_post_meta( $post_id, $this->meta_key_redirect_from );
			}

			if ( ! empty( $_POST['srm' . $this->meta_key_redirect_to] ) ) {
				update_post_meta( $post_id, $this->meta_key_redirect_to, $this->sanitize_redirect_to( $_POST['srm' . $this->meta_key_redirect_to] ) );
			} else {
				delete_post_meta( $post_id, $this->meta_key_redirect_to );
			}

			if ( ! empty( $_POST['srm' . $this->meta_key_redirect_status_code] ) ) {
				update_post_meta( $post_id, $this->meta_key_redirect_status_code, absint( $_POST['srm' . $this->meta_key_redirect_status_code] ) );
			} else {
				delete_post_meta( $post_id, $this->meta_key_redirect_status_code );
			}

			/**
			 * This fixes an important bug where the redirect cache was not up-to-date. Previously the cache was only being
			 * updated on transition_post_status which gets called BEFORE save post. But since save_post is where all the important
			 * redirect info is saved, updating the cache before it is not sufficient.
			 */
			$this->update_redirect_cache();
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
		$redirect_labels = array(
			'name' => _x( 'Safe Redirect Manager', 'post type general name' ),
			'singular_name' => _x( 'Redirect', 'post type singular name' ),
			'add_new' => _x( 'Create Redirect Rule', $this->redirect_post_type ),
			'add_new_item' => __( 'Safe Redirect Manager', 'safe-redirect-manager' ),
			'edit_item' => __( 'Edit Redirect Rule', 'safe-redirect-manager' ),
			'new_item' => __( 'New Redirect Rule', 'safe-redirect-manager' ),
			'all_items' => __( 'Safe Redirect Manager', 'safe-redirect-manager' ),
			'view_item' => __( 'View Redirect Rule', 'safe-redirect-manager' ),
			'search_items' => __( 'Search Redirects', 'safe-redirect-manager' ),
			'not_found' =>  __( 'No redirect rules found.', 'safe-redirect-manager' ),
			'not_found_in_trash' => __( 'No redirect rules found in trash.', 'safe-redirect-manager' ),
			'parent_item_colon' => '',
			'menu_name' => __( 'Safe Redirect Manager', 'safe-redirect-manager' )
		);
		$redirect_capability = 'manage_options';
		$redirect_capability = apply_filters( 'srm_restrict_to_capability', $redirect_capability );
		$capabilities = array(
			'edit_post' => $redirect_capability,
			'read_post' => $redirect_capability,
			'delete_post' => $redirect_capability,
			'edit_posts' => $redirect_capability,
			'edit_others_posts' => $redirect_capability,
			'publish_posts' => $redirect_capability,
			'read_private_posts' => $redirect_capability
		);

		$redirect_args = array(
		  'labels' => $redirect_labels,
		  'public' => false,
		  'publicly_queryable' => true,
		  'show_ui' => true,
		  'show_in_menu' => 'tools.php',
		  'query_var' => false,
		  'rewrite' => false,
		  'capability_type' => 'post',
		  'capabilities' => $capabilities,
		  'has_archive' => false,
		  'hierarchical' => false,
		  'register_meta_box_cb' => array( $this, 'action_redirect_rule_metabox' ),
		  'menu_position' => 80,
		  'supports' => array( '' )
		);
		register_post_type( $this->redirect_post_type, $redirect_args );
	}

	/**
	 * Registers meta boxes for redirect rule post type
	 *
	 * @since 1.0
	 * @uses add_meta_box
	 * @return void
	 */
	public function action_redirect_rule_metabox() {
		add_meta_box( 'redirect_settings', __( 'Redirect Settings', 'safe-redirect-manager' ), array( $this, 'redirect_rule_metabox' ), $this->redirect_post_type, 'normal', 'core' );
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
		wp_nonce_field( $this->redirect_nonce_action, $this->redirect_nonce_name );

		$redirect_from = get_post_meta( $post->ID, $this->meta_key_redirect_from, true );
		$redirect_to = get_post_meta( $post->ID, $this->meta_key_redirect_to, true );
		$status_code = get_post_meta( $post->ID, $this->meta_key_redirect_status_code, true );
		if ( empty( $status_code ) )
			$status_code = 302;
	?>
		<p>
			<label for="srm<?php echo $this->meta_key_redirect_from; ?>"><?php _e( 'Redirect From:', 'safe-redirect-manager' ); ?></label><br />
			<input class="widefat" type="text" name="srm<?php echo $this->meta_key_redirect_from; ?>" id="srm<?php echo $this->meta_key_redirect_from; ?>" value="<?php echo esc_attr( $redirect_from ); ?>" /><br />
			<p class="description"><?php _e( "This path should be relative to the root of this WordPress installation (or the sub-site, if you are running a multi-site). Appending a (*) wildcard character will match all requests with the base.", 'safe-redirect-manager' ); ?></p>
		</p>

		<p>
			<label for="srm<?php echo $this->meta_key_redirect_to; ?>"><?php _e( 'Redirect To:', 'safe-redirect-manager' ); ?></label><br />
			<input class="widefat" type="text" name="srm<?php echo $this->meta_key_redirect_to; ?>" id="srm<?php echo $this->meta_key_redirect_to; ?>" value="<?php echo esc_attr( $redirect_to ); ?>" /><br />
			<p class="description"><?php _e( "This can be a URL or a path relative to the root of your website (not your WordPress installation). Ending with a (*) wildcard character will append the request match to the redirect.", 'safe-redirect-manager'); ?></p>
		</p>

		<p>
			<label for="srm<?php echo $this->meta_key_redirect_status_code; ?>"><?php _e( 'HTTP Status Code:', 'safe-redirect-manager' ); ?></label>
			<select name="srm<?php echo $this->meta_key_redirect_status_code; ?>" id="srm<?php echo $this->meta_key_redirect_status_code; ?>">
				<?php foreach ( $this->valid_status_codes as $code ) : ?>
					<option <?php selected( $status_code, $code ); ?>><?php echo $code; ?></option>
				<?php endforeach; ?>
			</select>
			<em><?php _e( "If you don't know what this is, leave it as is.", 'safe-redirect-manager' ); ?></em>
		</p>
	<?php
	}

	/**
	 * Localize plugin
	 *
	 * @since 1.0
	 * @uses load_plugin_textdomain, plugin_basename
	 * @return void
	 */
	public function action_init() {
		load_plugin_textdomain( 'safe-redirect-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Apply whitelisted hosts to allowed_redirect_hosts filter
	 *
	 * @since 1.0
	 * @param array $content
	 * @return array
	 */
	public function filter_allowed_redirect_hosts( $content ) {

		foreach ( $this->whitelist_hosts as $host ) {
			$without_www = preg_replace( '/^www\./i', '', $host );
			$with_www = 'www.' . $without_www;

			if ( ! in_array( $without_www, $content ) ) $content[] = $without_www;
			if ( ! in_array( $with_www, $content ) ) $content[] = $with_www;
		}

		return $content;
	}

	/**
	 * Force update on the redirect cache and return cache
	 *
	 * @since 1.0
	 * @uses set_transient, get_post_meta, the_post, have_posts, get_the_ID
	 * @return array
	 */
	public function update_redirect_cache() {
		global $post;
		$old_post = $post;

		$args = array(
			'posts_per_page' => 1000,
			'post_type' => $this->redirect_post_type,
			'no_found_rows' => true,
			'update_term_cache' => false,
			'post_status' => 'publish'
		);
		$redirect_query = new WP_Query( $args );
		$redirect_cache = array();

		if ( $redirect_query->have_posts() ) {
			while ( $redirect_query->have_posts() ) {
				$redirect_query->the_post();

				$redirect_from = get_post_meta( get_the_ID(), $this->meta_key_redirect_from, true );
				$redirect_to = get_post_meta( get_the_ID(), $this->meta_key_redirect_to, true );
				$status_code = get_post_meta( get_the_ID(), $this->meta_key_redirect_status_code, true );

				if ( ! empty( $redirect_from ) && ! empty( $redirect_to ) ) {
					$redirect_cache[] = array(
						'redirect_from' => $redirect_from,
						'redirect_to' => $redirect_to,
						'status_code' => absint( $status_code )
					);
				}
			}
		}
		$post = $old_post;
		set_transient( $this->cache_key_redirects, $redirect_cache );

		return $redirect_cache;
	}

	/**
	 * Check current url against redirects
	 *
	 * @since 1.0
	 * @uses esc_url_raw, wp_safe_redirect, untrailingslashit, get_transient, add_filter
	 * @return void
	 */
	public function action_parse_request() {

		// get redirects from cache or recreate it
		if ( false === ( $redirects = get_transient( $this->cache_key_redirects ) ) ) {
			$redirects = $this->update_redirect_cache();
		}

		// If we have no redirects, there is no need to continue
		if ( empty( $redirects ) )
			return;

		// get requested path and add a / before it
		$requested_path = sanitize_text_field( $_SERVER['REQUEST_URI'] );

		/**
		 * If WordPress resides in a directory that is not the public root, we have to chop
		 * the pre-WP path off the requested path.
		 */
		$parsed_site_url = parse_url( site_url() );
		if ( isset( $parsed_site_url['path'] ) && '/' != $parsed_site_url['path'] ) {
			$requested_path = preg_replace( '@' . $parsed_site_url['path'] . '@i', '', $requested_path, 1 );
		}

		// Allow redirects to be filtered
		$redirects = apply_filters( 'srm_registered_redirects', $redirects, $requested_path );

		foreach ( (array)$redirects as $redirect ) {

			$redirect_from = untrailingslashit( $redirect['redirect_from'] );
			if ( empty( $redirect_from ) )
				$redirect_from = '/'; // this only happens in the case where there is a redirect on the root

			$redirect_to = $redirect['redirect_to'];
			$status_code = $redirect['status_code'];

			if ( apply_filters( 'srm_case_insensitive_redirects', true ) ) {
				$requested_path = strtolower( $requested_path );
				$redirect_from = strtolower( $redirect_from );
			}

			// check if requested path is the same as the redirect from path
			$matched_path = ( untrailingslashit( $requested_path ) == $redirect_from );

			// check if the redirect_from ends in a wildcard
			if ( !$matched_path && (strrpos( $redirect_from, '*' ) == strlen( $redirect_from ) - 1) ) {
				$wildcard_base = substr( $redirect_from, 0, strlen( $redirect_from ) - 1 );

				// mark as match if requested path matches the base of the redirect from
				$matched_path = (substr( $requested_path, 0, strlen( $wildcard_base ) ) == $wildcard_base);
				if ( (strrpos( $redirect_to, '*' ) == strlen( $redirect_to ) - 1 ) ) {
					$redirect_to = rtrim( $redirect_to, '*' ) . ltrim( substr( $requested_path, strlen( $wildcard_base ) ), '/' );
				}
			}

			if ( $matched_path ) {
				// whitelist redirect to host if necessary
				$parsed_redirect = parse_url( $redirect_to );
				if ( is_array( $parsed_redirect ) && ! empty( $parsed_redirect['host'] ) ) {
					$this->whitelist_hosts[] = $parsed_redirect['host'];
					add_filter( 'allowed_redirect_hosts' , array( $this, 'filter_allowed_redirect_hosts' ) );
				}

				header("X-Safe-Redirect-Manager: true");

				// if we have a valid status code, then redirect with it
				if ( in_array( $status_code, $this->valid_status_codes ) )
					wp_safe_redirect( esc_url_raw( $redirect_to ), $status_code );
				else
					wp_safe_redirect( esc_url_raw( $redirect_to ) );
				exit;
			}
		}
	}

	/**
	 * Sanitize redirect to path
	 *
	 * The only difference between this function and just calling esc_url_raw is
	 * esc_url_raw( 'test' ) == 'http://test', whereas sanitize_redirect_path( 'test' ) == '/test'
	 *
	 * @since 1.0
	 * @param string $path
	 * @uses esc_url_raw
	 * @return string
	 */
	public function sanitize_redirect_to( $path ) {
		$path = trim( $path );

		if (  preg_match( '/^www\./i', $path ) )
			$path = 'http://' . $path;

		if ( ! preg_match( '/^https?:\/\//i', $path ) )
			if ( strpos( $path, '/' ) !== 0 )
				$path = '/' . $path;

		return esc_url_raw( $path );
	}

	/**
	 * Sanitize redirect from path
	 *
	 * @since 1.0
	 * @param string $path
	 * @uses esc_url_raw
	 * @return string
	 */
	public function sanitize_redirect_from( $path ) {

		$path = trim( $path );

		if ( empty( $path ) )
				return '';

		// dont accept paths starting with a .
		if ( strpos( $path, '.' ) === 0 )
			return '';

		// turn path in to absolute
		if ( preg_match( '/https?:\/\//i', $path ) )
			$path = preg_replace( '/^(http:\/\/|https:\/\/)(www\.)?[^\/?]+\/?(.*)/i', '/$3', $path );
		elseif ( strpos( $path, '/' ) !== 0 )
			$path = '/' . $path;

		return esc_url_raw( $path );
	}
}

global $safe_redirect_manager;
$safe_redirect_manager = new SRM_Safe_Redirect_Manager();
