describe('MVP release demonstration', () => {
    it('proves environment isolation, rollback, audit history, and credential revocation', () => {
        let productionKey = '';

        const evaluateProduction = (expectedValue: boolean) => {
            cy.then({ log: false }, () =>
                cy.request({
                    url: '/api/v1/flags/new-checkout',
                    headers: {
                        Accept: 'application/json',
                        Authorization: `Bearer ${productionKey}`,
                    },
                    log: false,
                }),
            ).then((response) => {
                expect(response.status).to.equal(200);
                expect(response.body).to.deep.equal({
                    data: {
                        key: 'new-checkout',
                        value: expectedValue,
                        reason: 'STATIC',
                    },
                });
            });
        };

        cy.viewport(1280, 800);
        cy.visit('/sign-in');
        cy.get('input[name="email"]').type('owner@toggleflow.test');
        cy.get('input[name="password"]').type('toggleflow-demo', { log: false });
        cy.contains('button', 'Sign in').click();

        cy.contains('a', 'Projects').first().click();
        cy.contains('a', 'Checkout Service').click();
        cy.contains('a', 'API keys').click();
        cy.contains('button', 'Issue API key').click();
        cy.get('#api-key-name').type('Release demo Production');
        cy.get('#api-key-environment').select('Production');
        cy.get('form').contains('button', 'Issue API key').click();
        cy.get('[role="dialog"] code', { log: false })
            .first()
            .invoke({ log: false }, 'text')
            .then({ log: false }, (key) => {
                productionKey = key.trim();
                expect(productionKey).to.match(/^tf_env_[a-f0-9]+_[a-f0-9]+$/);
            });
        cy.get('[role="dialog"] code', { log: false }).first().invoke({ log: false }, 'text', '[redacted]');
        cy.get('[role="dialog"] input[type="checkbox"]').check();
        cy.get('[role="dialog"]').contains('button', 'Done').click();

        evaluateProduction(false);

        cy.get('nav[aria-label="Application"]').contains('a', 'Feature flags').click();
        cy.contains('a', 'New checkout').click();
        cy.get('[aria-label="Enable New checkout in Development"]').click();
        cy.contains('Development is now enabled').should('be.visible');
        evaluateProduction(false);

        cy.get('[aria-label="Enable New checkout in Production"]').focus().type('{enter}');
        cy.get('[role="dialog"]')
            .should('contain', 'will begin receiving true')
            .contains('button', 'Enable in Production')
            .click();
        cy.contains('Production is now enabled').should('be.visible');
        evaluateProduction(true);

        cy.get('[aria-label="Disable New checkout in Production"]').focus().type('{enter}');
        cy.get('[role="dialog"]')
            .should('contain', 'will begin receiving false')
            .contains('button', 'Disable in Production')
            .click();
        cy.contains('Production is now disabled').should('be.visible');
        evaluateProduction(false);

        cy.get('nav[aria-label="Application"]').contains('a', 'Audit history').click();
        cy.contains('Demo Owner enabled feature flag New checkout for Production').should('be.visible');
        cy.contains('Demo Owner disabled feature flag New checkout for Production').should('be.visible');

        cy.get('nav[aria-label="Application"]').contains('a', 'API keys').click();
        cy.contains('Release demo Production')
            .parents('tr')
            .within(() => cy.get('button[aria-label="Revoke Release demo Production"]').click());
        cy.get('[role="dialog"]').contains('button', 'Revoke API key').click();
        cy.contains('Release demo Production').parents('tr').should('contain', 'Revoked');

        cy.then({ log: false }, () =>
            cy.request({
                url: '/api/v1/flags/new-checkout',
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${productionKey}`,
                },
                failOnStatusCode: false,
                log: false,
            }),
        ).then((response) => {
            expect(response.status).to.equal(401);
            expect(response.body).to.deep.equal({
                error: {
                    code: 'INVALID_API_KEY',
                    message: 'The supplied API key is invalid or has been revoked.',
                },
            });
        });
    });
});
