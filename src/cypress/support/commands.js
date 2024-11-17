Cypress.Commands.add('login', (username = "gnyr_admin", password = "CoreysGoryStory") => {
    cy.visit({url: '/admin', failOnStatusCode: false})
    cy.get("#inputUsername").focus();
    cy.get("#inputUsername").type(username);
    cy.get("#inputPassword").focus()
    cy.get("#inputPassword").type(password);
    cy.get("#authenticateButton").contains("Authenticate").click();
})

Cypress.Commands.add('resetDatabase', () => {
    cy.request('POST', `${Cypress.env('apiUrl')}/resetDatabase`).then((response) => {
        console.log(`Status: ${response.status}`);
        console.log(`Body: ${JSON.stringify(response.body)}`);
    });
});
