describe('Menubar', () => {

    before(() => {
        cy.resetDatabase();
    });

    it('Click Reports', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Reports')
            .click()
            .wait(1000)
            .get('#service_body_id option:selected')
            .should('have.text', '-= Select A Service Body =-');
    })

    it('Click Service Bodies', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .wait(1000)
            .get('#service-bodies-table tr:contains(\'Brooklyn\')')
            .should('contain', 'Brooklyn Area Service (1006)');
    })

    it('Click Schedules', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Schedules')
            .click()
            .wait(1000)
            .get('#service_body_id option:selected')
            .should('have.text', 'Bronx Area Service (1005) / Greater New York Region (1)');
    })

    it('Click Settings', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Settings')
            .click()
            .wait(1000)
            .get('#settingsTable')
            .should('contain', 'https://latest.aws.bmlt.app/main_server');
    })

    it('Click Volunteers', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Volunteer')
            .click()
            .wait(1000)
            .get('#service_body_id option:selected')
            .should('have.text', 'Bronx Area Service (1005) / Greater New York Region (1)');
    })

    it('Click Groups', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Groups')
            .click()
            .wait(1000)
            .get('#service_body_id option:selected')
            .should('have.text', '-= Select A Service Body =-');
    })
})
