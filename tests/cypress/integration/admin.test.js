describe('Admin can login and make sure plugin is activated', () => {
	beforeEach(() => {
		cy.login();
	});

	it('Can activate plugin if it is deactivated', () => {
		cy.activatePlugin('safe-redirect-manager');
	});

	it('Can see "Safe Redirect Manager" menu item under Tools menu', () => {
		cy.visit('/wp-admin/tools.php');

		// Check menu item under Tools menu.
		cy.get('#menu-tools ul.wp-submenu li')
			.filter(':contains("Safe Redirect Manager")')
			.should('have.length', 1);
	});

	it('Can switch the export format and get a matching download link', () => {
		cy.createRedirectRule('/export-test', '/export-test-2', 'export rule note');
		cy.visit('/wp-admin/edit.php?post_type=redirect_rule');

		// Option values are opaque format keys, never the URLs themselves.
		cy.get('#srm-export-format option').should(($options) => {
			expect(
				[...$options].map((option) => option.value)
			).to.deep.equal(['csv', 'json']);
		});

		// The href comes from the localized map, not from the option value.
		cy.get('#srm-export-btn')
			.should('have.attr', 'href')
			.and('include', 'export_format=csv')
			.and('include', '_wpnonce=');

		cy.get('#srm-export-format').select('json');

		cy.get('#srm-export-btn')
			.should('have.attr', 'href')
			.and('include', 'export_format=json')
			.and('include', '_wpnonce=');
	});

	it('Can visit "Safe Redirect Manager" page', () => {
		cy.visit('/wp-admin/edit.php?post_type=redirect_rule');

		// Check Heading and create link.
		cy.get('#wpbody h1').contains('Safe Redirect Manager');
		cy.get('#wpbody a.page-title-action').should(
			'have.text',
			'Create Redirect Rule'
		);
	});
});
