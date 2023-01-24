// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
Cypress.Commands.add('createRedirectRule', (from, to, notes = '', regex = false, status = '302', message = '' ) => {
	cy.visit('/wp-admin/post-new.php?post_type=redirect_rule');

	cy.get('#srm_redirect_rule_from').click().clear().type(from);
	if (to !== '') {
		cy.get('#srm_redirect_rule_to').click().clear().type(to);
	}
	cy.get('#srm_redirect_rule_status_code').select(status);
	cy.get('#srm_redirect_rule_notes').click().clear().type(notes);

	if ( regex ) {
		cy.get('#srm_redirect_rule_from_regex').check();
	}

	if ('' !== message) {
		cy.get('#srm_redirect_rule_message').click().clear().type(message);
	}

	cy.get('#publish').click();
	cy.get( '.updated' ).should( 'be.visible' );
});

Cypress.Commands.add('verifyRedirectRule', (from, to) => {
	cy.visit(`/${from}`);
	cy.url().should('include', to);
	cy.visit(`/${from}/`);
	cy.url().should('include', to);
});

Cypress.Commands.add('verifyStatusCode', (from, status) => {
	cy.request({url: `/${from}`, failOnStatusCode: false}).its('status').should('equal', status);
	cy.visit(`/${from}`, {failOnStatusCode: false});
});

Cypress.Commands.add('verifyEndpointDead', (from, message) => {
	cy.visit(`/${from}/`, {failOnStatusCode: false});
	cy.get('.wp-die-message').should('exist');
	cy.get('.wp-die-message').contains(message);
});
