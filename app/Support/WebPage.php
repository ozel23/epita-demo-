<?php
namespace App\Support;
use Inertia\Inertia;
class WebPage
{
    public static function render($page, array $props = [])
    {
        // Laravel owns the data, permissions, validation and transitions.
        return Inertia::render($page, $props);
    }
}
