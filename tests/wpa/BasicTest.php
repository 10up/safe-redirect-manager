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
	public function testAdminMenuItemShows() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'admin' );

		$I->moveTo( 'wp-admin/tools.php' );

		$I->seeLink( 'Safe Redirect Manager' );
	}

	/**
	 * @testdox If the user goes to Tools > Safe Redirect Manager, it should show the post list table of created redirect rules and the Create Redirect Rule link.
	 */
	public function testAdminPageContent() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'admin' );

		$I->moveTo( 'wp-admin/edit.php?post_type=redirect_rule' );

		$I->seeText( 'Safe Redirect Manager' );

		$I->seeLink( 'Create Redirect Rule' );
	}
}
