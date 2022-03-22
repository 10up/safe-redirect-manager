describe( 'Admin can login and make sure plugin is activated', () => {

	beforeEach(() => {
		cy.login();
	});

	it( 'Can activate plugin if it is deactivated', () => {
    	cy.visit('/wp-admin/plugins.php');

		cy.get( '#deactivate-safe-redirect-manager' ).click();
		cy.get( '#activate-safe-redirect-manager' ).click();
		cy.get( '#deactivate-safe-redirect-manager' ).should( 'be.visible' );
	});

	it( 'Can see "Safe Redirect Manager" menu item under Tools menu', () => {
		cy.visit('/wp-admin/');

		// Check menu item under Tools menu.
		cy.get('#menu-tools ul.wp-submenu li').filter(':contains("Safe Redirect Manager")').should('have.length', 1)
	});

	it('Can visit "Safe Redirect Manager" page', () => {
		cy.visit('/wp-admin/edit.php?post_type=redirect_rule');

		// Check Heading and create link.
		cy.get('#wpbody h1').contains('Safe Redirect Manager');
		cy.get('#wpbody a.page-title-action').should('have.text', 'Create Redirect Rule');
	});
});


