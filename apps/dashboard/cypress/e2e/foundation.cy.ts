describe('ToggleFlow application foundation', () => {
    it('connects the dashboard to the Laravel API and Sanctum', () => {
        cy.visit('/');
        cy.get('#app').should('exist');

        cy.request('/api/management/foundation').its('body').should('deep.equal', { boundary: 'management' });

        cy.request('/api/v1/foundation').its('body').should('deep.equal', { boundary: 'evaluation', version: 'v1' });

        cy.request({ url: '/api/v1/missing', failOnStatusCode: false }).its('status').should('equal', 404);

        cy.request('/sanctum/csrf-cookie').its('status').should('equal', 204);
        cy.getCookie('XSRF-TOKEN').should('exist');
        cy.getCookie('toggleflow-session').should('exist');
    });
});
