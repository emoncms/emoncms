<?php
// Run the tests with `docker run -v $(pwd):/app --rm phpunit/phpunit -c phpunit.xml`

// Needed to prevent some files from calling `die` and killing the test suite
define('EMONCMS_EXEC', 1);