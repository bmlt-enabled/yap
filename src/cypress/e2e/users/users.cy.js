describe('Users', () => {

    before(() => {
        cy.resetDatabase();
    });

    it('Click Users', () => {
        cy
            .login('admin', 'admin')
            .get('.navbar-nav')
            .contains('Users')
            .click()
            .get('.container > :nth-child(1) > .btn')
            .should('have.text', 'Add User');
    })

    it('Add User and Then Login As Them And Then Delete', () => {
        let username = 'testtest';
        let password = 'testtest123'
        let name = 'cypressTestGuy'
        let serviceBodyId = '1048'

        cy
            .login('admin', 'admin')
            .get('.navbar-nav')
            .contains('Users')
            .click()
            .get('.container > :nth-child(1) > .btn')
            .click()
            .get('#username').click().clear().type(username)
            .get('#name').click().clear().type(name)
            .get('#password').click().clear().type(password)
            .get('#service_bodies').select(serviceBodyId)
            .get("#usersSaveButton").click()
            .get('tbody > :nth-child(2) > :nth-child(2)').contains(username)
            .get('tbody > :nth-child(2) > :nth-child(3)').contains(name)
            .get('tbody > :nth-child(2) > :nth-child(4)').contains(serviceBodyId)
            .get("#log-out-button").click()

        cy.login('testtest', 'testtest123')
            .get('.home-title').contains("Welcome, cypressTestGuy...")
            .get("#log-out-button").click();

        cy.login('admin', 'admin')
            .get('.navbar-nav')
            .contains('Users')
            .click()
            .get(':nth-child(1) > .btn-danger')
            .click()
            .get('tbody > :nth-child(2) > :nth-child(2)').should('not.exist')
    })

    it('Add User and Then Edit', () => {
        let username = 'testtest';
        let password = 'testtest123'
        let name = 'cypress test guy'
        let serviceBodyId = '1048'
        let extraServiceBodyId = '1049'

        cy.clock()
        cy
            .login('admin', 'admin')
            .get('.navbar-nav')
            .contains('Users')
            .click()
            .get('.container > :nth-child(1) > .btn')
            .click()
            .tick(1000)
            .get('#username').click().clear().type(username)
            .get('#name').click().clear().type(name)
            .get('#password').click().clear().type(password)
            .get('#service_bodies').select(serviceBodyId)
            .get("#usersSaveButton").click()
            .get('tbody > :nth-child(2) > :nth-child(2)').contains(username)
            .get('tbody > :nth-child(2) > :nth-child(3)').contains(name)
            .get('tbody > :nth-child(2) > :nth-child(4)').contains(serviceBodyId)

            // Edit User
            .get(':nth-child(2) > :nth-child(1) > .btn-warning')
            .click()
            .tick(1000)
            .get('#name').click().clear().type(`${name}_v2`)
            .get('#service_bodies').select([serviceBodyId, extraServiceBodyId])
            .get("#usersSaveButton").click()
            .get('tbody > :nth-child(2) > :nth-child(3)').contains(`${name}_v2`)
            .get('tbody > :nth-child(2) > :nth-child(4)').contains(`${serviceBodyId},${extraServiceBodyId}`)

            // Delete User
            .get(':nth-child(1) > .btn-danger')
            .click()
            .get('tbody > :nth-child(2) > :nth-child(2)').should('not.exist')
    })
})
