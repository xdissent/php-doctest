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
     * Contained objects may be searched, depending on the recurse option passed
     * to the instance's constructor.
     *
     * @param object $obj      The object to search for DocTests.
     * @param string $name     The name of the object.
     */
    public function find($obj, $name=null, $module=null, $globs=null, $extraglobs=null)
    {
    }
}