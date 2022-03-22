describe( 'Admin can login and make sure plugin is activated', () => {
	it( 'Can activate plugin if it is deactivated', () => {
		cy.login();
    	cy.visit('/wp-admin/plugins.php');

		cy.get( '#deactivate-safe-redirect-manager' ).click();
		cy.get( '#activate-safe-redirect-manager' ).click();
		cy.get( '#deactivate-safe-redirect-manager' ).should( 'be.visible' );
	} );
} );


