let helper = require('../Lib/emoncms_tests_helper.js');
let login_details = helper.getLoginDetails();

describe('In a new emonCMS installation ', function () {
    it('a new user can register as an Administrator', function () {
        helper.logIfDebug('\nSpecification: In a new emonCMS installation a new user can register as an Administrator\n------------------');
        helper.registerUser(login_details.login_url, login_details.username1, login_details.password1, 'email@email.com');
        let page_url = browser.getUrl();
        expect(page_url).not.toBe(login_details.login_url);
        expect(browser.isExisting('a*=Logout')).toBe(true);
    });
    it(' the administrator can update the database', function () {
        helper.logIfDebug('\nSpecification: In a new emonCMS installation the administrator can update the database\n------------------');
        browser.url(login_details.login_url + "/admin/db?apply=true");
        expect(browser.isVisible('.alert-success')).toBe(true);
    });
    it(' the administrator can logout', function () {
        helper.logIfDebug('\nSpecification: In a new emonCMS installation the administrator can logout\n------------------');
        helper.logout();
        expect(browser.isExisting('[name=username]')).toBe(true);
    });
    it(', if the Register option is enabled, a new user can be created without getting any Jasmine errors', function () {
        helper.logIfDebug('\nSpecification: In a new emonCMS installation, if the Register option is enabled, a new user can be created without getting any Jasmine errors\n------------------');
        if (browser.isVisible('a=register')) {
            helper.registerUser(login_details.login_url, login_details.username2, login_details.password2, 'email@email.com');
            helper.logout();
        }
    });
});