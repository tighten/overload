<?php

namespace Tightenco\Overload;

use Tightenco\Collect\Support\Collection;

trait Overloadable
{
    public function overload($args, $signatures)
    {
        return (new Collection($signatures))->map(function ($value, $key) {
            return new OverloadedMethodCandidate($value, $key, $this);
        })->first(function ($candidate) use ($args) {
            return $candidate->matches($args);
        })->call($args);
    }
}
