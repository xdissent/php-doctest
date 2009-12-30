<?php

/**
 * Import the DocTest_Parser class.
 */
require dirname(__FILE__) . '/Parser.php';

/**
 * Import the DocTest_Finder class.
 */
require dirname(__FILE__) . '/Finder.php';

class DocTest
{
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
     * @param array   $globs           
     * @param boolean $verbose
     * @param boolean $report
     * @param integer $optionflags     The or'ed combination of flags to use.
     * @param array   $extraglobs
     * @param boolean $raise_on_errors
     * @param object  $parser
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
        if (is_null($parser)) {
            $parser = new DocTest_Parser();
        }
        
        
        $bt = debug_backtrace();
        
        $mod_file = $bt[0]['file'];
        
        return self::testModCLI($file, $verbose);
    }
    
    public static function testMod()
    {
        $bt = debug_backtrace();
        
        /**
         * Find the file to test.
         */
        $mod_file = $bt[0]['file'];
        
        /**
         * Determine whether the test was requested through a web server.
         */
        if (!count($_SERVER['argv'])) {
            /**
             * Run the HTML tests.
             */
            return self::testModHTML($mod_file);
        }
                
        /**
         * Determine whether to output verbose CLI tests.
         */
        if (in_array('-v', $_SERVER['argv'])) {
            return self::testModCLI($mod_file, true);
        }
        
        /**
         * Run the non-verbose test CLI tests.
         */
        return self::testModCLI($mod_file);
    }
    
    protected static function testModHTML($file)
    {
        echo 'Testing HTML File: ' . $file;
    }
    
    protected static function testModCLI($file, $verbose=false)
    {
        
    }
}