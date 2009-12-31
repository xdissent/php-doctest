<?php

require dirname(__FILE__) . '/OutputChecker.php';

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
     */
    public $test;
    
    private $_name2ft;
    
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
     * @return array
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
         * Insert output buffer.
         */
        ob_start();
        
        /**
         * By default, simply output to stdout.
         */
        if (is_null($out)) {
            $out = array(self, 'stdout');
        }
        
        $ret = $this->_run($test, $out);
        
        /**
         * Clear test globals if necessary.
         */
        if ($clear_globs) {
            $test->globs = array();
        }
        
        /**
         * Remove output buffer.
         */
        ob_end_flush();
        //ob_end_clean();
        
        return $ret;
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
            
            try {
                $parse_error = $this->evalWithGlobs($example->source, $test->globs);
                $exception = null;
            } catch (Exception $exception) {
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
            }
        }
        
        $this->optionflags = $original_optionflags;
        
/*
        # Record and return the number of failures and tries.
        self.__record_outcome(test, failures, tries)
        return TestResults(failures, tries)
*/
    }
    
    protected function evalWithGlobs($source, &$globs)
    {
        extract($globs);
        $ret = eval($source);
        
        /**
         * Save the globals into globs so future examples may use them.
         */
        $new_glob_vars = get_defined_vars();
        
        unset($new_glob_vars['source']);
        unset($new_glob_vars['globs']);
        unset($new_glob_vars['ret']);
        
        foreach ($new_glob_vars as $name => $val) {
            $globs[$name] = $val;
        }
    }
    
    protected function reportStart($out, $test, $example)
    {
        call_user_func($out, 'starting report');
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