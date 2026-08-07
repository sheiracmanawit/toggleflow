describe('audit history', () => {
    const signIn = () => {
        cy.visit('/sign-in');
        cy.get('input[name="email"]').type('owner@toggleflow.test');
        cy.get('input[name="password"]').type('toggleflow-demo', { log: false });
        cy.contains('button', 'Sign in').click();
    };

    it('shows readable project changes from desktop navigation', () => {
        cy.viewport(1280, 900);
        signIn();
        cy.contains('a', 'Projects').first().click();
        cy.contains('button', 'Create project').first().click();
        cy.get('#project-name').type('Audited Checkout');
        cy.get('form').contains('button', 'Create project').click();
        cy.contains('button', 'Edit project').click();
        cy.get('#edit-project-name').clear().type('Renamed Checkout');
        cy.contains('button', 'Save changes').click();

        cy.get('nav[aria-label="Application"]').contains('a', 'Audit history').click();
        cy.location('pathname').should('match', /^\/projects\/\d+\/audit-log$/);
        cy.contains('h1', 'Audit history').should('be.visible');
        cy.get('main ol').within(() => {
            cy.contains('Demo Owner updated project Renamed Checkout').should('be.visible');
            cy.contains('Demo Owner created project Audited Checkout').should('be.visible');
        });
        cy.get('time').first().should('have.attr', 'datetime');
    });

    it('remains usable from keyboard-accessible mobile navigation', () => {
        cy.viewport(390, 844);
        signIn();
        cy.get('button[aria-label="Open navigation"]').should('be.visible').focus().type('{enter}');
        cy.get('aside[aria-label="Mobile application navigation"]').contains('a', 'Projects').click();
        cy.contains('button', 'Create project').first().click();
        cy.get('#project-name').type('Mobile Audit');
        cy.get('form').contains('button', 'Create project').click();
        cy.contains('h1', 'Mobile Audit').should('be.visible');
        cy.get('button[aria-label="Open navigation"]').should('be.visible').focus().type('{enter}');
        cy.get('aside[aria-label="Mobile application navigation"]').contains('a', 'Audit history').click();
        cy.contains('Demo Owner created project Mobile Audit').should('be.visible');
        cy.document().then((document) => {
            expect(document.documentElement.scrollWidth).to.be.at.most(document.documentElement.clientWidth);
        });
    });
});
