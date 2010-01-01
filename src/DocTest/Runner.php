<?php

/**
 * A class used to run DocTest test cases, and accumulate statistics.
 */
class DocTest_Runner
{
    /**
     * The checker that's used to compare expected outputs and actual outputs.
     *
     * @var object
     */
    private $_checker;
    
    /**
     * Flag indicating verbose output is required.
     *
     * @var boolean
     */
    private $_verbose;
    
    /**
     * Options for the runner.
     *
     * @var integer
     *
     * @todo Make this member variable private (probably).
     */
    protected $optionflags;
    
    /**
     * The original options for the runner.
     *
     * @var integer
     *
     * @todo Make this member variable private (probably).
     */
    protected $original_optionflags;
    
    /**
     * The number of tests that have been run.
     *
     * @var integer
     */
    public $tries = 0;
    
    /**
     * The number of tests that have failed.
     *
     * @var integer
     */
    public $failures = 0;
    
    /**
     * The test being run.
     *
     * @var object
     */
    public $test;
    
    /**
     * An associate array mapping tests to number of failures and tries.
     *
     * @var array
     */
    private $_name2ft;
    
    /**
     * A text divider.
     *
     * <note>This member variable is called "DIVIDER" in Python.</note>
     *
     * @var string
     */
    private $_divider;
    
    /**
     * Creates a new test runner.
     *
     * @param object  $checker     The object instance for checking test output.
     * @param boolean $verbose     Pass null to enable verbose only if "-v" 
     *                             is in the global script arguments. True 
     *                             or false will enable or disable verbose 
     *                             mode regardless of the script arguments.
     * @param integer $optionflags An or'ed combination of test flags.
     *
     * @return null
     */
    public function __construct($checker=null, $verbose=null, $optionflags=0)
    {
        /**
         * Create a checker if it's not given.
         */
        if (is_null($checker)) {
            $checker = new DocTest_OutputChecker();
        }
        $this->_checker = $checker;

        /**
         * Get verbose command line argument if required.
         */
        if (is_null($verbose)) {
            $verbose = in_array('-v', $_SERVER['argv']);
        }
        $this->_verbose = $verbose;

        /**
         * Save option flags.
         */
        $this->optionflags = $optionflags;
        $this->original_optionflags = $optionflags;

        $this->_name2ft = array();
        
        $this->_divider = str_repeat('*', 70);
    }
    
    /**
     * Runs a test.
     *
     * <note>
     * There is no "$compileflags" parameter as found in Python's doctest
     * implementation. PHP has no PDB or linecache to worry about either.
     * </note>
     */
    public function run($test, $out=null, $clear_globs=true)
    {
        $this->test = &$test;
        
        /**
         * By default, simply output to stdout.
         */
        if (is_null($out)) {
            $out = array(__CLASS__, 'stdout');
        }
        
        $r = $this->_run($test, $out);
        
        /**
         * Clear test globals if necessary.
         */
        if ($clear_globs) {
            $test->globs = array();
        }
        
        return $r;
    }
    
    /**
     * Runs a test (internal).
     *
     * <note>The Python equivalent function is called "__run".</note>
     *
     * @param object $test The test to run.
     * @param mixed  $out  The function to call for output.
     */
    private function _run(&$test, $out)
    {
        $tries = 0;
        $failures = 0;
        $original_optionflags = $this->optionflags;
        
        /**
         * Initialize output states.
         */
        $SUCCESS = 0;
        $FAILURE = 1;
        $BOOM = 2;
        
        $check = array($this->_checker, 'checkOutput');
        
        foreach ($test->examples as $examplenum => $example) {
            /** 
             * If DOCTEST_REPORT_ONLY_FIRST_FAILURE is set, then supress
             * reporting after the first failure.
             */
            if (($this->optionflags & DOCTEST_REPORT_ONLY_FIRST_FAILURE)
                && $failures > 0
            ) {
                $quiet = true;
            } else {
                $quiet = false;
            }
            
            
            /**
             * Merge in the example's options.
             */
            $this->optionflags = $original_optionflags;
            if (count($example->options)) {
                foreach ($example->options as $optionflag => $val) {
                    if ($val) {
                        $this->optionflags = $this->optionflags | $optionflag;
                    } else {
                        $this->optionflags = $this->optionflags & ~$optionflag;
                    }
                }
            }
            
            /**
             * If DOCTEST_SKIP is set, then skip this example.
             */
            if ($this->optionflags & DOCTEST_SKIP) {
                continue;
            }
            
            /**
             * Record that we started this example.
             */
            $tries += 1;
            
            if (!$quiet) {
                $this->reportStart($out, $test, $example);
            }
            
            /**
             * Buffer output for the example run.
             *
             * <caution>
             * Doctest examples should not end an output buffer
             * unless it begins one.
             * </caution>
             */
            ob_start();
            
            $exception = null;
            try {
                $parse_error = $this->evalWithGlobs($example->source, $test->globs);
            } catch (Exception $e) {
                $exception = $e;
            }
            
            /**
             * Get any output from the example run.
             */
            $got = ob_get_clean();
            
            /**
             * Guilty until proven innocent or insane.
             */
            $outcome = $FAILURE;
            
            /**
             * Don't know how to handle parse errors yet...
             */
            if ($parse_error) {
                // Do something smart.
            }
            
            /**
             * If the example executed without raising any exceptions,
             * verify its output.
             */
            if (is_null($exception)) {                
                $was_successful = call_user_func(
                    $check,
                    $example->want,
                    $got,
                    $this->optionflags
                );
                
                if ($was_successful) {
                    $outcome = $SUCCESS;
                }
            } else {
                $exc_msg = $exception->getMessage() . "\n";
                
                if (!$quiet) {
                    $got += (string)$exception;
                }
                 
                if (is_null($example->exc_msg)) {
                    $outcome = $BOOM;
                } else {
                    /**
                     * We expected an exception: see whether it matches.
                     */
                    $was_successful = call_user_func(
                        $check,
                        $example->exc_msg,
                        $exc_msg,
                        $this->optionflags
                    );
                    if ($was_successful) {
                        $outcome = $SUCCESS;
                    } else {
                    
                        /**
                         * Another chance if they didn't care about the detail.
                         *
                         * <caution>
                         * Not doing this until we separate type and msg.
                         * </caution>
                         *
                         * if self.optionflags & IGNORE_EXCEPTION_DETAIL:
                         *     m1 = re.match(r'[^:]*:', example.exc_msg)
                         *     m2 = re.match(r'[^:]*:', exc_msg)
                         *     if m1 and m2 and check(m1.group(0), m2.group(0),
                         *                    self.optionflags):
                         *         outcome = SUCCESS
                         */
                    }
                }
            }
            
            /**
             * Report the outcome.
             */
            if ($outcome === $SUCCESS) {
                if (!$quiet) {
                    $this->reportSuccess($out, $test, $example, $got);
                }
            } elseif ($outcome === $FAILURE) {
                if (!$quiet) {
                    $this->reportFailure($out, $test, $example, $got);
                }
                $failures += 1;
            } elseif ($outcome === $BOOM) {
                if (!$quiet) {
                    $this->reportUnexpectedException(
                        $out, 
                        $test, 
                        $example,
                        $exception
                    );
                }
                $failures += 1;
            } else {
                throw new Exception('Unknown test outcome.');
            }
        }
        
        $this->optionflags = $original_optionflags;
        
        /**
         * Record and return the number of failures and tries.
         */
        $this->_recordOutcome($test, $failures, $tries);

        return new DocTest_TestResults($failures, $tries);
    }
    
    private function _recordOutcome($test, $f, $t)
    {
        if (array_key_exists($test->name, $this->_name2ft)) {
            $f2 = $this->_name2ft[$test->name][0];
            $t2 = $this->_name2ft[$test->name][1];
        } else {
            $f2 = 0;
            $t2 = 0;
        }
        $this->_name2ft[$test->name] = array($f + $f2, $t + $t2);
        $this->failures += $f;
        $this->tries += $t;
    }
    
    public function summarize($verbose=null)
    {
        if (is_null($verbose)) {
            $verbose = $this->_verbose;
        }
        
        $notests = array();
        $passed = array();
        $failed = array();
        $totalt = 0;
        $totalf = 0;
        
        foreach ($this->_name2ft as $name => $ft) {
            $f = $ft[0];
            $t = $ft[1];
            $totalt += $t;
            $totalf += $f;
            if ($t == 0) {
                $notests[] = $name;
            } elseif ($f == 0) {
                $passed[] = array($name, $t);
            } else {
                $failed[] = array($name, $this->_name2ft[$name]);
            }
        }
        
        if ($verbose) {
            if (count($notests)) {
                echo count($notests) . " items had no tests:\n";
                sort($notests);
                foreach ($notests as $thing) {
                    echo '   ' . $thing . "\n";
                }
            }
            if (count($passed)) {
                echo count($passed) . " items passed all tests:\n";
                sort($passed);
                foreach ($passed as $thing) {
                    $name = $thing[0];
                    $count = $thing[1];
                    echo sprintf(" %3d tests in %s\n", $count, $name);
                }
            }
        }
        
        if ($failed) {
            echo $this->_divider . "\n";
            echo count($failed) . " items had failures:\n";
            sort($failed);
            foreach ($failed as $thing) {
                $name = $thing[0];
                $f = $thing[1][0];
                $t = $thing[1][1];
                echo sprintf(" %3d of %3d in %s\n", $f, $t, $name);
            }
        }
        
        if ($verbose) {
            echo $totalt . ' tests in ' . count($this->_name2ft) . " items.\n";
            echo $totalt - $totalf . ' passed and ' . $totalf . " failed.\n";
        }
        
        if ($totalf) {
            echo '***Test Failed*** ' . $totalf . " failures.\n";
        } elseif ($verbose) {
            echo "Test passed.\n";
        }
        
        return new DocTest_TestResults($totalf, $totalt);
    }
    
    /**
     * Executes the given source code in a clean scope with globals set.
     *
     * <caution>
     * The array of globals is passed by reference and will be updated
     * to include any new global variables that are set when executing the 
     * source.
     * </caution>
     *
     * @param string $source The source code to run.
     * @param array  $globs  The globals to set before execution.
     *
     * @return null
     */
    protected function evalWithGlobs($source, &$globs)
    {
        /**
         * Extract all global variables into the current scope.
         */
        extract($globs);
        
        /**
         * Run the source code in the current scope.
         */
        $ret = eval($source);
        
        /**
         * Get all variables in the current scope including those defined by
         * the eval'ed code.
         */
        $new_glob_vars = get_defined_vars();
        
        /**
         * Unset special variables that should not be added to globals.
         */
        unset($new_glob_vars['source']);
        unset($new_glob_vars['globs']);
        unset($new_glob_vars['ret']);
        
        /**
         * Save the globals into globs so future examples may use them.
         */
        foreach ($new_glob_vars as $name => $val) {
            $globs[$name] = $val;
        }
    }
    
    /**
     * Reports that the test runner is about to process the given example.
     *
     * This method only displays a message if in verbose mode.
     *
     * @param mixed  $out     The callback used to display output.
     * @param object $test    The doctest that is running.
     * @param object $example The current example in the test.
     *
     * @return null
     */
    protected function reportStart($out, $test, $example)
    {
        if ($this->_verbose) {
            if ($example->want !== '') {
                $output = "Trying:\n" . DocTest::indent($example->source) .
                    "Expecting:\n" . DocTest::indent($example->want);
            } else {
                $output = "Trying:\n" . DocTest::indent($example->source) .
                    "Expecting nothing\n";
            }
            call_user_func($out, $output);
        }
    }
    
    /**
     * Report that the given example ran successfully.
     *
     * @param mixed  $out     The callback used to display output.
     * @param object $test    The doctest that is running.
     * @param object $example The current example in the test.
     * @param string $got     The output received from the test.
     *
     * @return null
     */
    protected function reportSuccess($out, $test, $example, $got)
    {
        if ($this->_verbose) {
            call_user_func($out, "ok\n");
        }
    }

    /**
     * Report that the given example ran unsuccessfully.
     *
     * @param mixed  $out     The callback used to display output.
     * @param object $test    The doctest that is running.
     * @param object $example The current example in the test.
     * @param string $got     The output received from the test.
     *
     * @return null
     */
    protected function reportFailure($out, $test, $example, $got)
    {
        $output = $this->_failureHeader($test, $example);
        $output .= $this->_checker->outputDifference(
            $example,
            $got,
            $this->optionflags
        );
        call_user_func($out, $output);
    }
    
    /**
     * Report that an exception was thrown that we were not expecting.
     *
     * @param mixed  $out       The callback used to display output.
     * @param object $test      The doctest that is running.
     * @param object $example   The current example in the test.
     * @param object $exception The exception thrown by the test.
     *
     * @return null
     */
    protected function reportUnexpectedException($out, $test, $example, $exception)
    {
        $output = $this->_failureHeader($test, $example);
        $output .= "Exception raised:\n";
        $output .= DocTest::indent($this->_exceptionTraceback($exception));
        call_user_func($out, $output);
    }
    
    /**
     * Returns a highly visible string used to indicate that an error occurred.
     *
     * @param object $test      The doctest that is running.
     * @param object $example   The current example in the test.
     *
     * @return string
     */
    private function _failureHeader($test, $example)
    {
        $out = array($this->_divider);
        
        if ($test->filename !== '') {
            if (!is_null($test->lineno) && !is_null($example->lineno)) {
                $lineno = $test->lineno + $example->lineno + 1;
            } else {
                $lineno = '?';
            }
            $out[] = sprintf(
                'File "%s", line %s, in %s',
                $test->filename,
                $lineno,
                $test->name
            );
        } else {
            $out[] = sprintf(
                'Line %s, in %s',
                $example->lineno + 1,
                $test->name
            );
        }
        
        $out[] = 'Failed example:';
        $source = $example->source;
        $out[] = DocTest::indent($source);
        
        return implode("\n", $out);
    }
    
    /**
     * Returns a string representation of an exception.
     *
     * <note>
     * This method is the global "_exception_traceback()" function in Python
     * </note>
     *
     * @param object $exception The exception.
     *
     * @return string
     */
    private function _exceptionTraceback($exception)
    {
        return (string)$exception . "\n";
    }
    
    /**
     * Outputs a string to stdout.
     *
     * <note>Python's doctest uses the "sys.stdout.write" method instead.</note>
     *
     * @param string $out The string to output.
     *
     * return null
     */
    public static function stdout($out)
    {
        echo $out;
    }
}