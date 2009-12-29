<?php

require dirname(__FILE__) . '/Parser.php';

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
    
    public static function testFile($file)
    {
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

$p = new DocTest_Parser;
$o = $p->parse('This is a test:
php > PHP CODE HERE
expected output

Well that was cool.

Does this work too?
php > Some more
want me
php > No wantin!
php > And again. # doctest: +testoption
php { multi
php { line code.
expect THIS why dontcha?
php > throw new Exception("this\nis a\n multiline");
PHP Fatal error:  Uncaught exception \'Exception\' with message \'this
is a
 multiline\' in php shell code:1
Stack trace:
#0 {main}
  thrown in php shell code on line 1

And this is the end.');
var_dump($o);