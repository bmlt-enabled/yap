Cypress.Commands.add('login', () => {
    cy.visit({url: '/admin', failOnStatusCode: false})
    cy.get("#inputUsername").focus();
    cy.get("#inputUsername").type("gnyr_admin");
    cy.get("#inputPassword").focus()
    cy.get("#inputPassword").type("CoreysGoryStory");
    cy.get("#authenticateButton").contains("Authenticate").click();
})
