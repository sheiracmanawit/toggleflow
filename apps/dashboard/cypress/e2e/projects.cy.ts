describe('project management', () => {
    const signIn = () => {
        cy.visit('/sign-in');
        cy.get('input[name="email"]').type('owner@toggleflow.test');
        cy.get('input[name="password"]').type('toggleflow-demo', { log: false });
        cy.contains('button', 'Sign in').click();
        cy.location('pathname').should('equal', '/app');
    };

    it('creates, reviews, renames, and archives a project on a mobile-sized screen', () => {
        cy.viewport(390, 844);
        signIn();

        cy.contains('a', 'Projects').click();
        cy.location('pathname').should('equal', '/projects');
        cy.contains('Create your first application project').should('be.visible');
        cy.contains('button', 'Create project').first().click();
        cy.get('#project-name').type('Checkout Service');
        cy.get('#project-description').type('Controls checkout releases.');
        cy.contains('Development, Staging, and Production will be created').should('be.visible');
        cy.get('form').contains('button', 'Create project').click();

        cy.location('pathname').should('match', /^\/projects\/\d+$/);
        cy.contains('h1', 'Checkout Service').should('be.visible');
        cy.contains('Development').should('be.visible');
        cy.contains('Staging').should('be.visible');
        cy.contains('Production').should('be.visible');

        cy.contains('button', 'Edit project').click();
        cy.get('#edit-project-name').clear().type('Checkout API');
        cy.contains('button', 'Save changes').click();
        cy.contains('h1', 'Checkout API').should('be.visible');

        cy.contains('button', 'Archive project').click();
        cy.get('[role="dialog"]').should('be.visible').and('contain', 'leave active project views');
        cy.get('[role="dialog"]').contains('button', 'Archive project').click();

        cy.location('pathname').should('equal', '/projects');
        cy.contains('Create your first application project').should('be.visible');
        cy.contains('Checkout API').should('not.exist');
    });
});
