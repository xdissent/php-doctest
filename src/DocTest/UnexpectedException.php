<?php

class DocTest_UnexpectedException extends Exception
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
     * The actual exception that was thrown by the test.
     *
     * @var object
     */
    public $exception;
    
    /**
     * Creates an unexpected exception exception.
     *
     * @param object $test      The test which failed.
     * @param object $example   The example that caused the test to fail.
     * @param object $exception The exception generated by the example.
     * @param string $message   The optional exception message to send.
     *
     * @return null
     */
    public function __construct($test, $example, $exception, 
        $message='Unexpected exception encountered.'
    ) {
        /**
         * Save the objects that caused this failure.
         */
        $this->test = $test;
        $this->example = $example;
        $this->exception = $exception;

        /**
         * Call the base exception constructor.
         */
        parent::__construct($message);
    }
}