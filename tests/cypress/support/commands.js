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

Cypress.Commands.add( 'visitAdminPage', ( page = 'index.php' ) => {
	cy.login();
	if ( page.includes( 'http' ) ) {
		cy.visit( page );
	} else {
		cy.visit( `/wp-admin/${ page.replace( /^\/|\/$/g, '' ) }` );
	}
});

Cypress.Commands.add('createRedirectRule', (from, to, notes = '', regex = false ) => {
	cy.visitAdminPage('post-new.php?post_type=redirect_rule');

	cy.get('#srm_redirect_rule_from').click().clear().type(from);
	cy.get('#srm_redirect_rule_to').click().clear().type(to);
	cy.get('#srm_redirect_rule_notes').click().clear().type(notes);

	if ( regex ) {
		cy.get('#srm_redirect_rule_from_regex').check();
	}

	cy.get('#publish').click();
	cy.get( '.updated' ).should( 'be.visible' );
});

Cypress.Commands.add('deleteRedirectRules', () => {
	cy.visitAdminPage('edit.php?post_type=redirect_rule');
	cy.get('#cb-select-all-1').check();
	cy.get('#bulk-action-selector-top').select('trash');
	cy.get('#doaction').click();
});
