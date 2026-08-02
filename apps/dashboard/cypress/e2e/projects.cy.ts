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

        if (width < 768) {
            cy.get('button[aria-label="Open navigation"]').click();
            cy.get('aside[aria-label="Mobile application navigation"]').contains('a', 'Projects').click();
        } else {
            cy.get('header').contains('a', 'Projects').click();
        }
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

        if (width < 768) {
            cy.get('button[aria-label="Open navigation"]').click();
            cy.get('aside[aria-label="Mobile application navigation"]')
                .should('contain', `Checkout API ${suffix}`)
                .contains('button', 'Close')
                .click();
        } else {
            cy.get('nav[aria-label="Application"]').should('contain', `Checkout API ${suffix}`);
        }

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

        cy.get('header').contains('a', 'Projects').should('not.be.visible');
        cy.document().then((document) => {
            expect(document.documentElement.scrollWidth).to.be.at.most(document.documentElement.clientWidth);
        });

        cy.contains('dt', 'Active projects').should('be.visible');
        cy.contains('dt', 'Active flags').should('be.visible');
        cy.contains('h2', 'Recent activity').should('be.visible');
        cy.get('button[aria-label="Open navigation"]').click();
        cy.get('aside[aria-label="Mobile application navigation"]')
            .should('contain', 'Demo Owner')
            .and('contain', 'Sign out')
            .contains('a', 'Projects')
            .click();
        cy.contains('button', 'Create project').first().click();
        cy.get('#project-name').type('Release State Mobile');
        cy.get('form').contains('button', 'Create project').click();
        cy.contains('a', 'Manage feature flags').click();
        cy.contains('button', 'Create flag').first().click();
        cy.get('#flag-name').type('Mobile comparison');
        cy.get('form').contains('button', 'Create flag').click();

        cy.get('button[aria-label="Open navigation"]').focus().type('{enter}');
        cy.get('aside[aria-label="Mobile application navigation"]').within(() => {
            cy.contains('button', 'Close').should('be.focused');
            cy.contains('a', 'Project overview').click();
        });
        cy.contains('h2', 'Release state').should('be.visible');
        cy.get('[aria-label="Mobile release state"]')
            .should('be.visible')
            .and('contain', 'Mobile comparison')
            .within(() => {
                cy.contains('dt', 'Development').should('be.visible');
                cy.contains('dt', 'Staging').should('be.visible');
                cy.contains('dt', 'Production').should('be.visible');
                cy.contains('dd', 'Disabled').should('be.visible');
            });

        cy.get('button[aria-label="Open navigation"]').focus().type('{enter}');
        cy.get('aside[aria-label="Mobile application navigation"]').within(() => {
            cy.contains('button', 'Close').should('be.focused').trigger('keydown', { key: 'Tab', shiftKey: true });
            cy.contains('button', 'Sign out').should('be.focused').trigger('keydown', { key: 'Tab' });
            cy.contains('button', 'Close').should('be.focused');
        });
        cy.get('aside[aria-label="Mobile application navigation"]').trigger('keydown', { key: 'Escape' });
        cy.get('button[aria-label="Open navigation"]').should('be.focused');
    });
});
