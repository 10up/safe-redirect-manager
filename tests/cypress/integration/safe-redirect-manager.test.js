describe("Test redirect rules", () => {
	after(() => {
		cy.deleteRedirectRules();
	});

	it("Can create a rule", () => {
		// Test Create Rule.
		cy.createRedirectRule("/test", "/test2", "sample rule note");

		// Validate created rule in list table.
		cy.visit("/wp-admin/edit.php?post_type=redirect_rule");
		cy.get("#the-list td.title a").first().should("have.text", "/test");
		cy.get("#the-list td.srm_redirect_rule_to")
			.first()
			.should("have.text", "/test2");
		cy.get("#the-list td.srm_redirect_rule_status_code")
			.first()
			.should("have.text", "302");
	});

	it("Can redirect a simple rule request", () => {
		cy.createRedirectRule(
			"/first-blog",
			"/hello-world",
			"Simple rule note"
		);

		cy.visit("/first-blog");
		cy.url().should("include", "/hello-world");
	});

	it("Can redirect a wildcard rule request", () => {
		cy.createRedirectRule("/test*", "/sample-page", "Wildcard rule note");

		cy.visit("/test-1");
		cy.url().should("include", "/sample-page");
	});

	it("Can redirect a Regex rule request", () => {
		cy.createRedirectRule(
			"/blog/(.*)",
			"/hello-world",
			"Regex rule note",
			true
		);

		cy.visit("/blog/1");
		cy.url().should("include", "/hello-world");
	});
});
