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
})
