describe('Menubar', () => {

    beforeEach(() => {

    });

    it('Click Reports', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Reports')
            .click()
            .get('#service_body_id option:selected')
            .should('have.text', '-= Select A Service Body =-');
    })

    it('Click Service Bodies', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Service Bodies')
            .click()
            .get('#service-bodies-table tr:contains(\'Brooklyn\')')
            .should('contain', 'Brooklyn Area Service (1006)');
    })

    it('Click Schedules', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Schedules')
            .click()
            .get('#service_body_id option:selected')
            .should('have.text', '-= Select A Service Body =-');
    })

    it('Click Settings', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Settings')
            .click()
            .get('#settingsTable')
            .should('contain', 'https://latest.aws.bmlt.app/main_server');
    })

    it('Click Volunteers', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Volunteer')
            .click()
            .get('#service_body_id option:selected')
            .should('have.text', '-= Select A Service Body =-');
    })

    it('Click Groups', () => {
        cy
            .login()
            .get('.navbar-nav')
            .contains('Groups')
            .click()
            .get('#service_body_id option:selected')
            .should('have.text', '-= Select A Service Body =-');
    })
})
