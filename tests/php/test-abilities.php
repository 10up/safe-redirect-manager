<?php
/**
 * Test Abilities API integration
 *
 * @package safe-redirect-manager
 */

class SRMTestAbilities extends WP_UnitTestCase {

	/**
	 * Test creating, querying, updating and deleting a redirect
	 */
	public function testAbilities() {
		$run = function ( $name, $input = null ) {
			return wp_get_ability( 'safe-redirect-manager/' . $name )->execute( $input );
		};

		$this->assertWPError( $run( 'redirects-query' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$id = $run(
			'redirect-create',
			array(
				'redirect_from' => '/old/',
				'redirect_to'   => '/new/',
			)
		)['id'];

		$this->assertContains( $id, wp_list_pluck( $run( 'redirects-query' ), 'ID' ) );
		$this->assertWPError( $run( 'redirect-update', array( 'id' => $id, 'status_code' => 999 ) ) );
		$this->assertWPError( $run( 'redirect-update', array( 'id' => $id, 'redirect_from' => ' ' ) ) );
		$this->assertSame( '/old/', get_post_meta( $id, '_redirect_rule_from', true ) );

		$run( 'redirect-update', array( 'id' => $id, 'redirect_to' => '/newer/', 'post_status' => 'draft' ) );
		$this->assertSame( '/newer/', get_post_meta( $id, '_redirect_rule_to', true ) );
		$this->assertSame( 'draft', get_post_status( $id ) );

		$run( 'redirect-delete', array( 'id' => $id ) );
		$this->assertSame( 'trash', get_post_status( $id ) );
	}
}
