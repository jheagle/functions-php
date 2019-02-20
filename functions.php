<?php


/**
 * Based on the function passed in, reduce its parameters with the provided $args. Return the result of evaluation
 * the function or a new function with less arguments.
 *
 * @param callable|string $fn A string function name or function to be curried
 * @param string $class Optional class name for the function
 *
 * @return callable
 */
function curry($fn, $class = __CLASS__): callable
{
    /**
     * Take some $args to be applied to the function, either return the evaluated function or a new version of the
     * function with some $args applied here
     *
     * @param mixed ...$args The known function arguments to be re-used
     *
     * @return \Closure|mixed
     */
    return function (...$args) use ($fn, $class) {
        return count($args) >= (new \ReflectionMethod($class, $fn))->getNumberOfRequiredParameters()
            ? call_user_func_array([$class, $fn], $args)
            : function (...$a) use ($fn, $class, $args) {
                return call_user_func_array(curry($fn, $class), array_merge($args, $a));
            };
    };
}

/**
 * Pass in a few methods to be run in sequence, returns a function expecting the data which will be altered by the
 * sequence.
 *
 * @param callable[] ...$fns All of the functions receiving the same parameter
 *
 * @return \Closure
 */
function apply(...$fns)
{
    /**
     * Pass $data to each of the $fns provided along with any mutations it receives.
     *
     * @param mixed $data The data to have each function applied to.
     *
     * @return mixed
     */
    return function ($data) use ($fns) {
        /**
         * @var bool $cancelApply This flag is set when a function has received it by reference and within the
         * function it may cancel executing subsequent apply functions
         */
        $cancelApply = false;
        return array_reduce($fns, function ($d, callable $f) use ($data, &$cancelApply) {
            return $cancelApply ? $d : $f($d, $cancelApply);
        }, $data);
    };
}