<?php

if (!function_exists('e')) {
    /** Escape text before rendering it into HTML. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}