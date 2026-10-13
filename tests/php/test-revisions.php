<?php
/**
 * Test redirect rule revisions
 *
 * @package safe-redirect-manager
 */

class SRMTestRevisions extends WP_UnitTestCase {

	/**
	 * Re-register the post type and its revisioned meta before each test.
	 *
	 * The test suite clears registered meta keys between tests, while the plugin
	 * only registers them once on init. Re-running the registration keeps the
	 * meta revisioned for the duration of a test.
	 *
	 * @since 2.4.0
	 */
	public function set_up() {
		parent::set_up();

		SRM_Post_Type::factory()->action_register_post_types();
	}

	/**
	 * Create a rule and save it once so a baseline revision exists.
	 *
	 * Revisions are only stored on an update, so the first save after creation is
	 * what starts the history.
	 *
	 * @since 2.4.0
	 * @param string $from From path.
	 * @param string $to   To path.
	 * @return int Post ID.
	 */
	protected function createRuleWithRevision( $from, $to ) {
		$post_id = srm_create_redirect( $from, $to );

		wp_update_post(
			array(
				'ID' => $post_id,
			)
		);

		return $post_id;
	}

	/**
	 * Get a rule's revisions, newest first.
	 *
	 * Saves within a test can share a timestamp, so the order is forced by ID
	 * rather than relying on the date.
	 *
	 * @since 2.4.0
	 * @param int $post_id Post ID.
	 * @return WP_Post[] Revisions, newest first.
	 */
	protected function getRuleRevisions( $post_id ) {
		$revisions = array_values( wp_get_post_revisions( $post_id ) );

		usort(
			$revisions,
			function ( $a, $b ) {
				return $b->ID <=> $a->ID;
			}
		);

		return $revisions;
	}

	/**
	 * Test that a meta only change creates a revision
	 *
	 * @since 2.4.0
	 */
	public function testMetaOnlyChangeCreatesRevision() {
		$post_id = $this->createRuleWithRevision( '/old-path', '/new-path' );

		$this->assertCount( 1, wp_get_post_revisions( $post_id ) );

		// Change only the redirect meta, nothing else.
		update_post_meta( $post_id, '_redirect_rule_to', '/newer-path' );
		wp_update_post(
			array(
				'ID' => $post_id,
			)
		);

		$revisions = $this->getRuleRevisions( $post_id );
		$this->assertCount( 2, $revisions );

		// The newest revision holds the changed meta value, the older one the previous value.
		$this->assertSame( '/newer-path', get_post_meta( $revisions[0]->ID, '_redirect_rule_to', true ) );
		$this->assertSame( '/new-path', get_post_meta( $revisions[1]->ID, '_redirect_rule_to', true ) );
	}

	/**
	 * Test that a revision stores every redirect meta key
	 *
	 * @since 2.4.0
	 */
	public function testRevisionStoresAllRedirectMeta() {
		$post_id = $this->createRuleWithRevision( '/old-path', '/new-path' );

		$revisions = $this->getRuleRevisions( $post_id );

		$this->assertSame( '/old-path', get_post_meta( $revisions[0]->ID, '_redirect_rule_from', true ) );
		$this->assertSame( '/new-path', get_post_meta( $revisions[0]->ID, '_redirect_rule_to', true ) );
		$this->assertSame( '302', get_post_meta( $revisions[0]->ID, '_redirect_rule_status_code', true ) );
	}

	/**
	 * Test that restoring a revision restores the redirect meta
	 *
	 * @since 2.4.0
	 */
	public function testRestoreRevisionRestoresMeta() {
		$post_id = $this->createRuleWithRevision( '/old-path', '/new-path' );

		update_post_meta( $post_id, '_redirect_rule_to', '/newer-path' );
		wp_update_post(
			array(
				'ID' => $post_id,
			)
		);

		$this->assertSame( '/newer-path', get_post_meta( $post_id, '_redirect_rule_to', true ) );

		$revisions = $this->getRuleRevisions( $post_id );
		$this->assertCount( 2, $revisions );

		wp_restore_post_revision( $revisions[1]->ID );

		$this->assertSame( '/new-path', get_post_meta( $post_id, '_redirect_rule_to', true ) );
	}

	/**
	 * Test that revisions do not leak into the redirect list
	 *
	 * @since 2.4.0
	 */
	public function testRevisionsDoNotLeakIntoRedirects() {
		$post_id = $this->createRuleWithRevision( '/old-path', '/new-path' );

		update_post_meta( $post_id, '_redirect_rule_to', '/newer-path' );
		wp_update_post(
			array(
				'ID' => $post_id,
			)
		);

		$this->assertCount( 2, wp_get_post_revisions( $post_id ) );

		$redirects = srm_get_redirects( array(), true );
		$this->assertCount( 1, $redirects );
		$this->assertSame( $post_id, $redirects[0]['ID'] );
		$this->assertSame( '/old-path', $redirects[0]['redirect_from'] );
		$this->assertSame( '/newer-path', $redirects[0]['redirect_to'] );
	}

	/**
	 * Test that creating a redirect for an existing from path updates the original post
	 *
	 * @since 2.4.0
	 */
	public function testCreateRedirectWithExistingFromUpdatesParent() {
		$post_id = $this->createRuleWithRevision( '/old-path', '/new-path' );

		update_post_meta( $post_id, '_redirect_rule_to', '/newer-path' );
		wp_update_post(
			array(
				'ID' => $post_id,
			)
		);

		$this->assertCount( 2, wp_get_post_revisions( $post_id ) );

		// The duplicate check has to match the rule, not a revision holding the same from path.
		$updated_id = srm_create_redirect( '/old-path', '/final-path' );

		$this->assertNotInstanceOf( 'WP_Error', $updated_id );
		$this->assertSame( $post_id, $updated_id );
		$this->assertSame( '/final-path', get_post_meta( $post_id, '_redirect_rule_to', true ) );
		$this->assertSame( 'publish', get_post_status( $post_id ) );
	}

	/**
	 * Test that the revision screen fields include the redirect meta keys
	 *
	 * @since 2.4.0
	 */
	public function testRevisionFieldsIncludeRedirectMeta() {
		$post_id = $this->createRuleWithRevision( '/old-path', '/new-path' );

		$fields = _wp_post_revision_fields( get_post( $post_id, ARRAY_A ) );

		$this->assertArrayHasKey( '_redirect_rule_from', $fields );
		$this->assertArrayHasKey( '_redirect_rule_to', $fields );
		$this->assertArrayHasKey( '_redirect_rule_status_code', $fields );
		$this->assertSame( 'Redirect From', $fields['_redirect_rule_from'] );
		$this->assertSame( 'Redirect To', $fields['_redirect_rule_to'] );
		$this->assertSame( 'HTTP Status Code', $fields['_redirect_rule_status_code'] );
	}

	/**
	 * Test that revision fields are left alone for other post types
	 *
	 * @since 2.4.0
	 */
	public function testRevisionFieldsSkipOtherPostTypes() {
		$post_type = SRM_Post_Type::factory();

		$this->assertSame( array(), $post_type->filter_revision_fields( array(), array( 'post_type' => 'post' ) ) );
		$this->assertSame( array(), $post_type->filter_revision_fields( array(), array() ) );
	}

	/**
	 * Test that checkbox meta is shown as Yes or No on the revision screen
	 *
	 * @since 2.4.0
	 */
	public function testRevisionFieldFormatsBooleans() {
		$post_type = SRM_Post_Type::factory();

		$post_id = $this->createRuleWithRevision( '/old-path', '/new-path' );

		$revisions = $this->getRuleRevisions( $post_id );
		$revision  = get_post( $revisions[0]->ID );

		// Core writes revision meta with add_metadata(), since update_post_meta() targets the parent post.
		add_metadata( 'post', $revision->ID, '_force_https', true );
		$this->assertSame( 'Yes', $post_type->filter_revision_field( '', '_force_https', $revision ) );

		update_metadata( 'post', $revision->ID, '_force_https', false );
		$this->assertSame( 'No', $post_type->filter_revision_field( '', '_force_https', $revision ) );

		$this->assertSame( '/new-path', $post_type->filter_revision_field( '', '_redirect_rule_to', $revision ) );
	}

	/**
	 * Test that restoring a revision clears the redirect cache
	 *
	 * @since 2.4.0
	 */
	public function testRestoreRevisionFlushesCache() {
		$post_id = $this->createRuleWithRevision( '/old-path', '/new-path' );

		update_post_meta( $post_id, '_redirect_rule_to', '/newer-path' );
		wp_update_post(
			array(
				'ID' => $post_id,
			)
		);

		// Warm the cache with the current values.
		$redirects = srm_get_redirects();
		$this->assertSame( '/newer-path', $redirects[0]['redirect_to'] );

		$revisions = $this->getRuleRevisions( $post_id );
		wp_restore_post_revision( $revisions[1]->ID );

		// The cached list has to reflect the restored value.
		$redirects = srm_get_redirects();
		$this->assertSame( '/new-path', $redirects[0]['redirect_to'] );
	}
}
