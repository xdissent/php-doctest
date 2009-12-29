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
    
    private $_option_flags_by_name;
    
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
                (?:\n           [ ]*  php[ ]{.*)*)  # PS2 lines
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
    
        /**
         * Create option flags array.
         */
        $this->_option_flags_by_name = array(
            'testoption' => 1,
            'anotheroption' => 2
        );
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
             * Extract info from the regexp match
             */
            $info = $this->_parseExample($m, $name, $lineno);
            
            if (!$this->_isBlankOrComment($info['source'])) {
                $output[] = new DocTest_Example(
                    $info['source'],
                    $info['want'],
                    $info['exc_msg'],
                    $lineno,
                    $min_indent + strlen($m['indent'][0]),
                    $info['options']
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
    
    private function _parseExample($m, $name, $lineno)
    {
        $indent = strlen($m['indent'][0]);
        
        /**
         * Divide source into lines; check that they're properly
         * indented; and then strip their indentation & prompts.
         */
        $source_lines = explode("\n", $m['source'][0]);
        
        /**
         * We aren't doing this yet...
         * self._check_prompt_blank(source_lines, indent, name, lineno)
         * self._check_prefix(source_lines[1:], ' '*indent + '.', name, lineno)
         */
        foreach ($source_lines as &$sl) {
            $sl = substr($sl, $indent + 6);
        }
        unset($sl);
        $source = implode("\n", $source_lines);
                
        # Divide want into lines; check that it's properly indented; and
        # then strip the indentation.  Spaces before the last newline should
        # be preserved, so plain rstrip() isn't good enough.
        /*
        want = m.group('want')
        want_lines = want.split('\n')
        if len(want_lines) > 1 and re.match(r' *$', want_lines[-1]):
            del want_lines[-1]  # forget final newline & spaces after it
        self._check_prefix(want_lines, ' '*indent, name,
                           lineno + len(source_lines))
        want = '\n'.join([wl[indent:] for wl in want_lines])
        */
        $want = $m['want'][0];
        
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
        
        return array(
            'source' => $source,
            'want' => $want,
            'exc_msg' => $exc_msg,
            'options' => $options
        );
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
        
        if (preg_match($this->_option_directive_re, $source, $m)) {
            $option_strings = explode(' ', str_replace(',', ' ', $m[1]));
            foreach ($option_strings as $option) {
                /**
                 * Check for invalid option.
                 */
                $posneg = substr($option, 0, 1);
                $option_name = substr($option, 1);
                if (($posneg != '+' && $posneg != '-') 
                    || !array_key_exists(
                        $option_name, 
                        $this->_option_flags_by_name
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
                $flag = $this->_option_flags_by_name[$option_name];
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