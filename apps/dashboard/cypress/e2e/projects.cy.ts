describe('project management', () => {
    const signIn = () => {
        cy.visit('/sign-in');
        cy.get('input[name="email"]').type('owner@toggleflow.test');
        cy.get('input[name="password"]').type('toggleflow-demo', { log: false });
        cy.contains('button', 'Sign in').click();
        cy.location('pathname').should('equal', '/app');
    };

    const exerciseProjectLifecycle = (width: number, height: number, suffix: string) => {
        cy.viewport(width, height);
        signIn();

        cy.contains('a', 'Projects').click();
        cy.location('pathname').should('equal', '/projects');
        cy.contains('button', 'Create project').first().click();
        cy.get('#project-name').type(`Checkout Service ${suffix}`);
        cy.get('#project-description').type('Controls checkout releases.');
        cy.contains('Development, Staging, and Production will be created').should('be.visible');
        cy.get('form').contains('button', 'Create project').click();

        cy.location('pathname').should('match', /^\/projects\/\d+$/);
        cy.contains('h1', `Checkout Service ${suffix}`).should('be.visible');
        cy.contains('Development').should('be.visible');
        cy.contains('Staging').should('be.visible');
        cy.contains('Production').should('be.visible');

        cy.contains('button', 'Edit project').click();
        cy.get('#edit-project-name').clear().type(`Checkout API ${suffix}`);
        cy.contains('button', 'Save changes').click();
        cy.contains('h1', `Checkout API ${suffix}`).should('be.visible');

        cy.contains('button', 'Archive project').click();
        cy.get('[role="dialog"]').should('be.visible').and('contain', 'leave active project views');
        cy.get('[role="dialog"]').contains('button', 'Archive project').click();

        cy.location('pathname').should('equal', '/projects');
        cy.contains(`Checkout API ${suffix}`).should('not.exist');
    };

    it('creates, reviews, renames, and archives a project on a mobile-sized screen', () => {
        exerciseProjectLifecycle(390, 844, 'Mobile');
    });

    it('creates, reviews, renames, and archives a project on a desktop-sized screen', () => {
        exerciseProjectLifecycle(1280, 900, 'Desktop');
    });

    it('shows release state and keyboard-accessible project navigation on mobile', () => {
        cy.viewport(390, 844);
        signIn();

        cy.contains('dt', 'Active projects').should('be.visible');
        cy.contains('dt', 'Active flags').should('be.visible');
        cy.contains('h2', 'Recent activity').should('be.visible');
        cy.contains('a', 'Projects').click();
        cy.contains('button', 'Create project').first().click();
        cy.get('#project-name').type('Release State Mobile');
        cy.get('form').contains('button', 'Create project').click();
        cy.contains('a', 'Manage feature flags').click();
        cy.contains('button', 'Create flag').first().click();
        cy.get('#flag-name').type('Mobile comparison');
        cy.get('form').contains('button', 'Create flag').click();

        cy.get('button[aria-label="Open navigation"]').focus().type('{enter}');
        cy.get('aside[aria-label="Mobile application navigation"]')
            .should('be.focused')
            .contains('a', 'Project overview')
            .click();
        cy.contains('h2', 'Release state').should('be.visible');
        cy.contains('th', 'Development').should('exist');
        cy.contains('th', 'Staging').should('exist');
        cy.contains('th', 'Production').should('exist');
        cy.contains('Mobile comparison').should('be.visible');

        cy.get('button[aria-label="Open navigation"]').focus().type('{enter}');
        cy.get('aside[aria-label="Mobile application navigation"]').should('be.focused').type('{esc}');
        cy.get('button[aria-label="Open navigation"]').should('be.focused');
    });
});
