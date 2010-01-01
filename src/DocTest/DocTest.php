<?php

class DocTest
{
    /**
     * A static array of option flags values keyed by option name.
     *
     * @var array
     */
    public static $option_flags_by_name;
    
    public function __construct($examples, $globs, $name, $filename, 
        $lineno, $docstring
    ) {
        $this->examples = $examples;
        $this->docstring = $docstring;
        $this->globs = $globs;
        $this->name = $name;
        $this->filename = $filename;
        $this->lineno = $lineno;
    }
    
    /**
     * Returns a string representation of a DocTest.
     *
     * The string returned will contain the number of examples as well
     * as the filename and line number of the DocTest.
     *
     * @return string
     */
    public function __toString()
    {
        if (!count($this->examples)) {
            $examples = 'no examples';
        } elseif (count($this->examples == 1)) {
            $examples = '1 example';
        } else {
            $examples = sprintf('%d examples', count($this->examples));
        }
        return sprintf(
            '<DocTest %s from %s:%s (%s)>',
            $this->name,
            $this->filename,
            $this->lineno,
            $examples
        );
    }
    
    public static function testObj($obj, $globs=null, $verbose=false, 
        $name=null, $optionflags=0
    ){
        /**
         * Find, parse, and run all tests in the given object.
         */
        $finder = new DocTest_Finder($verbose, null, false);
        $runner = new DocTest_Runner(null, $verbose, $optionflags);
        $tests = $finder->find($obj, $name, $globs);
        foreach ($tests as $test) {
            $runner->run($test);
        }
    }
     
    /* Tests examples in the given file.
     *
     * The array returned will contain the number of test failures encountered
     * at offset 0 and the number of tests found at offset 1.
     *
     * @param string  $filename        The name of the file to test.
     * @param boolean $module_relative Whether to use a module relative path. By
     *                                 default, the calling module's path will 
     *                                 be used, or the package parameter if set.
     * @param string  $name            The name of the test. The basename of the
     *                                 filename will be used by default.
     * @param string  $package         A package whose path should be used for
     *                                 the relative module base path.
     * @param array   $globs           The global variables needed by the tests.
     * @param boolean $verbose         Whether or not to trigger verbose mode.
     * @param boolean $report          Whether or not to output a full report.
     * @param integer $optionflags     The or'ed combination of flags to use.
     * @param array   $extraglobs      Extra globals to use for the tests.
     * @param boolean $raise_on_errors Whether to raise an exception on error.
     * @param object  $parser          A parser to use for the tests.
     * @param string  $encoding        The encoding to use to convert the file
     *                                 to unicode.
     *
     * @return array
     */
    public static function testFile($filename, $module_relative=true,
        $name=null, $package=null, $globs=null, $verbose=null, $report=true,
        $optionflags=0, $extraglobs=null, $raise_on_error=false, 
        $parser=null, $encoding=null)
    {
        /**
         * Check for obvious path error.
         */
        if (!is_null($package) && !$module_relative) {
            throw new UnexpectedValueException(
                'Package may only be specified for module-relative paths.'
            );
        }
        
        /**
         * Initialize parser.
         */
        if (is_null($parser)) {
            $parser = new DocTest_Parser();
        }
        
        /**
         * Relativize the path
         */
        list($text, $filename) = self::_loadTestFile($filename, $package, $module_relative);
        
        /**
         * If no name was given, then use the file's name.
         */
        if (is_null($name)) {
            $name = basename($filename);
        }
        
        /**
         * Assemble the globals.
         */
        if (is_null($globs)) {
            $globs = array();
        }
        if (!is_null($extraglobs)) {
            $globs = array_merge($globs, $extraglobs);
        }
        
        if ($raise_on_error) {
            $runner = new DocTest_DebugRunner(null, $verbose, $optionflags);
        } else {
            $runner = new DocTest_Runner(null, $verbose, $optionflags);
        }
        
        /**
         * Convert encoding
         *
         * @todo Make this work.
         */
        if (!is_null($encoding) && function_exists('mb_convert_encoding')) {
            $text = mb_convert_encoding($text, $encoding);
        }
        
        /**
         * Read the file, convert it to a test, and run it.
         */
        $test = $parser->getDocTest($text, $globs, $name, $filename, 0);
        $runner->run($test);
        
        if ($report) {
            $runner->summarize();
        }
        
        return new DocTest_TestResults($runner->failures, $runner->tries);
    }
    
    private static function _loadTestFile($filename, $package, $module_relative)
    {
        /**
         * Determine the absolute file path.
         */
        if ($module_relative) {
        
            /**
             * Use the calling module's dir as a base path if no package is set.
             */
            if (is_null($package)) {
                /**
                 * Get a backtrace.
                 */
                $bt = debug_backtrace();
                
                /**
                 * Find the first file in the trace that's not this one.
                 */
                foreach ($bt as $trace) {
                    if ($trace['file'] !== __FILE__) {
                        /**
                         * Found calling file. Get the dir and kill the loop.
                         */
                        $package = dirname($trace['file']);
                        break;
                    }
                }
            }
            
            /**
             * Ensure package ends in a slash.
             */
            if (substr($package, -1) !== '/') {
                $package .= '/';
            }
            
            /**
             * Add the filename to the package dir.
             */
            $filename = $package . $filename;
        }
        
        /**
         * Return the file as a string along with the resolved filename.
         */
        return array(file_get_contents($filename), $filename);
    }
    
    /**
     * Registers available options.
     */
    public static function registerOptions()
    {
        /**
         * Protect against multiple calls.
         */
        if (!is_null(self::$option_flags_by_name)) {
            return;
        }

        /**
         * Initialize options flag array.
         */
        self::$option_flags_by_name = array();
        
        /**
         * The possible base options.
         */
        $options = array(
            'DONT_ACCEPT_TRUE_FOR_1',
            'DONT_ACCEPT_BLANKLINE',
            'NORMALIZE_WHITESPACE',
            'ELLIPSIS',
            'SKIP',
            'IGNORE_EXCEPTION_DETAIL',
            'REPORT_UDIFF',
            'REPORT_NDIFF',
            'REPORT_CDIFF',
            'REPORT_ONLY_FIRST_FAILURE'
        );
        
        /**
         * Define a global namespaced constant for each option and add it
         * to the static named options array.
         */
        foreach ($options as $i => $option) {
            $namespaced = 'DOCTEST_' . $option;
            define($namespaced, 1 << $i);
            self::$option_flags_by_name[$option] = constant($namespaced);
        }
        
        /**
         * Create comparison flags combination.
         */
        $comp_flags = DOCTEST_DONT_ACCEPT_TRUE_FOR_1 |
            DOCTEST_DONT_ACCEPT_BLANKLINE |
            DOCTEST_NORMALIZE_WHITESPACE |
            DOCTEST_ELLIPSIS |
            DOCTEST_SKIP |
            DOCTEST_IGNORE_EXCEPTION_DETAIL;
        define('DOCTEST_COMPARISON_FLAGS', $comp_flags);
        
        /**
         * Create reporting flags combination.
         */
        $rep_flags = DOCTEST_REPORT_UDIFF |
            DOCTEST_REPORT_CDIFF |
            DOCTEST_REPORT_NDIFF |
            DOCTEST_REPORT_ONLY_FIRST_FAILURE;
        define('DOCTEST_REPORTING_FLAGS', $rep_flags);
    }
    
    /**
     * Returns a string with non-empty lines prefixed with a number of spaces.
     *
     * <note>This method is the global "_indent()" function in Python.</note>
     *
     * @param string  $s      The string to indent.
     * @param integer $indent The number of spaces to indent.
     *
     * @return string
     */
    public function indent($s, $indent=4) {
        return preg_replace('/(?m)^(?!$)/', str_repeat(' ', $indent), $s);
    }
}