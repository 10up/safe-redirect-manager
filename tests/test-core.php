<?php

class SRMTestCore extends WP_UnitTestCase {

	/**
	 * Test root redirect
	 *
	 * @since 1.8
	 */
	public function testRootRedirect() {
		global $safe_redirect_manager;

		$_SERVER['REQUEST_URI'] = '/';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );
	}

	/**
	 * Test lots of permutations of URL trailing slashes with and without regex
	 *
	 * @since 1.8
	 */
	public function testTrailingSlashes() {
		/**
		 * First without regex
		 */

		global $safe_redirect_manager;

		$_SERVER['REQUEST_URI'] = '/one';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/one/', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );

		$_SERVER['REQUEST_URI'] = '/one/';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/one', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );

		$_SERVER['REQUEST_URI'] = '/one/two';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/one/two/', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );

		$_SERVER['REQUEST_URI'] = '/one/two/';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/one/two', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );

		/**
		 * Now with regex
		 */

		$_SERVER['REQUEST_URI'] = '/one/two';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/.*/', $redirect_to, 301, true );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );

		$_SERVER['REQUEST_URI'] = '/one/two/';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/.*', $redirect_to, 301, true );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );
	}

	/**
	 * Test some simple redirections
	 *
	 * @since 1.8
	 */
	public function testSimplePath() {
		global $safe_redirect_manager;

		$_SERVER['REQUEST_URI'] = '/test';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/test', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );

		/**
		 * Test longer path with no trailing slash
		 */

		$_SERVER['REQUEST_URI'] = '/test/this/path';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/test/this/path/', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );

		/**
		 * Test a redirect miss
		 */

		$_SERVER['REQUEST_URI'] = '/test/wrong/path';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/test/right/path/', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( ! $redirected );
	}

	/**
	 * Test regex redirections
	 *
	 * @since 1.8
	 */
	public function testSimplePathRegex() {
		global $safe_redirect_manager;

		$_SERVER['REQUEST_URI'] = '/tet/555/path/sdfsfsdf';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/tes?t/[0-9]+/path/[^/]+/?', $redirect_to, 301, true );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );

		/**
		 * Test regex replacement
		 */

		$_SERVER['REQUEST_URI'] = '/well/everything-else/strip';
		$redirected = false;
		$redirect_to = '/$1';
		$safe_redirect_manager->create_redirect( '/([a-z]+)/.*', $redirect_to, 301, true );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === '/well' ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );

		/**
		 * Test regex miss
		 */

		$_SERVER['REQUEST_URI'] = '/another/test';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/[0-9]+', $redirect_to, 301, true );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( ! $redirected );
	}
}