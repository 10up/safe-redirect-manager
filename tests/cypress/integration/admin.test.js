describe("Admin can login and make sure plugin is activated", () => {
	it("Can activate plugin if it is deactivated", () => {
		cy.visitAdminPage("plugins.php");

		cy.get('[aria-label="Deactivate Safe Redirect Manager"]').click();
		cy.get('[aria-label="Activate Safe Redirect Manager"]').click();
		cy.get('[aria-label="Deactivate Safe Redirect Manager"]').should("be.visible");
	});

	it('Can see "Safe Redirect Manager" menu item under Tools menu', () => {
		cy.visitAdminPage("/");

		// Check menu item under Tools menu.
		cy.get("#menu-tools ul.wp-submenu li")
			.filter(':contains("Safe Redirect Manager")')
			.should("have.length", 1);
	});

	it('Can visit "Safe Redirect Manager" page', () => {
		cy.visitAdminPage("edit.php?post_type=redirect_rule");

		// Check Heading and create link.
		cy.get("#wpbody h1").contains("Safe Redirect Manager");
		cy.get("#wpbody a.page-title-action").should(
			"have.text",
			"Create Redirect Rule"
		);
	});
});
