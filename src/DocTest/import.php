<?php

/**
 * Create special global constants for use in want strings.
 */
define('DOCTEST_BLANKLINE_MARKER', '<BLANKLINE>');
define('DOCTEST_ELLIPSIS_MARKER', '...');

/**
 * Define a path constant for includes.
 */
define('DOCTEST_PATH', dirname(__FILE__) . '/');

/**
 * Import the DocTest_Failure exception.
 */
require_once DOCTEST_PATH . 'Failure.php';

/**
 * Import the DocTest_UnexpectedException exception.
 */
require_once DOCTEST_PATH . 'UnexpectedException.php';

/**
 * Import the DocTest_Example source file.
 */
require_once DOCTEST_PATH . 'Example.php';

/**
 * Import the DocTest_Parser class.
 */
require_once DOCTEST_PATH . 'Parser.php';

/**
 * Import the DocTest_Finder class.
 */
require_once DOCTEST_PATH . 'Finder.php';

/**
 * Import the DocTest_OutputChecker class.
 */
require_once DOCTEST_PATH . 'OutputChecker.php';

/**
 * Import the DocTest_TestResults class.
 */
require_once DOCTEST_PATH . 'TestResults.php';

/**
 * Import the DocTest_Runner class.
 */
require_once DOCTEST_PATH . 'Runner.php';

/**
 * Import the DocTest_DebugRunner class.
 */
require_once DOCTEST_PATH . 'DebugRunner.php';

/**
 * Import the main DocTest class.
 */
require_once DOCTEST_PATH . 'DocTest.php';

/**
 * Register option flag constants.
 */
DocTest::registerOptions();