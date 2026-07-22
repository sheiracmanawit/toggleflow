describe('dashboard authentication', () => {
    const signIn = () => {
        cy.visit('/sign-in');
        cy.get('input[name="email"]').type('owner@toggleflow.test');
        cy.get('input[name="password"]').type('toggleflow-demo', { log: false });
        cy.contains('button', 'Sign in').click();
        cy.location('pathname').should('equal', '/app');
        cy.contains('Signed in as Demo Owner').should('be.visible');
    };

    it('signs in, persists the session, and signs out without restoring protected state', () => {
        cy.visit('/app');
        cy.location('pathname').should('equal', '/sign-in');
        cy.contains('authentication is required').should('be.visible');

        signIn();
        cy.reload();
        cy.location('pathname').should('equal', '/app');
        cy.contains('Signed in as Demo Owner').should('be.visible');

        cy.contains('button', 'Sign out').click();
        cy.location('pathname').should('equal', '/sign-in');
        cy.go('back');
        cy.location('pathname').should('equal', '/sign-in');
        cy.contains('h1', 'Dashboard').should('not.exist');
    });

    it('returns an expired session to sign in without presenting protected state', () => {
        signIn();
        cy.clearCookie('toggleflow-session');
        cy.reload();

        cy.location('pathname').should('equal', '/sign-in');
        cy.contains('session has expired').should('be.visible');
        cy.contains('h1', 'Dashboard').should('not.exist');
    });

    it('shows a generic error for invalid credentials and permits retry', () => {
        cy.visit('/sign-in');
        cy.get('input[name="email"]').type('missing@example.com');
        cy.get('input[name="password"]').type('not-the-password', { log: false });
        cy.contains('button', 'Sign in').click();

        cy.get('[role="alert"]').should('have.focus').and('contain', 'provided credentials are invalid');
        cy.get('input[name="email"]').clear().type('owner@toggleflow.test');
        cy.get('input[name="password"]').clear().type('toggleflow-demo', { log: false });
        cy.contains('button', 'Sign in').click();
        cy.location('pathname').should('equal', '/app');
    });
});
