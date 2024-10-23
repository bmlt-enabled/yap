describe('Volunteers', () => {

    beforeEach(() => {

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
    });

});
