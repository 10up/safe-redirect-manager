<?php
/**
 * Basic SRM tests
 *
 * @package safe-redirect-manager
 */

/**
 * PHPUnit test class
 */
class BasicTest extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * Home page loads properly
	 */
	public function testHomePageLoads() {
		$I = $this->openBrowserPage();

		$I->moveTo( '/' );

		$I->seeElement( 'body.home' );
	}

	/**
	 * Admin dashboard loads properly
	 */
	public function testAdminLoads() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'admin' );

		$I->seeElement( '#wpadminbar' );
	}

	/**
	 * Admin menu item shows
	 */
	public function testAdaminMenuItemShows() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'admin' );

		$I->moveTo( 'wp-admin/tools.php' );

		$I->seeLink( 'Safe Redirect Manager' );
	}
}
