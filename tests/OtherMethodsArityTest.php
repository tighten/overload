<?php

namespace Tightenco\Overload\Tests;

use PHPUnit\Framework\TestCase;

class OtherMethodsArityTest extends TestCase
{
    /** @test */
    function single_argument_call_is_matched_to_single_argument_method()
    {
        $class = new OtherMethodsArity;

        $return = $class->do('single item');

        $this->assertEquals('one', $return);
    }
}

class OtherMethodsArity
{
    use \Tightenco\Overload\OverloadTrait;

    public function do()
    {
        return $this->overload('do', func_get_args());
    }

    public function doOne($a)
    {
        return 'one';
    }

    public function doTwo($a, $b)
    {
        return 'two';
    }

    public function doFallback()
    {
        return 'fallback';
    }
}
