<?php

error_reporting(E_ALL);

require dirname(__FILE__) . '/../src/DocTest/import.php';

/**
 * Filename relative to this module.
 */
DocTest::testFile('tests.txt', true, null, null, null, true);

/**
 * Relative filename with directory.
 */
//DocTest::testFile('subdir/tests.txt');

/**
 * Absolute path.
 */
//DocTest::testFile('/Users/xdissent/Sites/doctest/examples/tests.txt', false);

/**
 * Use a package base.
 */
//DocTest::testFile('tests.txt', true, null, '/Users/xdissent/Sites/doctest/examples');

/**
 * Use a package base and filename with directory.
 */
//DocTest::testFile('examples/tests.txt', true, null, '/Users/xdissent/Sites/doctest');

/**
 * Get an exception.
 */
try {
    DocTest::testFile('tests.txt', false, null, '/Users/xdissent/Sites/doctest/examples');
} catch (UnexpectedValueException $e) {
}
