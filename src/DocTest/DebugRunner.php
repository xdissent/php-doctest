<?php

class DocTest_DebugRunner extends DocTest_Runner
{
    public function run($test, $out=null, $clear_globs=true)
    {
        $r = parent::run($test, $out, false);
        
        /**
         * Clear test globals if necessary.
         */
        if ($clear_globs) {
            $test->globs = array();
        }
        
        return $r;
    }
    
    protected function reportFailure($out, $test, $example, $got)
    {
        throw new DocTest_Failure($test, $example, $got);
    }
    
    protected function reportUnexpectedException($out, $test, $example, $exception)
    {
        throw new DocTest_UnexpectedException($test, $example, $exception);
    }
}