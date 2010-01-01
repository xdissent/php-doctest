<?php

/**
 * A single doctest example, consisting of source code and expected output.
 */
class DocTest_Example
{
    /**
     * A single PHP statement, always ending in a newline.
     *
     * The constructor adds a newline if needed.
     *
     * @var string
     */
    public $source;
    
    /**
     * The expected output from running the source code.
     *
     * Output may be either taken directly from stdout or a message generated
     * from an exception. This member should always end with a newline unless
     * it's empty, in which case it's an empty string. The constructor adds
     * a newline if needed.
     *
     * @var string
     */
    public $want;

    /**
     * The exception message generated by the example.
     *
     * This member will be a string message if the example is expected to 
     * generate an exception, or null if no exception is expected. The message
     * ends with a newline unless it's null. The constructor adds a newline
     * if needed.
     *
     * @var string
     */
    public $exc_msg;
    
    /**
     * The line number at which this example appears in the DocTest string.
     *
     * This line number is zero-based with respect to the beginning of the
     * DocTest.
     *
     * @var integer
     */
    public $lineno;
    
    /**
     * The example's indentation in the DocTest string.
     *
     * The number of space characters that precede the example's first prompt.
     *
     * @var integer
     */
    public $indent;
    
    /**
     * An array mapping from option flags to true or false, which is used to
     * override default options for this example. Any option flags not contained
     * in this array are left at their default value (as specified by the
     * DocTest_Runner's option flags). By default, no options are set.
     *
     * @var array
     */
    public $options;
    
    /**
     * Instantiate an example.
     *
     * @param string  $source  The source for the example.
     * @param string  $want    The expected example output.
     * @param string  $exc_msg The exception message expected.
     * @param integer $lineno  The line number on which the example begins.
     * @param integer $indent  The number of spaces indenting the example.
     * @param array   $options The doctest options to use with the example.
     *
     * @return null
     */
    public function __construct($source, $want, $exc_msg=null, $lineno=0, 
        $indent=0, $options=null
    ) {
        /**
         * Normalize variables.
         */
         
        if (substr($source, -1) !== "\n") {
            $source .= "\n";
        }
        
        if ($want !== '' && substr($want, -1) !== "\n") {
            $want .= "\n";
        }
        
        if (!is_null($exc_msg) && substr($exc_msg, -1) !== "\n") {
            $exc_msg .= "\n";
        }
        
        if (is_null($options)) {
            $options = array();
        }
        
        /**
         * Store the example's data.
         */
        $this->source = $source;
        $this->want = $want;
        $this->exc_msg = $exc_msg;
        $this->lineno = $lineno;
        $this->indent = $indent;
        $this->options = $options;
    }
}