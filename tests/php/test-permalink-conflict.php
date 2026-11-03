<?php
/**
 * Tests for the permalink conflict notice shown when a post collides with a
 * redirect rule.
 *
 * @package safe-redirect-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Run in WP context only.
}

class SRMTestPermalinkConflict extends WP_UnitTestCase {

	/**
	 * Set up permalinks so a post slug maps to a clean path.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
	}

	/**
	 * Restore the permalink structure and clean up the current user.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->set_permalink_structure( '' );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * The user meta key the plugin stores conflicts under.
	 *
	 * @return string
	 */
	private function conflict_meta_key() {
		return '_srm_redirect_conflicts_' . get_current_blog_id();
	}

	/**
	 * Transition a post to a given status with a current user set.
	 *
	 * @param WP_Post $post       Post to transition.
	 * @param string  $new_status Status to transition to.
	 * @param string  $old_status Status transitioning from.
	 * @param int     $user       User to act as.
	 * @return void
	 */
	private function transition_post( $post, $new_status, $old_status = 'draft', $user = 1 ) {
		wp_set_current_user( $user );
		SRM_Post_Type::factory()->action_redirect_conflict_check( $new_status, $old_status, $post );
	}

	/**
	 * The conflict is stored against the user that published the post.
	 *
	 * @return void
	 */
	public function testConflictIsStoredOnPublish() {
		$user_id     = $this->factory()->user->create();
		$redirect_id = srm_create_redirect( '/conflict/', '/target/' );

		$this->assertNotWPError( $redirect_id );

		$post_id = $this->factory()->post->create(
			array(
				'post_name'   => 'conflict',
				'post_status' => 'publish',
			)
		);

		$this->transition_post( get_post( $post_id ), 'publish', 'draft', $user_id );

		$conflicts = get_user_meta( $user_id, $this->conflict_meta_key(), true );
		$this->assertIsArray( $conflicts );
		$this->assertContains( $post_id, $conflicts );
	}

	/**
	 * Public content that is not redirected should not be flagged.
	 *
	 * @return void
	 */
	public function testNoConflictWhenPermalinkIsNotRedirected() {
		$user_id = $this->factory()->user->create();
		srm_create_redirect( '/conflict/', '/target/' );

		$post_id = $this->factory()->post->create(
			array(
				'post_name'   => 'safe-page',
				'post_status' => 'publish',
			)
		);

		$this->transition_post( get_post( $post_id ), 'publish', 'draft', $user_id );

		$this->assertEmpty( get_user_meta( $user_id, $this->conflict_meta_key(), true ) );
	}

	/**
	 * The redirect post type itself should never be flagged.
	 *
	 * @return void
	 */
	public function testNoConflictForRedirectRulePostType() {
		$user_id     = $this->factory()->user->create();
		$redirect_id = srm_create_redirect( '/conflict/', '/target/' );

		$this->assertNotWPError( $redirect_id );

		$this->transition_post( get_post( $redirect_id ), 'publish', 'draft', $user_id );

		$this->assertEmpty( get_user_meta( $user_id, $this->conflict_meta_key(), true ) );
	}

	/**
	 * Only the publish transition should trigger the check.
	 *
	 * @return void
	 */
	public function testNoConflictWhenNotPublishing() {
		$user_id = $this->factory()->user->create();
		srm_create_redirect( '/conflict/', '/target/' );

		$post_id = $this->factory()->post->create(
			array(
				'post_name'   => 'conflict',
				'post_status' => 'draft',
			)
		);

		$this->transition_post( get_post( $post_id ), 'draft', 'new', $user_id );

		$this->assertEmpty( get_user_meta( $user_id, $this->conflict_meta_key(), true ) );
	}

	/**
	 * Re-saving an already published post should not flag it again.
	 *
	 * @return void
	 */
	public function testNoConflictOnPublishToPublishUpdate() {
		$user_id = $this->factory()->user->create();
		srm_create_redirect( '/conflict/', '/target/' );

		$post_id = $this->factory()->post->create(
			array(
				'post_name'   => 'conflict',
				'post_status' => 'publish',
			)
		);

		$this->transition_post( get_post( $post_id ), 'publish', 'publish', $user_id );

		$this->assertEmpty( get_user_meta( $user_id, $this->conflict_meta_key(), true ) );
	}

	/**
	 * A scheduled publish with no current user falls back to the post author.
	 *
	 * @return void
	 */
	public function testConflictIsStoredOnAuthorWhenNoCurrentUser() {
		$author_id = $this->factory()->user->create();
		srm_create_redirect( '/conflict/', '/target/' );

		$post_id = $this->factory()->post->create(
			array(
				'post_name'   => 'conflict',
				'post_status' => 'publish',
				'post_author' => $author_id,
			)
		);

		$this->transition_post( get_post( $post_id ), 'publish', 'future', 0 );

		$conflicts = get_user_meta( $author_id, $this->conflict_meta_key(), true );
		$this->assertIsArray( $conflicts );
		$this->assertContains( $post_id, $conflicts );
	}

	/**
	 * When redirects only run on 404, published content is never redirected.
	 *
	 * @return void
	 */
	public function testNoConflictWhenRedirectsOnlyRunOn404() {
		add_filter( 'srm_redirect_only_on_404', '__return_true' );

		$user_id = $this->factory()->user->create();
		srm_create_redirect( '/conflict/', '/target/' );

		$post_id = $this->factory()->post->create(
			array(
				'post_name'   => 'conflict',
				'post_status' => 'publish',
			)
		);

		$this->transition_post( get_post( $post_id ), 'publish', 'draft', $user_id );

		remove_filter( 'srm_redirect_only_on_404', '__return_true' );

		$this->assertEmpty( get_user_meta( $user_id, $this->conflict_meta_key(), true ) );
	}

	/**
	 * The filter should allow disabling the check entirely.
	 *
	 * @return void
	 */
	public function testFilterCanDisableNotice() {
		add_filter( 'srm_redirect_conflict_check', '__return_false' );

		$user_id = $this->factory()->user->create();
		srm_create_redirect( '/conflict/', '/target/' );

		$post_id = $this->factory()->post->create(
			array(
				'post_name'   => 'conflict',
				'post_status' => 'publish',
			)
		);

		$this->transition_post( get_post( $post_id ), 'publish', 'draft', $user_id );

		remove_filter( 'srm_redirect_conflict_check', '__return_false' );

		$this->assertEmpty( get_user_meta( $user_id, $this->conflict_meta_key(), true ) );
	}

	/**
	 * A stored conflict is rendered as an admin warning and then cleared.
	 *
	 * @return void
	 */
	public function testNoticeIsRenderedAndCleared() {
		$user_id = $this->factory()->user->create();
		$admin   = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$redirect_id = srm_create_redirect( '/conflict/', '/target/' );
		$post_id     = $this->factory()->post->create(
			array(
				'post_name'   => 'conflict',
				'post_status' => 'publish',
			)
		);

		update_user_meta( $admin, $this->conflict_meta_key(), array( $post_id ) );

		ob_start();
		SRM_Post_Type::factory()->action_redirect_conflict_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'notice-warning', $output );
		$this->assertStringContainsString( 'unreachable', $output );
		$this->assertEmpty( get_user_meta( $admin, $this->conflict_meta_key(), true ) );

		// The unrelated user is untouched.
		$this->assertEmpty( get_user_meta( $user_id, $this->conflict_meta_key(), true ) );
	}

	/**
	 * A conflict that has since been resolved is not reported.
	 *
	 * @return void
	 */
	public function testResolvedConflictIsNotReported() {
		$admin = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$post_id = $this->factory()->post->create(
			array(
				'post_name'   => 'conflict',
				'post_status' => 'publish',
			)
		);

		// No redirect rule exists, so the stored conflict is stale.
		update_user_meta( $admin, $this->conflict_meta_key(), array( $post_id ) );

		ob_start();
		SRM_Post_Type::factory()->action_redirect_conflict_notice();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'notice-warning', $output );
		$this->assertEmpty( get_user_meta( $admin, $this->conflict_meta_key(), true ) );
	}
}