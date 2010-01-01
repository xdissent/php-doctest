<?php
/**
 * This is the "example" module.
 *
 * The example module supplies one function, {@link factorial()}.  For example,
 *
 * <code>
 * php > var_dump(factorial(5));
 * int(120)
 * </code>
 */

/**
 * Returns the factorial of n, an exact integer >= 0.
 *
 * If the result is small enough to fit in an int, return an int.
 * Else return a long.
 *
 * <code>
 * php > for($n = 0; $n < 6; $n++) { $out[] = factorial($n); }
 * php > var_dump($n);
 * array(6) {
 *   [0]=>
 *   int(0)
 *   [1]=>
 *   int(1)
 *   [2]=>
 *   int(2)
 *   [3]=>
 *   int(3)
 *   [4]=>
 *   int(4)
 *   [5]=>
 *   int(5)
 * }
 * </code>
 * 
 * Factorials of floats are OK, but the float must be an exact integer:
 *
 * <code>
 * php > var_dump(factorial(30.1));
 * PHP Fatal error:  Uncaught exception 'InvalidArgumentException' with message '$n must be exact integer'
 * php > var_dump(factorial(30.0));
 * float(2.6525285981219E+32)
 * </code>
 *
 * It must also not be ridiculously large:
 *
 * <code>
 * php > var_dump(factorial(1e100));
 * PHP Fatal error:  Uncaught exception 'OverflowException' with message 
 *     '$n too large'
 * </code>
 */
function factorial($n) {
    if (!($n >= 0)) {
        throw new InvalidArgumentException('$n must be >= 0');
    }
    
    if (floor($n) != $n) {
        throw new InvalidArgumentException('$n must be exact integer');
    }
    
    if ($n + 1 == $n) {
        throw new OverflowException('$n too large');
    }
    
    $result = 1;
    $factor = 2;
    
    while ($factor <= $n) {
        $result *= $factor;
        $factor += 1;
    }
    
    return $result;
}

if (!count(debug_backtrace())) {
    require dirname(__FILE__) . '/../src/DocTest/import.php';
    DocTest::testObj('factorial', null, true);
}