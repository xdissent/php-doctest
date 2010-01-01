<?php

/**
 * A class used to parse strings containing doctest examples.
 */
class DocTest_Parser
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
     * This regex does not check for multiline comments, which are basically
     * impossible to do in doctests that reside within another multiline
     * comment in a source file anyway. It does check for both shell style
     * and C++ style single line comments.
     *
     * @var string
     */
    private $_is_blank_or_comment_re = '/^[ ]*(#.*)?$/';
    
    /**
     * A regex to find doctest option directives.
     *
     * This regular expression looks for option directives in the
     * source code of an example.  Option directives are comments
     * starting with "doctest:".  Warning: this may give false
     * positives for string-literals that contain the strings
     * "#doctest:" or "//doctest:'.  Eliminating these false positives would 
     * require actually parsing the string; but we limit them by ignoring any
     * line containing "#doctest:" or "//doctest:" that is *followed* by a 
     * quote mark.
     *
     * @var string
     */
    private $_option_directive_re = '/(?:#|\/\/)\s*doctest:\s*([^\n\'"]*)$/m';
    
    /**
     * Initializes a DocTest_Parser instance.
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
                (?:\n           [ ]*  php[ ]{.*)*)  # PS2 lines
            \n?
            # Want consists of any non-blank lines that do not start with PS1.
            (?P<want> (?:(?![ ]*$)        # Not a blank line
                         (?![ ]*php[ ]>)  # Not a line starting with PS1
                         .*$\n?           # But any other line
                      )*)
            /xm';

        $this->_exception_re = '/
            # An exception triggers a fatal error.
            ^(?P<hdr>PHP\ Fatal\ error:\s+ Uncaught\ exception\ \'
            
                (?P<type> \w+)          # Type of exception.
            \'\ with\ message\ \'
                (?P<msg> \w+ .*)        # The message.
            \'\ in\ 
                (?P<loc>[\w\ ]+)        # The location.
            :
                (?P<line>\d+)           # The line number.
            )
            \s* $ \n?
            
            Stack\ trace: 
            \s* $ \n?
                (?P<stack> .*)          # The stack trace.
            \s+ thrown\ in\ 
                (?P<loc2>[\w\ ]+)       # The location (again!)
            on\ line\ 
                (?P<line2>\d+)          # The line number (AGAIN!)
            $
            /xms';
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
        if (preg_match($this->_is_blank_or_comment_re, $line)) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns a DocTest object containing all examples found in a string.
     *
     * @param string  $string   The string to parse to find examples.
     * @param array   $globs    The globs to use for the DocTest.
     * @param string  $name     The name of the source.
     * @param string  $filename The filename of the source.
     * @param integer $lineno   The line number beginning the source.
     *
     * @return object
     * @see DocTest
     */
    public function getDocTest($string, $globs, $name, $filename, $lineno)
    {
        return new DocTest(
            $this->getExamples($string, $name),
            $globs,
            $name,
            $filename,
            $lineno,
            $string
        );
    }
    
    /**
     * Returns an array of Example objects after parsing the string.
     *
     * Line numbers are zero-based, because it's most common in doctests
     * that nothing interesting appears on the same line as the opening
     * comment string, and so the first interesting line is called "line 1"
     * then. The optional name argument is only used for error messages.
     *
     * @param string $string The string to parse for examples.
     * @param string $name   The name of the source string.
     *
     * @return array
     * @see DocTest_Example
     */
    protected function getExamples($string, $name='<string>')
    {
        $examples = array();
        $parsed = $this->parse($string, $name);
        foreach ($parsed as $x) {
            if ($x instanceof DocTest_Example) {
                $examples[] = $x;
            }
        }
        return $examples;
    }
    
    /**
     * Returns an array of Examples and string extracted from the passed string.
     *
     * Line numbers for the Examples are 0-based.  The optional
     * argument `name` is a name identifying this string, and is only
     * used for error messages.
     *
     * @param string $string The text to parse.
     * @param string $name   The name of the string to parse.
     *
     * @return array
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
        
        /**
         * Find all examples in the string.
         */
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
             * Extract info from the regexp match.
             */
            list($source, $want, $exc_msg, $options) = $this->_parseExample(
                $m, 
                $name, 
                $lineno
            );
            
            if (!$this->_isBlankOrComment($source)) {
                $output[] = new DocTest_Example(
                    $source,
                    $want,
                    $exc_msg,
                    $lineno,
                    $min_indent + strlen($m['indent'][0]),
                    $options
                );
            }
            
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
    
    /**
     * Parses an example.
     *
     * @param array   $m      A regex match array.
     * @param string  $name   The name of the example.
     * @param integer $lineno The line number at which this example appears.
     *
     * @return array
     */
    private function _parseExample($m, $name, $lineno)
    {
        $indent = strlen($m['indent'][0]);
        
        /**
         * Divide source into lines; check that they're properly
         * indented; and then strip their indentation & prompts.
         */
        $source_lines = explode("\n", $m['source'][0]);
        foreach ($source_lines as &$sl) {
            $sl = substr($sl, $indent + 6);
        }
        unset($sl);
        $source = implode("\n", $source_lines);
                
        /**
         * We aren't doing this yet...
         *
         * self._check_prompt_blank(source_lines, indent, name, lineno)
         * self._check_prefix(source_lines[1:], ' '*indent + '.', name, lineno)
         */

        $want = $m['want'][0];
        /**
         * We aren't doing this yet either...
         *
         * # Divide want into lines; check that it's properly indented; and
         * # then strip the indentation.  Spaces before the last newline should
         * # be preserved, so plain rstrip() isn't good enough.
         *
         * want = m.group('want')
         * want_lines = want.split('\n')
         * if len(want_lines) > 1 and re.match(r' *$', want_lines[-1]):
         *     del want_lines[-1]  # forget final newline & spaces after it
         * self._check_prefix(want_lines, ' '*indent, name,
         *                    lineno + len(source_lines))
         * want = '\n'.join([wl[indent:] for wl in want_lines])
         */
        
        /**
         * If want contains a traceback message, then extract it.
         */
        if (preg_match($this->_exception_re, $want, $m)) {
            $exc_msg = $m['msg'];
        } else {
            $exc_msg = null;
        }

        /**
         * Extract options from the source.
         */
        $options = $this->_findOptions($source, $name, $lineno);
        
        return array($source, $want, $exc_msg, $options);
    }
    
    /**
     * Return an array containing option overrides extracted from the source.
     *
     * "name" is the string's name, and "lineno" is the line number
     * where the example starts; both are used for error messages.
     *
     * @param string $source The source from which to extract options.
     * @param string $name   The name of the source.
     * @param string $lineno The line number of the source.
     *
     * @return array
     */
    private function _findOptions($source, $name, $lineno)
    {
        $options = array();
        
        /**
         * Check for options in source comments.
         */
        if (preg_match($this->_option_directive_re, $source, $m)) {
        
            /**
             * Split comments by whitespace and commas.
             */
            $option_strings = explode(' ', str_replace(',', ' ', $m[1]));
            
            /**
             * Process each option string.
             */
            foreach ($option_strings as $option) {
            
                $posneg = substr($option, 0, 1);
                $option_name = substr($option, 1);
                
                /**
                 * Remove "DOCTEST_" prefix if found.
                 */
                $option_name = str_replace('DOCTEST_', '', $option_name);
                
                /**
                 * Check for invalid option.
                 */
                if (($posneg != '+' && $posneg != '-') 
                    || !array_key_exists(
                        $option_name, 
                        DocTest::$option_flags_by_name
                    )
                ) {
                    $msg = sprintf(
                        'line %d of the doctest for %s has an invalid ' .
                            'option: %s',
                        $lineno + 1,
                        $name,
                        $option
                    );
                    throw new UnexpectedValueException($msg);
                }
                
                /**
                 * Retrieve the flag for the found option.
                 */
                $flag = DocTest::$option_flags_by_name[$option_name];
                
                /**
                 * Add the option to the options array.
                 */
                if ($posneg == '+') {
                    $options[$flag] = true;
                } else {
                    $options[$flag] = false;
                }
            }
        }
        
        /**
         * Make sure blank lines or comments don't specify options.
         */
        if (count($options) && $this->_isBlankOrComment($source)) {
            $msg = sprintf(
                'line %d of the doctest for %s has an option directive '.
                    'on a line with no example: %s',
                $lineno,
                $name,
                $source
            );
            throw new UnexpectedValueException($msg);
        }
                            
        return $options;
    }
}