<?php

class DocTest_OutputChecker
{
    /**
     * <note>This method is called "check_output()" in Python.</note>
     */
    public function checkOutput($want, $got, $optionflags)
    {
        /**
         * Handle the common case first, for efficiency:
         * if they're string-identical, always return true.
         */
        if ($want === $got) {
            return true;
        }
        
        /**
         * The values True and False replaced 1 and 0 as the return
         * value for boolean comparisons.
         */
        if (!($optionflags & DOCTEST_DONT_ACCEPT_TRUE_FOR_1)) {
            if ($got === 'True\n' && $want === '1\n') {
                return true;
            }
            if ($got === 'False\n' && $want === '0\n') {
                return true;
            }
        }
        
        /**
         * "<BLANKLINE>" can be used as a special sequence to signify a
         * blank line, unless the DONT_ACCEPT_BLANKLINE flag is used.
         */
        if (!($optionflags & DOCTEST_DONT_ACCEPT_BLANKLINE)) {
            /**
             * Replace <BLANKLINE> in want with a blank line.
             */
            $blank_re = '/(?m)^';
            $blank_re .= preg_quote(DOCTEST_BLANKLINE_MARKER);
            $blank_re .= '\s*?$/';
            
            $want = preg_replace($blank_re, '', $want);
            
            /**
             * If a line in got contains only spaces, then remove the
             * spaces.
             */
            $got = preg_replace('/(?m)^\s*?$/', '', $got);
            
            if ($got === $want) {
                return true;
            }
        }
        
        /**
         * This flag causes doctest to ignore any differences in the
         * contents of whitespace strings. Note that this can be used
         * in conjunction with the ELLIPSIS flag.
         */
        if ($optionflags & DOCTEST_NORMALIZE_WHITESPACE) {
            $got = trim(implode(' ', preg_split('/\s+/', $got)));
            $want = trim(implode(' ', preg_split('/\s+/', $want)));
            
            if ($got === $want) {
                return true;
            }
        }
        
        /**
         * The ELLIPSIS flag says to let the sequence "..." in want
         * match any substring in got.
         */
        if ($optionflags & DOCTEST_ELLIPSIS) {
            if ($this->_ellipsisMatch($want, $got)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function _ellipsisMatch($want, $got)
    {
        /**
         * Try to bail early.
         */
        if (strpos($want, DOCTEST_ELLIPSIS_MARKER) === false) {
            if ($want === $got) {
                return true;
            }
            return false;
        }
        
        /**
         * Find the "real" strings.
         */
        $ws = explode(DOCTEST_ELLIPSIS_MARKER, $want);
        
        /**
         * Deal with exact matches possibly needed at one or both ends.
         */
        $startpos = 0;
        $endpos = strlen($got);

        $w = $ws[0];
        
        if ($w !== '') {
            /**
             * Starts with exact match.
             */
            if (substr($got, 0, strlen($w)) === $w) {
                $startpos = strlen($w);
                unset($ws[0]);
            } else {
                return false;
            }
        }
        
        $w = $ws[count($ws) - 1];
        
        if ($w !== '') {
            /**
             * Ends with exact match.
             */
            if (substr($got, -strlen($w)) === $w) {
                $endpos -= strlen($w);
                unset($ws[count($ws) - 1]);
            } else {
                return false;
            }
        }
        
        if ($startpos > $endpos) {
            /**
             * Exact end matches required more characters than we have, as in
             * 'aa...aa' and 'aaa'.
             */
            return false;
        }
        
        /**
         * For the rest, we only need to find the leftmost non-overlapping
         * match for each piece.  If there's no overall match that way alone,
         * there's no overall match period.
         */
        foreach ($ws as $w) {
            /**
             * w may be '' at times, if there are consecutive ellipses, or
             * due to an ellipsis at the start or end of `want`.  That's OK.
             * Search for an empty string succeeds, and doesn't change startpos.
             */
            $sliced = substr($got, $startpos, $endpos - $startpos);
            $startpos = strpos($sliced, $w);

            if ($startpos === false) {
                return false;
            }
            
            $startpos += strlen($w);
        }
        
        return true;
    }
}