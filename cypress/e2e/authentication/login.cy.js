describe('Login', () => {

    beforeEach(() => {
        // runs before each test in the block
        cy.visit('/admin')
    });

    it('Login with creds', () => {
        cy.get("#inputUsername").focus();
        cy.get("#inputUsername").type("gnyr_admin");
        cy.get("#inputPassword").focus()
        cy.get("#inputPassword").type("CoreysGoryStory");
        cy.get("#authenticateButton").contains("Authenticate").click();

        cy.get('.home-title').contains("Welcome, gnyr_admin...");
    })
})
