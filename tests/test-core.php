<?php

class SRMTestCore extends WP_UnitTestCase {

	/**
	 * Test root redirect
	 *
	 * @since 1.7.3
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
	 * Test redirect with cases
	 *
	 * @since 1.7.4
	 */
	public function testCaseInsensitiveRedirect() {
		global $safe_redirect_manager;

		$_SERVER['REQUEST_URI'] = '/ONE';
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

		$_SERVER['REQUEST_URI'] = '/one';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/ONE/', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );
	}

	/**
	 * Try a redirect after filtering case sensitivity
	 *
	 * @since 1.7.4
	 */
	public function testCaseSensitiveRedirect() {
		global $safe_redirect_manager;

		$_SERVER['REQUEST_URI'] = '/ONE';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/one/', $redirect_to );

		add_filter( 'srm_case_insensitive_redirects', function( $value ) {
			return false;
		}, 10, 1 );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertFalse( $redirected );
	}

	/**
	 * Test case sensitive redirect to
	 *
	 * @since 1.7.4
	 */
	public function testCaseSensitiveRedirectTo() {
		global $safe_redirect_manager;

		$_SERVER['REQUEST_URI'] = '/ONE';
		$redirected = false;
		$redirect_to = '/goHERE';
		$safe_redirect_manager->create_redirect( '/one/', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );
	}

	/**
	 * Test basic wildcards
	 *
	 * @since 1.7.4
	 */
	public function testBasicWildcard() {
		global $safe_redirect_manager;

		$_SERVER['REQUEST_URI'] = '/one/dfsdf';
		$redirected = false;
		$redirect_to = '/gohere';
		$safe_redirect_manager->create_redirect( '/one*', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === $redirect_to ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );
	}

	/**
	 * Test replace wildcards
	 *
	 * @since 1.7.4
	 */
	public function testReplaceWildcard() {
		global $safe_redirect_manager;

		$_SERVER['REQUEST_URI'] = '/one/two';
		$redirected = false;
		$redirect_to = '/gohere/*';
		$safe_redirect_manager->create_redirect( '/one/*', $redirect_to );

		add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
			if ( $redirected_to === '/gohere/two' ) {
				$redirected = true;
			}
		}, 10, 3 );

		$safe_redirect_manager->action_parse_request();

		$this->assertTrue( $redirected );
	}

	/**
	 * Test lots of permutations of URL trailing slashes with and without regex
	 *
	 * @since 1.7.3
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
	 * @since 1.7.3
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
	 * @since 1.7.3
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

    /**
     * Test that replace (both wildcard and regex) doesn't change the casing on the matched part
     *
     * @since 1.7.5
     */
    public function testReplaceCasing() {
        global $safe_redirect_manager;

        // with wildcard
        $_SERVER['REQUEST_URI'] = '/myfiles1/FooBar.JPEG';
        $redirected = false;
        $redirect_to = '/images1/*';
        $safe_redirect_manager->create_redirect( '/myfiles1/*', $redirect_to );

        add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
            if ( $redirected_to === '/images1/FooBar.JPEG' ) {
                $redirected = true;
            }
        }, 10, 3 );

        $safe_redirect_manager->action_parse_request();

        $this->assertTrue( $redirected );

        // with regex
        $_SERVER['REQUEST_URI'] = '/myfiles2/FooBar.JPEG';
        $redirected = false;
        $redirect_to = '/images2/$1';
        $safe_redirect_manager->create_redirect( '/myfiles2/(.*\.jpe?g)', $redirect_to, 301, true );

        add_action( 'srm_do_redirect', function( $requested_path, $redirected_to, $status_code ) use ( &$redirect_to, &$redirected ) {
            if ( $redirected_to === '/images2/FooBar.JPEG' ) {
                $redirected = true;
            }
        }, 10, 3 );

        $safe_redirect_manager->action_parse_request();

        $this->assertTrue( $redirected );
    }

	/**
	 * Tests import redirects from file.
	 *
	 * @since 1.7.6
	 *
	 * @access public
	 * @global SRM_Safe_Redirect_Manager $safe_redirect_manager The plugin instance.
	 */
	public function testFileImport() {
		global $safe_redirect_manager;

		// create temp file and fill up it with redirects
		$tmp_file = tmpfile();

		$redirects = array(
			// headers
			array( 'http code', 'legacy url', 'new url', 'is_regex' ),
			// redirects
			array( 302, '/some-url', '/new-url', 0 ),
			array( 301, '/broken-url', '/fixed-url', 0 ),
			array( 301, '/reg?ex/\d+/path', '/go/here', 1 ),
		);

		foreach ( $redirects as $row ) {
			fputcsv( $tmp_file, $row );
		}

		// let's import it
		fseek( $tmp_file, 0 );
		$processed = $safe_redirect_manager->import_file( $tmp_file, array(
			'source' => 'legacy url',
			'target' => 'new url',
			'regex'  => 'is_regex',
			'code'   => 'http code',
		) );

		// assert results
		$this->assertTrue( is_array( $processed ) && ! empty( $processed['created'] ) );
		$this->assertEquals( count( $redirects ) - 1, $processed['created'] );

		// close temp file
		fclose( $tmp_file );
	}

}