Cypress.Commands.add('login', (username = "gnyr_admin", password = "CoreysGoryStory") => {
    cy.visit({url: '/admin', failOnStatusCode: false})
    cy.get("#inputUsername").focus();
    cy.get("#inputUsername").type(username);
    cy.get("#inputPassword").focus()
    cy.get("#inputPassword").type(password);
    cy.get("#authenticateButton").contains("Authenticate").click();
})
