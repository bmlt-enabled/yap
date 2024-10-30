describe('Volunteers', () => {

    before(() => {
        cy.resetDatabase();
    });

    it('Save Volunteers', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .get('#service-bodies-table tr:contains(\'Brooklyn\')')
            .should('contain', 'Brooklyn Area Service (1006)')
            .get("[onclick=\"openServiceBodyCallHandling(1005);\"]")
            .click()
            .wait(1000)
            .get('#serviceBodyCallHandling_1005 > .modal-dialog > .modal-content > .modal-body > #serviceBodyCallHandlingForm > #volunteer_routing')
            .select('Volunteers')
            .wait(500)
            .get("#serviceBodyCallHandling_1005 > .modal-dialog > .modal-content > .modal-footer > .btn-primary")
            .click()
            .wait(1000)

        cy
            .login()
            .get('.navbar-nav')
            .contains('Volunteers')
            .click()

        cy
            .get("#add-volunteer").click()

        cy
            .get('#volunteerCard_1 > #volunteersForm > .card-header > .form-group > .volunteer-name-text > #volunteer_name')
            .invoke('val', 'danny g')
            .get('#volunteerCard_1 > #volunteersForm > .card-header > .form-group > .expand-button > .btn')
            .click()
            .wait(500)
            .get('#volunteerCard_1 > #volunteersForm > .card-body > :nth-child(1) > #volunteer_phone_number')
            .invoke('val', '9735558811')
            .get('#volunteerCard_1 > #volunteersForm > .card-footer > #volunteerCardFooter > .form-check > #volunteer_enabled')
            .click()
            .get('#save-volunteers')
            .click()

        cy.wait(2000)
    });

    it('Check Volunteer', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Volunteers')
            .click()

        cy
            .get('#volunteerCard_1 > #volunteersForm > .card-header > .form-group > .expand-button > .btn')
            .click()
            .wait(500)
            .get('#volunteerCard_1 > #volunteersForm > .card-header > .form-group > .volunteer-name-text > #volunteer_name')
            .should('have.value', 'danny g')

        cy.wait(2000)
    });

    it('Update Volunteer', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Volunteers')
            .click()

        cy
            .get('#volunteerCard_1 > #volunteersForm > .card-header > .form-group > .volunteer-name-text > #volunteer_name')
            .invoke('val', 'danny g2')
            .get('#volunteerCard_1 > #volunteersForm > .card-header > .form-group > .expand-button > .btn')
            .click()
            .get('#save-volunteers')
            .click()

        cy.wait(2000)

        cy
            .login()
            .get('.navbar-nav')
            .contains('Volunteers')
            .click()

        cy
            .get('#volunteerCard_1 > #volunteersForm > .card-header > .form-group > .expand-button > .btn')
            .click()
            .wait(500)
            .get('#volunteerCard_1 > #volunteersForm > .card-header > .form-group > .volunteer-name-text > #volunteer_name')
            .should('have.value', 'danny g2')

        cy.wait(2000)
    });
});
