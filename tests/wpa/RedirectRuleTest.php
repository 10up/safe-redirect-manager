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
	/**
	 * Create redirect rule
	 */
	public function testRuleSaves() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'admin' );

		$I->moveTo( 'wp-admin/post-new.php?post_type=redirect_rule' );

		$I->fillField( '#srm_redirect_rule_from', '/test' );

		$I->fillField( '#srm_redirect_rule_to', '/test2' );

		$I->fillField( '#srm_redirect_rule_notes', 'Notes' );

		$I->click( '#publish' );

		$I->waitUntilElementVisible( '.updated' );

		$I->seeValueInProperty( '#srm_redirect_rule_from', 'value', '/test' );

		$I->seeValueInProperty( '#srm_redirect_rule_to', 'value', '/test2' );

		$I->seeValueInProperty( '#srm_redirect_rule_notes', 'value', 'Notes' );
	}
}
