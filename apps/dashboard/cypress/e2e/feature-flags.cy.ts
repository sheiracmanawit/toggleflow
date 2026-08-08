describe('boolean feature flag management', () => {
    it('creates a disabled flag and changes environments safely on mobile', () => {
        cy.viewport(390, 844);
        cy.visit('/sign-in');
        cy.get('input[name="email"]').type('owner@toggleflow.test');
        cy.get('input[name="password"]').type('toggleflow-demo', { log: false });
        cy.contains('button', 'Sign in').click();

        cy.get('button[aria-label="Open navigation"]').click();
        cy.get('aside[aria-label="Mobile application navigation"]').contains('a', 'Projects').click();
        cy.contains('button', 'Create project').first().click();
        cy.get('#project-name').type('Flag Controls');
        cy.get('form').contains('button', 'Create project').click();
        cy.contains('a', 'Manage feature flags').click();

        cy.contains('button', 'Create flag').first().click();
        cy.get('#flag-name').type('New checkout');
        cy.get('#flag-key').should('have.value', 'new-checkout');
        cy.get('#flag-description').type('Controls the new checkout experience.');
        cy.contains('begin disabled in Development, Staging, and Production').should('be.visible');
        cy.get('form').contains('button', 'Create flag').click();

        cy.get('[aria-label="Flag lifecycle: Active"]').should('contain', 'Active');
        cy.contains('Development').should('be.visible');
        cy.contains('Staging').should('be.visible');
        cy.contains('Production').should('be.visible');
        cy.get('[aria-label="Enable New checkout in Development"]')
            .should('have.attr', 'aria-checked', 'false')
            .click();
        cy.contains('Development is now enabled').should('be.visible');
        cy.get('[aria-label="Disable New checkout in Development"]').should('have.attr', 'aria-checked', 'true');
        cy.get('[aria-label="Enable New checkout in Production"]').should('have.attr', 'aria-checked', 'false');

        cy.get('[aria-label="Enable New checkout in Production"]').focus().type('{enter}');
        cy.get('[role="dialog"]')
            .should('contain', 'will begin receiving true')
            .and('contain', 'does not deploy application code');
        cy.get('[role="dialog"]').contains('button', 'Enable in Production').click();
        cy.contains('Production is now enabled').should('be.visible');
        cy.get('[aria-label="Disable New checkout in Production"]').should('have.attr', 'aria-checked', 'true');

        cy.contains('a', '← Feature flags').click();
        cy.contains('li', 'New checkout')
            .should('contain', 'Development')
            .and('contain', 'Staging')
            .and('contain', 'Production')
            .and('contain', 'Enabled');
        cy.get('a[aria-label="Manage New checkout"]:visible').should('be.visible');
        cy.contains('a:visible', 'New checkout').click();

        cy.viewport(1280, 800);
        cy.contains('a', '← Feature flags').click();
        cy.contains('Controls the new checkout experience.').should('be.visible');
        cy.get('thead').should('contain', 'Development').and('contain', 'Staging').and('contain', 'Production');
        cy.contains('tr', 'New checkout').should('contain', 'Active').and('contain', 'Enabled');
        cy.get('a[aria-label="Manage New checkout"]:visible').should('be.visible');
        cy.contains('a:visible', 'New checkout').click();
        cy.get('[aria-label="Flag lifecycle: Active"]').should('contain', 'Active');

        cy.viewport(390, 844);
        cy.contains('button', 'Archive flag').click();
        cy.get('[role="dialog"]').should('contain', 'leave active flag views');
        cy.get('[role="dialog"]').contains('button', 'Archive flag').click();
        cy.location('pathname').should('match', /^\/projects\/\d+\/flags$/);
        cy.contains('New checkout').should('not.exist');
    });
});
