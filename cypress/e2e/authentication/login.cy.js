describe('Login', () => {

    beforeEach(() => {
        // runs before each test in the block
        cy.visit('http://localhost:3100/yap/admin')
    });

    it('Login with creds', () => {
        cy.get("#inputUsername").focus();
        cy.get("#inputUsername").type("admin");
        cy.get("#inputPassword").focus()
        cy.get("#inputPassword").type("admin");
        cy.get("#authenticateButton").contains("Authenticate").click();

        cy.get('.home-title').contains("Welcome, admin...");
    })
})
