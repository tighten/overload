<?php

if (! defined('overload')) {
    function overload()
    {
        (new Tightenco\Overload\Overload)(func_get_args());
    }
}
