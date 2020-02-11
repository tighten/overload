![Overload logo](https://raw.githubusercontent.com/tightenco/overload/master/overload-banner.png)

# Method overloading for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tightenco/overload.svg?style=flat-square)](https://packagist.org/packages/tightenco/overload)
[![Build Status](https://img.shields.io/travis/tightenco/overload/master.svg?style=flat-square)](https://travis-ci.org/tightenco/overload)
[![Total Downloads](https://img.shields.io/packagist/dt/tightenco/overload.svg?style=flat-square)](https://packagist.org/packages/tightenco/overload)


NOTE: This is a beta release. It's Adam's original code almost exactly, and his docs; if a lot of folks are interested, we can, as a community, find its limits and edges and where it needs to grow. **Please note that, while all credit goes to Adam for writing this, the responsibility for maintaining it is not on him. Tighten will do our best to keep it up, but if this goes anywhere it will be because of community support. This is a beta release and does not carry with it any promise that it doesn't have bugs or holes.**


## Installation

You can install the package via composer:

```bash
composer require tightenco/overload
```

## Usage

This package gives you a declarative way to support multiple signatures for the same method.

### Basic Example

Say we have a `Ticket` class with a `holdUntil` method that lets us put that ticket on hold until a certain date and time by passing in a DateTime object:

```php
class Ticket extends Model
{
    // ...

    public function holdUntil(DateTime $dateTime)
    {
        $this->update(['hold_until' => $dateTime]);
    }

    // ...
}
```

...but now you decide it would be convenient if it could also accept a well-formatted date string.

Normally you'd do something like this:

```php
class Ticket extends Model
{
    // ...

    public function holdUntil($dateTime)
    {
        if (is_string($dateTime)) {
            $dateTime = Carbon::parse($dateTime);
        }

        $this->update(['hold_until' => $dateTime]);
    }

    // ...
}
```

The overloadable trait allows you to essentially pattern match in a declarative way instead of conditionally checking arguments:

```php
class Ticket extends Model
{
    use Overloadable;

    // ...

    public function holdUntil(...$args)
    {
        return $this->overload($args, [
            function (string $dateTime) {
               $this->update(['hold_until' => Carbon::parse($dateTime)]);
            },
            function (DateTime $dateTime) {
               $this->update(['hold_until' => $dateTime]);
            },
        ]);
    }

    // ...
}
```

If you wanted to avoid that duplication, you could even do this wild recursive madness:

```php
class Ticket extends Model
{
    use Overloadable;

    // ...

    public function holdUntil(...$args)
    {
        return $this->overload($args, [
            function (string $dateTime) {
               $this->holdUntil(Carbon::parse($dateTime));
            },
            function (DateTime $dateTime) {
               $this->update(['hold_until' => $dateTime]);
            },
        ]);
    }

    // ...
}
```


### A cooler example

You might be thinking:

> "Uhhh bro, that looks like even more code."

Yeah, because that example is boring. This one is a bit more fun.

I've always wanted Laravel's `validate` controller helper to accept a closure as its last parameter that let me return whatever HTTP response I wanted if validation failed.

But the method signature for `validate` takes like a million things and I don't want to pass a ton of empty arrays, for example:

```php
public function store()
{
    //                             Super grim! 😭
    //                                ⬇️  ⬇️
    $this->validate($request, $rules, [], [], function ($errors) {
        return response()->json([
            'someOtherInfo' => 'toInclude',
            'errors' => $errors
        ], 422);
    });
}
```

I'd love if I could just do:

```php
public function store()
{
    $this->validate($request, $rules, function ($errors) {
        return response()->json([
            'someOtherInfo' => 'toInclude',
            'errors' => $errors
        ], 422);
    });
}
```

...and have it magically work, knowing I clearly don't care about the `$messages` or `$customAttributes` arguments, but can you imagine how gross it would be to add those checks inside the `validate` method to do all this argument counting and type checking?!

Check out how it would work with this badass trait from the gods:

```php
trait ValidatesRequests
{
    // ...

    public function validate(...$args)
    {
        return $this->overload($args, [
            function ($request, $rules, Closure $callback) {
                return $this->validateRequest($request, $rules, [], [], $callback);
            },
            function ($request, $rules, $messages, Closure $callback) {
                return $this->validateRequest($request, $rules, $messages, [], $callback);
            },
            'validateRequest',
        ]);
    }

    // Move the real logic into a new private function...
    protected function validateRequest(Request $request, array $rules, array $messages = [], array $customAttributes = [], Closure $onErrorCallback = null)
    {
        $validator = $this->getValidationFactory()->make($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            $this->throwValidationException($request, $validator, $onErrorCallback);
        }
    }

    // ...
}
```


### Matching Options

Overloadable doesn't just work with closures; you can do all sorts of crazy stuff!

Check out this example from the test:

```php
class SomeOverloadable
{
    use Overloadable;

    public function someMethod(...$args)
    {
        return $this->overload($args, [
            // Call this closure if two args are passed and the first is an int
            function (int $a, $b) {
                return 'From the Closure';
            },

            // Call this method if the args match the args of `methodA` (uses reflection)
            'methodA',

            // Call this method if the args match the args of `methodB` (uses reflection)
            'methodB',

            // Call methodC if exactly 2 arguments of any type are passed
            'methodC' => ['*', '*'],

            // Call methodD if 3 args are passed and the first is an array
            'methodD' => ['array', '*', '*'],

            // Call methodE if 3 args are passed and the last is a closure
            'methodE' => ['*', '*', Closure::class],
        ]);
    }

    private function methodA($arg1)
    {
        return 'Method A';
    }

    private function methodB(\DateTime $arg1, array $arg2, int $arg3)
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
}
```

Methods are matched in the order they are specified when you call `overload`.

### Notes from Adam's original work

I'm still just hacking around with this and there's probably a bunch of things I'm missing.

For example, it just occurred to me that I haven't really considered how the reflection-based detection stuff should handle optional arguments, and off the top of my head I don't even know what it should do ¯\\_(ツ)_/¯ 

Either way, I think it's some pretty fun code and I thought it was pretty cool that we could even come up with an API for it at all.

## Upcoming plans:
- ~~Release beta with Adam's exact code~~
- Discover known shortcomings and document as issues (for starters, optional arguments and the forthcoming union types)
- Fix ^^
- Profit? 🤣 OK, not profit.

### Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email hello@tighten.co instead of using the issue tracker.

## Credits

The idea of method overloading comes from other languages that have it natively. I (Matt) have heard about it multiple times, including from my friend Adam Wathan, so when I decided to finally build something about it, I got a few hours in and then paused and asked Adam if he'd ever seen anyone build it. Turns out... he had.

He sent me a link to this [gist](https://gist.github.com/adamwathan/120f5acb69ba84e3fa911437242796c3). However, Adam didn't want to maintain a package, so, with his blessing, I spun this off to make it more accessible to the rest of the world.

- [Adam Wathan](https://github.com/adamwathan)
- [Matt Stauffer](https://github.com/mattstauffer)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
