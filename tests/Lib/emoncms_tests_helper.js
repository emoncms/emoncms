let debug = process.env.DEBUG;
let travis = process.env.TRAVIS;

module.exports = {
    getLoginDetails: function () { // Info about users can be found in the readme.md file
        if (travis)
            return require('./travis_login_details');
        else
            return require('./login_details_for_an_existing_installation');
    },
    registerUser: function (url, username, password, email) {
        this.logIfDebug('Registering user');
        browser.url(url)
                .click('a=register')
                .setValue('[name=email]', email)
                .setValue('[name=username]', username)
                .setValue('[name=password]', password)
                .setValue("#confirm-password", password)
                .click('#register');
    },
    login: function (url, username, password) {
        this.logIfDebug('Logging: ' + url + ' - ' + username + ' - ' + password);
        browser.url(url)
                .setValue('[name=username]', username)
                .setValue('[name=password]', password)
                .click('#login');
    },
    goToMyAccountPage: function (name, description) {
        this.logIfDebug('Going to My Account page');
        browser.click('a=Account');
    },
    logout: function () {
        this.logIfDebug('Logging out');
        browser.click('#user-dropdown')
        browser.click('#logout-link');
    }
    ,
    logIfDebug(message) {
        if (debug)
            console.log(message);
    },
    clearAjaxCallsBuffer() {
        try {
            browser.getRequests();
        } catch (e) {
        }
    }
};