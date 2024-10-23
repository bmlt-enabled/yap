describe('Groups', () => {

    beforeEach(() => {

    });

    it('Add a new group', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Groups')
            .click()
            .get('#service_body_id option:selected')
            .should('have.text', '-= Select A Service Body =-')


        cy.get('#service_body_id')
            .select(1)

        cy.get("#addGroupButton").click()

        cy.get("#group_name").type("testgroup1");

        cy.wait(500);

        // Click to close the modal
        cy.get('#addGroupDialog > .modal-dialog > .modal-content > .modal-footer > .btn-primary').click();

        // Use a wait to ensure transition ends (if necessary)
        cy.wait(500); // Adjust time based on your modal’s transition duration

        // Ensure the modal is hidden
        cy.get('#addGroupDialog').should('not.be.visible');
    })

    it('Edit a group', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Groups')
            .click()
            .get('#service_body_id option:selected')
            .should('have.text', '-= Select A Service Body =-')

        cy.get('#service_body_id')
            .select(1)

        cy.get('#group_id')
            .select(1)

        cy.get("#editGroupButton").click()

        cy.get("#group_name")
            .clear()
            .invoke("val", "testgroup1-modified");

        cy.wait(500);

        // Click to close the modal
        cy.get('#addGroupDialog > .modal-dialog > .modal-content > .modal-footer > .btn-primary').click();

        // Use a wait to ensure transition ends (if necessary)
        cy.wait(500); // Adjust time based on your modal’s transition duration

        // Ensure the modal is hidden
        cy
            .get('#addGroupDialog')
            .should('not.be.visible')
            .wait(5000)
            .get("#group_id")
            .select(1)
            .get('#group_id option:selected')
            .should('have.text', 'testgroup1-modified');
    })

    it('Delete a group', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Groups')
            .click()
            .get('#service_body_id option:selected')
            .should('have.text', '-= Select A Service Body =-')

        cy.get('#service_body_id')
            .select(1)

        cy.get('#group_id')
            .select(1)

        cy.get("#deleteGroupButton").click()

        cy.wait(500);

        cy.get('#service_body_id')
            .select(1)

        cy.get("#group_id")
            .should('be.empty');
    })
})
