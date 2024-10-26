describe('Service Body Call Handling', () => {

    beforeEach(() => {

    });

    it('Save service body', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .get('#service-bodies-table tr:contains(\'Brooklyn\')')
            .should('contain', 'Brooklyn Area Service (1006)')
            .get("[onclick=\"openServiceBodyCallHandling(1005);\"]")
            .click()
            .wait(5000)
            .get("#serviceBodyCallHandling_1005 > .modal-dialog > .modal-content > .modal-body > #serviceBodyCallHandlingForm > :nth-child(4) > #forced_caller_id")
            .focus()
            .clear()
            .invoke("val", "123")
            .wait(500)
            .get("#serviceBodyCallHandling_1005 > .modal-dialog > .modal-content > .modal-footer > .btn-primary")
            .click()

        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .get("[onclick=\"openServiceBodyCallHandling(1005);\"]")
            .click()
            .wait(5000)
            .get("#serviceBodyCallHandling_1005 > .modal-dialog > .modal-content > .modal-body > #serviceBodyCallHandlingForm > :nth-child(4) > #forced_caller_id")
            .focus()
            .should('have.value', '123');
    })


    it('Update service body', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .get('#service-bodies-table tr:contains(\'Brooklyn\')')
            .should('contain', 'Brooklyn Area Service (1006)')
            .get("[onclick=\"openServiceBodyCallHandling(1005);\"]")
            .click()
            .wait(5000)
            .get("#serviceBodyCallHandling_1005 > .modal-dialog > .modal-content > .modal-body > #serviceBodyCallHandlingForm > :nth-child(4) > #forced_caller_id")
            .focus()
            .clear()
            .invoke("val", "123abc")
            .wait(500)
            .get("#serviceBodyCallHandling_1005 > .modal-dialog > .modal-content > .modal-footer > .btn-primary")
            .click()

        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .get("[onclick=\"openServiceBodyCallHandling(1005);\"]")
            .click()
            .wait(5000)
            .get("#serviceBodyCallHandling_1005 > .modal-dialog > .modal-content > .modal-body > #serviceBodyCallHandlingForm > :nth-child(4) > #forced_caller_id")
            .focus()
            .should('have.value', '123abc');
    })
})
