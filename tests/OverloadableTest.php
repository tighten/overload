<?php

namespace Tightenco\Overload\Tests;

use Closure;
use DateTime;
use PHPUnit\Framework\TestCase;
use Tightenco\Overload\Overloadable;

class OverloadableTest extends TestCase
{
    private $overloadable;

    protected function setUp(): void
    {
        $this->overloadable = new TestOverloadable;
    }

    /** @test */
    function it_overloads()
    {
        $this->assertEquals('Method A', $this->overloadable->someMethod(1.2));
        $this->assertEquals('From the Closure', $this->overloadable->someMethod(5, true));
        $this->assertEquals('Method B', $this->overloadable->someMethod(new DateTime, [1, 2, 3], 9));
        $this->assertEquals('Method C', $this->overloadable->someMethod('foo', 'bar'));
        $this->assertEquals('Method D', $this->overloadable->someMethod([], true, true));
        $this->assertEquals('Method E', $this->overloadable->someMethod(true, true, function () {}));
        $this->assertEquals('Method F', $this->overloadable->someMethod('testString'));
    }
}

class TestOverloadable
{
    use Overloadable;

    public function someMethod(...$args)
    {
        return $this->overload($args, [
            function (int $a, $b) {
                return 'From the Closure';
            },
            'methodA',
            'methodB',
            'methodC' => ['string', '*'],
            'methodD' => ['array', '*', 'bool'],
            'methodE' => ['bool', '*', Closure::class],
            'methodF',
        ]);
    }

    private function methodA(float $arg1)
    {
        return 'Method A';
    }

    private function methodB(DateTime $arg1, array $arg2, int $arg3)
    {
        return 'Method B';
    }

    private function methodC($arg1, $arg2)
    {
        return 'Method C';
    }

    private function methodD($arg1, $arg2, $arg3)
    {
        return 'Method D';
    }

    private function methodE($arg1, $arg2, $arg3)
    {
        return 'Method E';
    }

    private function methodF($arg1)
    {
        return 'Method F';
    }
}
