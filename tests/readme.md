# emonCMS tests

We run the tests using Jasmine (testing framework for JavaScript), Selenium server (for interactions with browsers) and WebdriverIO (webdriver bindings for JavaScript)
Only the test files are included here. In order to run them locally you need to do the setup below.

## Set up

To install all dependencies
`npm install`

To install selenium stand alone server
`./node_modules/.bin/selenium-standalone install`

To install the webdrivers, download:
 - The [most recent ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver/home)
 - The [most recent GeckoDriver for Firefox] (https://github.com/mozilla/geckodriver/releases)

Include their location in your PATH environment variable (/etc/environment)

## To run the tests
First start the selenium server
`./node_modules/.bin/selenium-standalone start`

Run the all tests in `./specs` except `emonCMS-travis-setup.js`
`./node_modules/.bin/wdio wdio.conf.js`

Run then tests in headless mode:
`HEADLESS=true ./node_modules/.bin/wdio wdio.conf.js`

Run the test with debug info:
`DEBUG=true ./node_modules/.bin/wdio wdio.conf.js`

By default tests are run in Chrome, to use Firefox:
`BROWSER=firefox ./node_modules/.bin/wdio wdio.conf.js`

## Specs
These tests are to be run locally or as part of the Script job in Travis

## emonCMS users for the tests
The Travis build sets up a new emonCMS installation. It creates its own emonCMS users and they are the ones used in the tests during the Script job. They are in `./Lib/travis_login_details.js`. To run emonCMS tests with this users we have added the environmental variable to the wdio command `TRAVIS=true` in the Script job in the Travis build

For running the tests in a existing emonCMS installations (local machine or staging server) the tests users need to exist already because we cannot rely on the availability of the Register option. The test users are in `./Lib/login_details_for_an_existing_installation.js`. This file is .gitignored. The structure of the file is the same than `./Lib/travis_login_details.js`

# Credits
This test suite setup, "emonCMS-travis-setup" and "emonCMS-first-login" specifications have been developed by [Carbon Co-op](https://carbon.coop/)
We hope this will be useful and extended by the community