describe('Service Body Configuration', () => {

    before(() => {
        cy.resetDatabase();
    });

    it('Save configuration', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .get('#service-bodies-table tr:contains(\'Brooklyn\')')
            .should('contain', 'Brooklyn Area Service (1006)')
            .get("[onclick=\"openServiceBodyConfigure(1005);\"]")
            .click()
            .wait(1000)
            .get('#serviceBodyConfiguration_1005 > .modal-dialog > .modal-content > .modal-body > #serviceBodyConfigurationForm > #serviceBodyConfigurationFields')
            .select("title")
            .get('#serviceBodyConfiguration_1005 > .modal-dialog > .modal-content > .modal-body > #serviceBodyConfigurationForm > .btn')
            .click()
            .get('#title')
            .invoke("val", "Welcome to the Helpline Dude!")
            .get('#serviceBodyConfiguration_1005 > .modal-dialog > .modal-content > .modal-footer > .btn-primary')
            .click()
            .wait(1000)

        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .get('#service-bodies-table tr:contains(\'Brooklyn\')')
            .should('contain', 'Brooklyn Area Service (1006)')
            .get("[onclick=\"openServiceBodyConfigure(1005);\"]")
            .click()
            .wait(1000)
            .get('#title')
            .should('have.value', 'Welcome to the Helpline Dude!')
    })

    it('Save configuration', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .get('#service-bodies-table tr:contains(\'Brooklyn\')')
            .should('contain', 'Brooklyn Area Service (1006)')
            .get("[onclick=\"openServiceBodyConfigure(1005);\"]")
            .click()
            .wait(1000)
            .get('#title')
            .invoke("val", "Welcome to the Helpline Bro!")
            .get('#serviceBodyConfiguration_1005 > .modal-dialog > .modal-content > .modal-footer > .btn-primary')
            .click()
            .wait(1000)

        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .get('#service-bodies-table tr:contains(\'Brooklyn\')')
            .should('contain', 'Brooklyn Area Service (1006)')
            .get("[onclick=\"openServiceBodyConfigure(1005);\"]")
            .click()
            .wait(1000)
            .get('#title')
            .should('have.value', 'Welcome to the Helpline Bro!')
    })
})
