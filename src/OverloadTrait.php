<?php

namespace Tightenco\Overload;

trait OverloadTrait
{
    public function overload($rule, $args)
    {
        if (is_string($rule)) {
            return $this->overloadForAllMethodsByPrefix($this, $rule, $args);
        }
    }

    public function overloadForAllMethodsByPrefix($object, $prefix, $args)
    {
        // @todo get all methods on $object that start with $prefix
        // @todo strip out one with nothing after the prefix, assuming it's the primary method
        // @todo strip out and set aside one with "fallback" after the prefix
        // @todo check the argument arity for each method and find one that matches the count of $args, and pass there
        // @todo if that doesn't work, pass it off to the fallback
        // @todo if no fallback... throw exception?
    }
}
