<?php
/**
 * Test redirect rules
 *
 * @package safe-redirect-manager
 */

/**
 * PHPUnit test class
 */
class RedirectRuleTest extends \WPAcceptance\PHPUnit\TestCase {

	protected function createRule( $I, $from, $to, $note = '', $regex = false ) {
		$I->moveTo( 'wp-admin/post-new.php?post_type=redirect_rule' );

		$I->fillField( '#srm_redirect_rule_from', $from );

		$I->fillField( '#srm_redirect_rule_to', $to );

		$I->fillField( '#srm_redirect_rule_notes', $note );

		if ( $regex ) {
			$I->checkOptions( '#srm_redirect_rule_from_regex' );
		}

		$I->click( '#publish' );

		$I->waitUntilElementVisible( '.updated' );
	}

	/**
	 * Create redirect rule
	 */
	public function testRuleSaves() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'admin' );

		$this->createRule( $I, '/test', '/test2', 'Notes' );

		$I->seeValueInProperty( '#srm_redirect_rule_from', 'value', '/test' );

		$I->seeValueInProperty( '#srm_redirect_rule_to', 'value', '/test2' );

		$I->seeValueInProperty( '#srm_redirect_rule_notes', 'value', 'Notes' );
	}

	/**
	 * @testdox If the user creates a redirect rule, it should show it in the rule list table with redirect from, redirect to and HTTP status code.
	 */
	public function testSeeListTable() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'admin' );

		$this->createRule( $I, '/test3', '/test4', 'Test notes' );

		$I->moveTo( 'wp-admin/edit.php?post_type=redirect_rule' );

		$I->seeText( 'test3' );

		$I->seeText( 'test4' );

		$I->seeText( '302' );
	}

	/**
	 * @testdox If the user creates a redirect rule, it should redirect matched requests using correct HTTP status code.
	 */
	public function testRedirectRequest() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'admin' );

		$this->createRule( $I, '/test5', '/test6' );

		$I->moveTo( 'test5' );

		$this->assertContains( 'test6', $I->getCurrentUrl() );
	}

	/**
	 * @testdox If the user creates a redirect rule with wildcard character, it should redirect matched requests using correct HTTP status code.
	 */
	public function testRedirectWildcard() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'admin' );

		$this->createRule( $I, '/from-*', '/hello-world' );

		$I->moveTo( 'from-us' );

		$this->assertContains( 'hello-world', $I->getCurrentUrl() );

		$I->moveTo( 'from-vn' );

		$this->assertContains( 'hello-world', $I->getCurrentUrl() );
	}

	/**
	 * @testdox If the user creates a redirect rule using regular expression, it should redirect matched requests using correct HTTP status code.
	 */
	public function testRedirectRegex() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'admin' );

		$this->createRule( $I, '/news/(.*)', '/blog/$1', '', true );

		$I->moveTo( 'news/test-post' );

		$this->assertContains( 'blog/test-post', $I->getCurrentUrl() );
	}
}
