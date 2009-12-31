<?php

class DocTest_OutputChecker
{
    /**
     * <note>This method is called "check_output()" in Python.</note>
     */
    public function checkOutput($want, $got, $optionflags)
    {
        if ($want === $got) {
            return true;
        }
        return false;
    }
}