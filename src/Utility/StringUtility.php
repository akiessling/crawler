<?php


namespace AndreasKiessling\Crawler\Utility;


class StringUtility
{
    public static function beginsWith(string $haystack, string $needle):bool
    {
        return $needle !== '' && strpos($haystack, $needle) === 0;
    }

    public static function endsWith(string $haystack, string $needle):bool
    {
        $haystackLength = strlen($haystack);
        $needleLength = strlen($needle);
        if (!$haystackLength || $needleLength > $haystackLength) {
            return false;
        }
        $position = strrpos($haystack, $needle);
        return $position !== false && $position === $haystackLength - $needleLength;
    }
}
