describe('Login', () => {

    beforeEach(() => {

    });

    it('Login with creds', () => {
        cy
            .login()
            .get('.home-title').contains("Welcome, gnyr_admin...");
    })
})
