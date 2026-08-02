describe('environment API key management', () => {
    it('issues overlapping keys with one-time disclosure and revokes one safely', () => {
        cy.viewport(390, 844);
        cy.visit('/sign-in');
        cy.get('input[name="email"]').type('owner@toggleflow.test');
        cy.get('input[name="password"]').type('toggleflow-demo', { log: false });
        cy.contains('button', 'Sign in').click();

        cy.get('button[aria-label="Open navigation"]').click();
        cy.get('aside[aria-label="Mobile application navigation"]').contains('a', 'Projects').click();
        cy.contains('button', 'Create project').first().click();
        cy.get('#project-name').type('Credential Controls');
        cy.get('form').contains('button', 'Create project').click();
        cy.contains('a', 'Manage API keys').click();

        cy.contains('button', 'Issue API key').click();
        cy.get('#api-key-name').type('Production primary');
        cy.get('#api-key-environment').select('Production');
        cy.get('form').contains('button', 'Issue API key').click();

        cy.get('[role="dialog"]')
            .should('contain', 'shown once')
            .and('contain', 'Production primary')
            .find('code')
            .first()
            .invoke('text')
            .should('match', /tf_env_[a-f0-9]+_[a-f0-9]+/);
        cy.get('[role="dialog"]').contains('button', 'Done').should('be.disabled');
        cy.get('[role="dialog"]').get('input[type="checkbox"]').check();
        cy.get('[role="dialog"]').contains('button', 'Done').click();
        cy.get('[role="dialog"]').should('not.exist');
        cy.contains('tf_env_').should('be.visible');

        cy.contains('button', 'Issue API key').click();
        cy.get('#api-key-name').type('Production replacement');
        cy.get('#api-key-environment').select('Production');
        cy.get('form').contains('button', 'Issue API key').click();
        cy.get('[role="dialog"]').get('input[type="checkbox"]').check();
        cy.get('[role="dialog"]').contains('button', 'Done').click();

        cy.contains('Production primary')
            .parents('li')
            .within(() => cy.contains('button', 'Revoke').click());
        cy.get('[role="dialog"]')
            .should('contain', 'immediately lose evaluation access')
            .contains('button', 'Revoke API key')
            .click();

        cy.contains('Production primary').parents('li').should('contain', 'Revoked');
        cy.contains('Production replacement').parents('li').should('contain', 'Active');
    });
});
