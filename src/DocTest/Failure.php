<?php

class DocTest_Failure extends Exception
{
    /**
     * The test being run when the failure was encountered.
     *
     * @var object
     */
    public $test;
    
    /**
     * The example in the test that failed.
     *
     * @var object
     */
    public $example;
    
    /**
     * The actual output from the example when tested.
     *
     * @var string
     */
    public $got;
    
    /**
     * Creates a failure exception.
     *
     * @param object $test    The test which failed.
     * @param object $example The example that caused the test to fail.
     * @param string $got     The actual output from running the example.
     * @param string $message The optional exception message to send.
     *
     * @return null
     */
    public function __construct($test, $example, $got, 
        $message='Test failure encountered.'
    ) {
        /**
         * Save the objects that caused this failure.
         */
        $this->test = $test;
        $this->example = $example;
        $this->got = $got;
        
        /**
         * Call the base exception constructor.
         */
        parent::__construct($message);
    }
}