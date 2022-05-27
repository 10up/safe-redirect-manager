describe('Test redirect rules', () => {
	before(() => {
		cy.login();
	});

	it('Can create a redirect rule', () => {
		// Test Create Rule.
		cy.createRedirectRule('/test', '/test2', 'sample rule note');

		// Validate created rule in list table.
		cy.visit('/wp-admin/edit.php?post_type=redirect_rule');
		cy.get('#the-list td.title a').first().should('have.text', '/test');
		cy.get('#the-list td.srm_redirect_rule_to')
			.first()
			.should('have.text', '/test2');
		cy.get('#the-list td.srm_redirect_rule_status_code')
			.first()
			.should('have.text', '302');
	});

	it('Can redirect a simple rule request', () => {
		// no leading slash, no trailing slash
		cy.createRedirectRule(
			'first-blog',
			'hello-world',
			'Simple rule note (no leading slash, no trailing slash)'
		);
		cy.visit('/first-blog');
		cy.url().should('include', '/hello-world');
		cy.visit('/first-blog/');
		cy.url().should('include', '/hello-world');


		// leading slash, no trailing slash
		cy.createRedirectRule(
			'/first-blog-2',
			'/hello-world',
			'Simple rule note (leading slash, no trailing slash)'
		);
		cy.visit('/first-blog-2');
		cy.url().should('include', '/hello-world');
		cy.visit('/first-blog-2/');
		cy.url().should('include', '/hello-world');

		// no leading slash, trailing slash
		cy.createRedirectRule(
			'first-blog-3/',
			'hello-world/',
			'Simple rule note (no leading slash, trailing slash)'
		);
		cy.visit('/first-blog-3');
		cy.url().should('include', '/hello-world');
		cy.visit('/first-blog-3/');
		cy.url().should('include', '/hello-world');

		// leading slash, trailing slash
		cy.createRedirectRule(
			'/first-blog-4/',
			'/hello-world/',
			'Simple rule note (leading slash, trailing slash)'
		);
		cy.visit('/first-blog-4');
		cy.url().should('include', '/hello-world');
		cy.visit('/first-blog-4/');
		cy.url().should('include', '/hello-world');
	});

	it.skip('Can redirect a wildcard rule request', () => {
		// no leading slash, no trailing slash
		cy.createRedirectRule('test*', 'sample-page', 'Wildcard rule note (no leading slash, no trailing slash)');
		cy.visit('/test-1');
		cy.url().should('include', '/sample-page');
		cy.visit('/test-1/');
		cy.url().should('include', '/sample-page');

		// leading slash, no trailing slash
		cy.createRedirectRule('/2-test*', '/sample-page', 'Wildcard rule note (leading slash, no trailing slash)');
		cy.visit('/2-test-1');
		cy.url().should('include', '/sample-page');
		cy.visit('/2-test-1/');
		cy.url().should('include', '/sample-page');

		// no leading slash, trailing slash
		cy.createRedirectRule('3-test*/', 'sample-page/', 'Wildcard rule note (no leading slash, trailing slash)');
		cy.visit('/3-test-1');
		cy.url().should('include', '/sample-page');
		cy.visit('/3-test-1/');
		cy.url().should('include', '/sample-page');

		// leading slash, trailing slash
		cy.createRedirectRule('/4-test*/', '/sample-page/', 'Wildcard rule note (leading slash, trailing slash)');
		cy.visit('/4-test-1');
		cy.url().should('include', '/sample-page');
		cy.visit('/4-test-1/');
		cy.url().should('include', '/sample-page');
	});

	it('Can redirect a Regex rule request', () => {
		// TODO: Uncomment this test case once issue #269 get resolved.
		// // no leading slash, no trailing slash
		// cy.createRedirectRule(
		// 	'blog/(.*)',
		// 	'hello-world',
		// 	'Regex rule note (no leading slash, no trailing slash)',
		// 	true
		// );
		// cy.visit('/blog/1');
		// cy.url().should('include', '/hello-world');
		// cy.visit('/blog/1/');
		// cy.url().should('include', '/hello-world');

		// leading slash, no trailing slash
		cy.createRedirectRule(
			'/blog-2/(.*)',
			'/hello-world',
			'Regex rule note (leading slash, no trailing slash)',
			true
		);
		cy.visit('/blog-2/1');
		cy.url().should('include', '/hello-world');
		cy.visit('/blog-2/1/');
		cy.url().should('include', '/hello-world');

		// TODO: Uncomment this test case once issue #269 get resolved.
		// // no leading slash, trailing slash
		// cy.createRedirectRule(
		// 	'blog-3/(.*)/',
		// 	'hello-world/',
		// 	'Regex rule note (no leading slash, trailing slash)',
		// 	true
		// );
		// cy.visit('/blog-3/1');
		// cy.url().should('include', '/hello-world');
		// cy.visit('/blog-3/1/');
		// cy.url().should('include', '/hello-world');

		// leading slash, trailing slash
		cy.createRedirectRule(
			'/blog-4/(.*)/',
			'/hello-world/',
			'Regex rule note (leading slash, trailing slash)',
			true
		);
		cy.visit('/blog-4/1');
		cy.url().should('include', '/hello-world');
		cy.visit('/blog-4/1/');
		cy.url().should('include', '/hello-world');
	});
});
