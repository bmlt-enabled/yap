describe('Login', () => {

    it('Login with BMLT creds', () => {
        cy
            .login()
            .get('.home-title').contains("Welcome, gnyr_admin...");
    })

    it('Login with Yap creds', () => {
        cy
            .login('admin', 'admin')
            .get('.home-title').contains("Welcome, admin...");
    })
})
