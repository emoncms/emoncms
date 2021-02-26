let helper = require('../Lib/emoncms_tests_helper.js');
let login_details = helper.getLoginDetails();

describe('In an emonCMS installation ', function () {
    it('an exisiting user can login', function () { // Check rade.md for users
        helper.logIfDebug('\nSpecification: In an emonCMS installation an exisiting user can login\n------------------');
        helper.login(login_details.login_url, login_details.username1, login_details.password1, 'email@email.com');
        let page_url = browser.getUrl();
        expect(page_url).not.toBe(login_details.login_url);
        expect(browser.isExisting('a*=Logout')).toBe(true);
    });
    it('a user can logout', function () {
        helper.logIfDebug('\nSpecification: In an emonCMS installation a user can logout\n------------------');
        helper.logout();
        expect(browser.isExisting('[name=username]')).toBe(true);
        let page_url = browser.getUrl();
        expect(page_url).toBe(login_details.login_url);
    });
});