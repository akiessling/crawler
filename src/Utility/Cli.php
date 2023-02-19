<?php


namespace AndreasKiessling\Crawler\Utility;


class Cli
{

    public static function getColorTagForStatusCode(string $code): string
    {
        if (StringUtility::beginsWith($code, '2')) {
            return 'info';
        }

        if (StringUtility::beginsWith($code, '3')) {
            return 'comment';
        }

        return 'error';
    }
}
