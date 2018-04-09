import React, { Component } from 'react';

class Login extends Component {
    render() {
        return (
            <div className="Login">
                Username: <input />
                Password: <input />
                <input type="button" value="Login" />
            </div>
        );
    }
}

export default Login;