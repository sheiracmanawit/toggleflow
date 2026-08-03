describe('responsive and accessible release surfaces', () => {
    const signIn = () => {
        cy.visit('/sign-in');
        cy.get('input[name="email"]').type('owner@toggleflow.test');
        cy.get('input[name="password"]').type('toggleflow-demo', { log: false });
        cy.contains('button', 'Sign in').click();
    };

    const expectNoPageOverflow = () => {
        cy.document().then((document) => {
            expect(document.documentElement.scrollWidth).to.be.at.most(document.documentElement.clientWidth);
        });
    };

    const checkSeededReleaseSurfaces = () => {
        cy.window().then((window) => {
            if (window.innerWidth < 768) {
                cy.get('button[aria-label="Open navigation"]').click();
                cy.get('aside[aria-label="Mobile application navigation"]').contains('a', 'Projects').click();
            } else {
                cy.get('header').contains('a', 'Projects').click();
            }
        });
        cy.contains('a', 'Checkout Service').click();
        cy.contains('h1', 'Checkout Service').should('be.visible');
        expectNoPageOverflow();

        cy.visit('/projects/1/flags');
        cy.contains('h1', 'Feature flags').should('be.visible');
        cy.contains('a:visible', 'New checkout').should('be.visible');
        expectNoPageOverflow();

        cy.visit('/projects/1/api-keys');
        cy.contains('h1', 'API keys').should('be.visible');
        expectNoPageOverflow();

        cy.visit('/projects/1/audit-log');
        cy.contains('h1', 'Audit history').should('be.visible');
        expectNoPageOverflow();
    };

    it('keeps the primary workflow keyboard reachable at the mobile baseline', () => {
        cy.viewport(375, 812);
        signIn();
        cy.get('button[aria-label="Open navigation"]').focus().type('{enter}');
        cy.get('aside[aria-label="Mobile application navigation"]').should('be.visible');
        cy.focused().should('contain', 'Close');
        cy.focused().type('{esc}');
        cy.get('aside[aria-label="Mobile application navigation"]').should('not.exist');
        cy.get('button[aria-label="Open navigation"]').should('have.focus');
        checkSeededReleaseSurfaces();
    });

    it('reflows the primary workflow at the tablet baseline', () => {
        cy.viewport(768, 1024);
        signIn();
        checkSeededReleaseSurfaces();
    });

    it('presents the complete primary workflow at the desktop baseline', () => {
        cy.viewport(1280, 800);
        signIn();
        checkSeededReleaseSurfaces();
    });
});
