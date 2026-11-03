<?php
/**
 * Test Abilities API integration
 *
 * @package safe-redirect-manager
 */

class SRMTestAbilities extends WP_UnitTestCase {

	/**
	 * Logs in as an administrator.
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Test abilities are registered
	 */
	public function testAbilitiesRegistered() {
		$this->assertInstanceOf( 'WP_Ability', wp_get_ability( 'safe-redirect-manager/list-redirects' ) );
		$this->assertInstanceOf( 'WP_Ability', wp_get_ability( 'safe-redirect-manager/create-redirect' ) );
		$this->assertInstanceOf( 'WP_Ability', wp_get_ability( 'safe-redirect-manager/delete-redirect' ) );
	}

	/**
	 * Test users without the redirect capability are denied
	 */
	public function testPermissionDenied() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$result = wp_get_ability( 'safe-redirect-manager/list-redirects' )->execute();

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * Test creating, listing and deleting a redirect
	 */
	public function testCreateListDelete() {
		$created = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/ability-from/',
				'redirect_to'   => '/ability-to/',
				'status_code'   => 301,
				'post_status'   => 'draft',
				'notes'         => 'Created by ability',
			)
		);

		$this->assertIsInt( $created['id'] );

		$redirects = wp_get_ability( 'safe-redirect-manager/list-redirects' )->execute();
		$redirect  = wp_list_filter( $redirects, array( 'ID' => $created['id'] ) );
		$redirect  = reset( $redirect );

		$this->assertSame( '/ability-from/', $redirect['redirect_from'] );
		$this->assertSame( '/ability-to/', $redirect['redirect_to'] );
		$this->assertSame( 301, $redirect['status_code'] );
		$this->assertSame( 'draft', $redirect['post_status'] );
		$this->assertSame( 'Created by ability', $redirect['notes'] );
		$this->assertSame( get_current_user_id(), (int) get_post_field( 'post_author', $created['id'] ) );

		$deleted = wp_get_ability( 'safe-redirect-manager/delete-redirect' )->execute( array( 'id' => $created['id'] ) );

		$this->assertSame( array( 'id' => $created['id'] ), $deleted );
		$this->assertSame( 'trash', get_post_status( $created['id'] ) );
	}

	/**
	 * Test invalid status codes are rejected
	 */
	public function testCreateInvalidStatusCode() {
		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/from/',
				'redirect_to'   => '/to/',
				'status_code'   => 999,
			)
		);

		$this->assertWPError( $result );
	}

	/**
	 * Test a regex redirect in the documented format redirects
	 */
	public function testCreateRegexRedirect() {
		$request_uri            = $_SERVER['REQUEST_URI'] ?? null;
		$_SERVER['REQUEST_URI'] = '/blog/hello';
		$actual_redirect        = '';

		$created = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/blog/(.*)$',
				'redirect_to'   => '/news/$1',
				'enable_regex'  => true,
			)
		);

		$this->assertIsInt( $created['id'] ?? null );

		add_action(
			'srm_do_redirect',
			function ( $requested_path, $redirected_to ) use ( &$actual_redirect ) {
				$actual_redirect = $redirected_to;
			},
			10,
			2
		);

		SRM_Redirect::factory()->maybe_redirect();

		$_SERVER['REQUEST_URI'] = $request_uri;

		$this->assertSame( '/news/hello', $actual_redirect );
	}

	/**
	 * Test enable_regex sent as the string "false" creates a plain rule
	 */
	public function testCreateRegexFalseString() {
		$created = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/a.b/',
				'redirect_to'   => '/to/',
				'enable_regex'  => 'false',
			)
		);

		$this->assertFalse( (bool) get_post_meta( $created['id'], '_redirect_rule_from_regex', true ) );
	}

	/**
	 * Test status codes are read when creating, not when registering
	 */
	public function testCreateLateStatusCode() {
		add_filter(
			'srm_additional_status_codes',
			function ( $codes ) {
				$codes[308] = 'Permanent Redirect';
				return $codes;
			}
		);

		$created = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/late/',
				'redirect_to'   => '/to/',
				'status_code'   => 308,
			)
		);

		$this->assertSame( 308, (int) get_post_meta( $created['id'], '_redirect_rule_status_code', true ) );
	}

	/**
	 * Test creating over a trashed rule restores it from the trash
	 */
	public function testCreateRestoresTrashed() {
		$redirect_id = srm_create_redirect( '/restore-me/', '/to/' );
		$slug        = get_post_field( 'post_name', $redirect_id );

		wp_get_ability( 'safe-redirect-manager/delete-redirect' )->execute( array( 'id' => $redirect_id ) );

		$created = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/restore-me/',
				'redirect_to'   => '/new-to/',
			)
		);

		$this->assertSame( array( 'id' => $redirect_id ), $created );
		$this->assertSame( 'publish', get_post_status( $redirect_id ) );
		$this->assertSame( $slug, get_post_field( 'post_name', $redirect_id ) );
		$this->assertSame( '', get_post_meta( $redirect_id, '_wp_trash_meta_status', true ) );
	}

	/**
	 * Test a rejected create leaves a trashed rule in the trash
	 */
	public function testCreateInvalidStatusKeepsTrashed() {
		$redirect_id = srm_create_redirect( '/stay-trashed/', '/to/' );

		wp_trash_post( $redirect_id );

		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/stay-trashed/',
				'redirect_to'   => '/new-to/',
				'status_code'   => 999,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'trash', get_post_status( $redirect_id ) );
	}

	/**
	 * Test an existing rule can still be overwritten at the maximum
	 */
	public function testCreateOverwriteAtMaxRedirects() {
		$redirect_id = srm_create_redirect( '/existing/', '/to/' );

		add_filter(
			'srm_max_redirects',
			function () {
				return 1;
			}
		);

		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/existing/',
				'redirect_to'   => '/new-to/',
			)
		);

		$this->assertSame( array( 'id' => $redirect_id ), $result );
	}

	/**
	 * Test invalid regex patterns are rejected
	 */
	public function testCreateInvalidRegex() {
		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/blog/(.*',
				'redirect_to'   => '/news/',
				'enable_regex'  => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-regex', $result->get_error_code() );
	}

	/**
	 * Test creating with an existing from path overwrites that rule
	 */
	public function testCreateOverwritesExisting() {
		$ability = wp_get_ability( 'safe-redirect-manager/create-redirect' );
		$first   = $ability->execute(
			array(
				'redirect_from' => '/same/',
				'redirect_to'   => '/first/',
				'status_code'   => 301,
			)
		);
		$second  = $ability->execute(
			array(
				'redirect_from' => '/same/',
				'redirect_to'   => '/second/',
			)
		);

		$this->assertSame( $first['id'], $second['id'] );
		$this->assertSame( '/second/', get_post_meta( $second['id'], '_redirect_rule_to', true ) );
		$this->assertSame( 302, (int) get_post_meta( $second['id'], '_redirect_rule_status_code', true ) );
	}

	/**
	 * Test creating is refused once the maximum number of redirects is reached
	 */
	public function testCreateMaxRedirectsReached() {
		srm_create_redirect( '/existing/', '/to/' );

		add_filter(
			'srm_max_redirects',
			function () {
				return 1;
			}
		);

		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/new/',
				'redirect_to'   => '/to/',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'max-redirects-reached', $result->get_error_code() );
	}

	/**
	 * Test deleting an already trashed redirect succeeds
	 */
	public function testDeleteIsIdempotent() {
		$redirect_id = srm_create_redirect( '/trash-me/', '/to/' );
		$ability     = wp_get_ability( 'safe-redirect-manager/delete-redirect' );

		$ability->execute( array( 'id' => $redirect_id ) );

		$this->assertSame( array( 'id' => $redirect_id ), $ability->execute( array( 'id' => $redirect_id ) ) );
		$this->assertSame( 'trash', get_post_status( $redirect_id ) );
	}

	/**
	 * Test a from path that sanitizes to nothing is rejected
	 */
	public function testCreateEmptyFrom() {
		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '.htaccess',
				'redirect_to'   => '/to/',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-argument', $result->get_error_code() );
	}

	/**
	 * Test the regex check compiles the pattern the way redirects do, without trailing slashes
	 */
	public function testCreateRegexTrailingBackslash() {
		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/foo\\',
				'redirect_to'   => '/to/',
				'enable_regex'  => true,
			)
		);

		$this->assertIsInt( $result['id'] ?? null );
	}

	/**
	 * Test drafts can be created at the maximum but not published over it
	 */
	public function testCreateDraftAtMaxRedirects() {
		srm_create_redirect( '/existing/', '/to/' );

		add_filter(
			'srm_max_redirects',
			function () {
				return 1;
			}
		);

		$ability = wp_get_ability( 'safe-redirect-manager/create-redirect' );
		$draft   = $ability->execute(
			array(
				'redirect_from' => '/draft/',
				'redirect_to'   => '/to/',
				'post_status'   => 'draft',
			)
		);

		$this->assertIsInt( $draft['id'] );

		$result = $ability->execute(
			array(
				'redirect_from' => '/draft/',
				'redirect_to'   => '/to/',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'draft', get_post_status( $draft['id'] ) );
	}

	/**
	 * Test a delete that skips the trash still stops the redirect
	 */
	public function testDeleteWithoutTrashFlushesCache() {
		$redirect_id = srm_create_redirect( '/no-trash/', '/to/' );

		srm_get_redirects();

		// Deletes outright without a status change, as wp_trash_post() does when EMPTY_TRASH_DAYS is 0.
		add_filter(
			'pre_trash_post',
			function ( $check, $post ) {
				return (bool) wp_delete_post( $post->ID, true );
			},
			10,
			2
		);

		wp_get_ability( 'safe-redirect-manager/delete-redirect' )->execute( array( 'id' => $redirect_id ) );

		$this->assertNotContains( $redirect_id, wp_list_pluck( srm_get_redirects(), 'ID' ) );
	}

	/**
	 * Test an empty destination is rejected instead of redirecting to the site root
	 */
	public function testCreateEmptyTo() {
		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/empty-to/',
				'redirect_to'   => '   ',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-argument', $result->get_error_code() );
	}

	/**
	 * Test negative status codes are rejected
	 */
	public function testCreateNegativeStatusCode() {
		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/negative/',
				'redirect_to'   => '/to/',
				'status_code'   => -301,
			)
		);

		$this->assertWPError( $result );
	}

	/**
	 * Test a failed overwrite puts a restored rule back in the trash
	 */
	public function testCreateFailureRetrashes() {
		$redirect_id = srm_create_redirect( '/retrash/', '/to/' );

		wp_trash_post( $redirect_id );

		// Make only the overwrite's wp_update_post() fail, after the restore and before the re-trash.
		$fail_once = function () use ( &$fail_once ) {
			remove_filter( 'wp_insert_post_empty_content', $fail_once );
			return true;
		};

		add_action(
			'untrashed_post',
			function () use ( &$fail_once ) {
				add_filter( 'wp_insert_post_empty_content', $fail_once );
			}
		);

		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/retrash/',
				'redirect_to'   => '/new-to/',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'trash', get_post_status( $redirect_id ) );
	}

	/**
	 * Test invalid input at the maximum reports the input error, not the limit
	 */
	public function testCreateInvalidAtMaxRedirects() {
		srm_create_redirect( '/existing/', '/to/' );

		add_filter(
			'srm_max_redirects',
			function () {
				return 1;
			}
		);

		$result = wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/new/',
				'redirect_to'   => '/to/',
				'status_code'   => 999,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-argument', $result->get_error_code() );
	}

	/**
	 * Test an overwrite keeps the rule's author
	 */
	public function testCreateOverwriteKeepsAuthor() {
		$author_id   = self::factory()->user->create( array( 'role' => 'editor' ) );
		$redirect_id = srm_create_redirect( '/authored/', '/to/', 302, false, 'publish', 0, '', $author_id );

		wp_get_ability( 'safe-redirect-manager/create-redirect' )->execute(
			array(
				'redirect_from' => '/authored/',
				'redirect_to'   => '/new-to/',
			)
		);

		$this->assertSame( $author_id, (int) get_post_field( 'post_author', $redirect_id ) );
	}

	/**
	 * Test list-redirects returns force_https as a boolean
	 */
	public function testListForceHttpsBoolean() {
		$forced = srm_create_redirect( '/forced/', '/to/' );
		$plain  = srm_create_redirect( '/plain/', '/to/' );

		update_post_meta( $forced, '_force_https', true );

		$redirects = array_column( wp_get_ability( 'safe-redirect-manager/list-redirects' )->execute(), null, 'ID' );

		$this->assertTrue( $redirects[ $forced ]['force_https'] );
		$this->assertFalse( $redirects[ $plain ]['force_https'] );
	}

	/**
	 * Test rejected input returns a client error status over REST, not 500
	 */
	public function testRestClientErrorStatus() {
		$request = new WP_REST_Request( 'DELETE', '/wp-abilities/v1/abilities/safe-redirect-manager/create-redirect/run' );
		$request->set_query_params(
			array(
				'input' => array(
					'redirect_from' => '/from/',
					'redirect_to'   => '/to/',
					'status_code'   => 999,
				),
			)
		);

		$this->assertSame( 400, rest_do_request( $request )->get_status() );

		$request = new WP_REST_Request( 'DELETE', '/wp-abilities/v1/abilities/safe-redirect-manager/delete-redirect/run' );
		$request->set_query_params( array( 'input' => array( 'id' => self::factory()->post->create() ) ) );

		$this->assertSame( 404, rest_do_request( $request )->get_status() );
	}

	/**
	 * Test deleting a post that isn't a redirect is rejected
	 */
	public function testDeleteNonRedirect() {
		$post_id = self::factory()->post->create();

		$result = wp_get_ability( 'safe-redirect-manager/delete-redirect' )->execute( array( 'id' => $post_id ) );

		$this->assertWPError( $result );
		$this->assertSame( 'publish', get_post_status( $post_id ) );
	}
}
