<?php

require dirname(__FILE__) . '/Example.php';

class DocTestParser
{
    /**
     * A regex to extract an example from source.
     *
     * @var string
     */
    private $_example_re;
    
    /**
     * A regex used to extract exception information from source.
     *
     * @var string
     */
    private $_exception_re;
    
    /**
     * A regex used to determine indentation of source code.
     *
     * @var string
     */
    private $_indent_re = '/^([ ]*)(?=\S)/m';
    
    /**
     * A regex to determine whether a line is blank or is a comment.
     *
     * @var string
     */
    private $_is_blank_or_comment_re = '/^[ ]*(\/\/.*)?$/';
    
    /**
     * Initializes a DocTestParser instance.
     *
     * @return null
     */
    public function __construct()
    {
        /**
         * Initialize long regex patterns that need Nowdoc from PHP 5.3.
         */
        $this->_example_re = '/
            # Source consists of a PS1 line followed by zero or more PS2 lines.
            (?P<source>
                (?:^(?P<indent> [ ]*) php[ ]>.*)    # PS1 line
                (?:\n           [ ]*  \.\.\. .*)*)  # PS2 lines
            \n?
            # Want consists of any non-blank lines that do not start with PS1.
            (?P<want> (?:(?![ ]*$)        # Not a blank line
                         (?![ ]*php[ ]>)  # Not a line starting with PS1
                         .*$\n?           # But any other line
                      )*)/xm';
        
        $this->_exception_re = '/
            # Grab the traceback header.  Different versions of Python have
            # said different things on the first traceback line.
            ^(?P<hdr> Traceback\ \(
                (?: most\ recent\ call\ last
                |   innermost\ last
                ) \) :
            )
            \s* $                # toss trailing whitespace on the header.
            (?P<stack> .*?)      # don\'t blink: absorb stuff until...
            ^ (?P<msg> \w+ .*)   #     a line *starts* with alphanum./xms';
    }
    
    /**
     * Return the minimum indentation of any non-blank line in source code.
     *
     * @param string $source The source code to check for indentation.
     *
     * @return integer
     */
    private function _minIndent($source)
    {
        $matches = array();
        
        preg_match_all($this->_indent_re, $source, $matches, PREG_SET_ORDER);
        
        if (count($matches) < 0) {
            return 0;
        }
        
        $indents = array();
        
        foreach ($matches[0] as $m) {
            $indents[] = strlen($m);
        }
        
        if (count($indents) > 0) {
            return min($indents);
        }
        
        return 0;
    }
    
    /**
     * Returns a boolean indicating whether a line is blank or a comment.
     *
     * @param string $line The line to check for a comment or blankness.
     *
     * @return boolean
     */
    private function _isBlankOrComment($line)
    {
        if (preg_match($this->_is_blank_or_comment_re, $string)) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns an array of Examples and string extracted from the passed string.
     *
     * Line numbers for the Examples are 0-based.  The optional
     * argument `name` is a name identifying this string, and is only
     * used for error messages.
     */
    public function parse($string, $name='<string>')
    {
        /**
         * Expand tab characters into 8 spaces.
         */
        $string = str_replace("\t", str_repeat(' ', 8), $string);
        
        /**
         * Find the minimum indentation level for the entire string.
         */
        $min_indent = $this->_minIndent($string);
        
        /**
         * Remove the minimum indentation from each line if found.
         */
        if ($min_indent > 0) {
            $lines = explode("\n", $string);
            foreach ($lines as &$line) {
                $line = substr($line, $min_indent);
            }
            unset($line);
            $string = implode("\n", $lines);
        }
        
        $matches = array();
        $output = array();
        $charno = 0;
        $lineno = 0;
        
        preg_match_all($this->_example_re, $string, $matches, PREG_OFFSET_CAPTURE);
        
        /**
         * Traverse all matches. (Similar to Python's finditer() loop.)
         */
        for ($i = 0; $i < count($matches[0]); $i++) {
            $m = array();
            
            foreach ($matches as $k => $v) {
                $m[$k] = $v[$i];
            }
                        
            /**
             * $m is now an array of match groups (offset 0 is full pattern).
             * Each group is an array in which the value of offset 0 is the
             * matched string, and offset 1 is the string offset at which it 
             * was matched.
             */

            if ($m[0][1] - $charno > 0) {
                /**
                 * Add the pre-example text to output.
                 */
                $output[] = substr($string, $charno, $m[0][1] - $charno);
                
                /**
                 * Update lineno (lines before this example)
                 */
                $lineno += substr_count($string, "\n", $charno, $m[0][1] - $charno);
            }
            
            /**
             * Extract info from the regexp match
             */
            $info = $this->_parseExample($m, $name, $lineno);
            
            // if (!$this->_isBlankOrComment($info['source'])) {
                $output[] = new Example(
                    $info['source'],
                    $info['want'],
                    $info['exc_msg'],
                    $lineno,
                    $min_indent + strlen($m['indent'][0]),
                    $info['options']
                );
            //}
            
            /**
             * Update lineno (lines inside this example.)
             */
            $lineno += substr_count($string, "\n", $m[0][1], strlen($m[0][0]));
            
            /**
             * Update charno.
             */
            $charno = $m[0][1] + strlen($m[0][0]);
        }
            
        /**
         * Add any remaining post-example text to `output`.
         */
        if (substr($string, $charno) !== false) {
            $output[] = substr($string, $charno);
        }
        
        return $output;
    }
    
    private function _parseExample($m, $name, $lineno)
    {
        return array(
            'source' => 'source',
            'want' => 'want',
            'exc_msg' => 'exc_msg',
            'options' => 'options'
        );
    }
}