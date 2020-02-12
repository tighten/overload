<?php

namespace Tightenco\Overload;

use Closure;
use Exception;
use ReflectionFunction;
use ReflectionMethod;

class OverloadedMethodCandidate
{
    private $callable;
    private $arguments = [];

    public function __construct($signatureOrMethod, $methodOrKey, $object)
    {
        if (! is_int($methodOrKey)) {
            $this->buildFromSignature($signatureOrMethod, $methodOrKey, $object);
        } elseif (is_string($signatureOrMethod) && method_exists($object, $signatureOrMethod)) {
            $this->buildUsingMethodReflection($object, $signatureOrMethod);
        } elseif ($signatureOrMethod instanceof Closure) {
            $this->buildUsingClosureReflection($object, $signatureOrMethod);
        } else {
            throw new Exception('Unrecognized overloaded method definition.');
        }
    }

    public function matches($args)
    {
        if (count($args) !== count($this->arguments)) {
            return false;
        }

        return collect($args)->zip($this->arguments)->reduce(function ($isMatch, $argAndType) {
            list($arg, $type) = $argAndType;
            return $isMatch && ($type === '*' || gettype($arg) === $type || $arg instanceof $type);
        }, true);
    }

    public function call($args)
    {
        return $this->callable->__invoke(...$args);
    }

    private function buildFromSignature($signature, $method, $object)
    {
        $this->callable = $this->bindCallable($object, $method);
        $this->arguments = $this->normalizeTypes($signature);
    }

    private function buildUsingMethodReflection($object, $method)
    {
        $this->callable = $this->bindCallable($object, $method);
        $reflected = new ReflectionMethod($object, $method);
        $this->arguments = $this->mapArguments($reflected);
    }

    private function bindCallable($object, $method)
    {
        $closure = function (...$args) use ($method) {
            return $this->{$method}(...$args);
        };
        return $closure->bindTo($object, $object);
    }

    private function buildUsingClosureReflection($object, $closure)
    {
        $this->callable = $closure->bindTo($object);
        $reflected = new ReflectionFunction($closure);
        $this->arguments = $this->mapArguments($reflected);
    }

    private function mapArguments($reflectionFunction)
    {
        $types = array_map(function ($parameter) {
            if (! $parameter->hasType()) {
                return '*';
            }

            return $parameter->getType()->getName();
        }, $reflectionFunction->getParameters());

        return $this->normalizeTypes($types);
    }

    private function normalizeTypes($types)
    {
        return array_map(function ($type) {
            return $type == 'int' ? 'integer' : $type;
        }, $types);
    }
}
