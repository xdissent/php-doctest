<?php

/**
 * A class that extracts DocTests from an object.
 */
class DocTest_Finder
{
    /**
     * The parser object used by the finder.
     *
     * @var object
     */
    private $_parser;
    
    /**
     * Whether to be verbose when finding tests.
     *
     * @var boolean
     */
    private $_verbose;
    
    /**
     * Whether the finder will search contained or not.
     *
     * @var boolean
     */
    private $_recurse;
    
    /**
     * Whether the finder will include tests for objects with empty doc strings.
     *
     * @var boolean
     */
    private $_exclude_empty;
    
    /**
     * Creates a new doctest finder.
     *
     * @param boolean $verbose       Whether to be verbose when finding tests.
     * @param object  $parser        A parser object.
     * @param boolean $recurse       Whether the finder will search contained 
     *                               objects or not.
     * @param boolean $exclude_empty Whether the finder will include tests for
     *                               objects with empty doc strings.
     *
     * @return null
     */
    public function __construct($verbose=false, $parser=null, $recurse=true, 
        $exclude_empty=true
    ) {
        /**
         * Initialize the parser object if not given.
         */
        if (is_null($parser)) {
            $parser = new DocTest_Parser();
        }
        
        /**
         * Store the finder's data.
         */
        $this->_parser = $parser;
        $this->_verbose = $verbose;
        $this->_recurse = $recurse;
        $this->_exclude_empty = $exclude_empty;
    }
    
    /**
     * Returns an array of DocTests defined by an object's docstring.
     *
     * An object may be an instance of a class, a class name, a function name,
     * or a class or instance method name (in the form accepted by 
     * {@link call_user_func()}).
     *
     * If a filename is given as the object, the file-level docblock will be
     * searched for tests. If a recursive search is requested for a file, all
     * required and included files in the passed file will also be searched,
     * recursively.
     *
     * <caution>File names are not yet accepted!</caution>
     *
     * Contained objects may be searched, depending on the recurse option passed
     * to the finder instance's constructor.
     *
     * <note>
     * Python's doctest defines a "module" parameter to this method which is
     * used to find the requested object. This doesn't make sense in a PHP
     * context since all functions and classes are considered global. It is
     * omitted here.
     * </note>
     *
     * @param mixed  $obj        The object to search for tests.
     * @param string $name       The name to use for the tests.
     * @param array  $globs      The globals to give to the test.
     * @param array  $extraglobs More globals.
     *
     * @return object
     */
    public function find($obj=null, $name=null, $globs=null, $extraglobs=null)
    {
        /**
         * If name was not specified, then extract it from the object.
         */
        if (is_null($obj)) {
            $name = 'All tests';
        } elseif (is_string($obj)) {
            /**
             * Obj is a function or class name.
             */
            $name = $obj;
        } elseif (is_array($obj)) {
            /**
             * Check for "call_user_func()" syntax.
             */
            if (is_string($obj[0])) {
                /**
                 * Static method format.
                 */
                $name = $obj[0] . '::' . $obj[1] . '()';
            } elseif (is_object($obj)) {
                /**
                 * Instance method format.
                 */
                $name = get_class($obj[0]) . '::' . $obj[1] . '()';
            } else {
                /**
                 * Unknown array object passed.
                 */
                throw new Exception('Got unknown object type.');
            }
        } elseif (is_object($obj)) {
            /**
             * Standard object instance.
             */
            $name = get_class($obj);
        } else {
            /**
             * Uknown object type passed.
             */
            throw new Exception('Got unknown object type.');
        }
        
        /**
         * Initialize globals, and merge in extraglobs.
         */
        if (is_null($globs)) {
            $globs = array();
        }
        
        if (!is_null($extraglobs)) {
            $globs = array_merge($globs, $extraglobs);
        }
        
        $tests = array();
        
        $this->_find($tests, $obj, $name, $globs, array());
        
        return $tests;
    }
    
    private function _find(&$tests, $obj, $name, $globs, $seen)
    {
        if ($this->_verbose) {
            echo 'Finding tests in ' . $name . "\n";
        }
        
        $docblock = $this->_getDocBlock($obj);
        $source = $this->_stripComment($docblock);
        
        $test = $this->_getTest($obj, $name, $globs, $source);
        
        if (!is_null($test)) {
            $tests[] = $test;
        }
    }
    
    private function _getDocBlock($obj)
    {
        if (is_string($obj)) {
            /**
             * Obj is either a function or a class name.
             */
            if (function_exists($obj)) {
                /**
                 * Obj is a function name.
                 */
                $reflection = new ReflectionFunction($obj);
                $docblock = $reflection->getDocComment();
            } elseif (class_exists($obj, false)) {
                /**
                 * Obj is a class name.
                 */
                $reflection = new ReflectionClass($obj);
                $docblock = $reflection->getDocComment();
            } else {
                throw new Exception('Invalid class or function name.');
            }
        } elseif (is_array($obj)) {
            /**
             * Check for "call_user_func()" syntax.
             */
            if (is_string($obj[0]) || is_object($obj)) {
                /**
                 * Static method format.
                 */
                $reflection = new ReflectionMethod($obj[0], $obj[1]);
                $docblock = $reflection->getDocComment();
            } else {
                /**
                 * Unknown array object passed.
                 */
                throw new Exception('Got unknown object type.');
            }
        } elseif (is_object($obj)) {
            /**
             * Obj is an object instance.
             */
            $reflection = new ReflectionObject($obj);
            $docblock = $reflection->getDocComment();
        }
        
        if ($docblock === false) {
            $docblock = '';
        }
        
        return $docblock;
    }
    
    /**
     * Removes docblock comment formatting and returns plaintext.
     */
    private function _stripComment($docblock)
    {
        return preg_replace('/^\s*\/?\*+\/?(.*)$/m', '$1', $docblock);
    }
    
    private function _getTest($obj, $name, $globs, $source)
    {
        /**
         * Don't bother if the docstring is empty.
         */
        if ($this->_exclude_empty && $source === '') {
            return null;
        }
        
        return $this->_parser->getDocTest($source, $globs, $name, 'unknown', -1);
    }
}