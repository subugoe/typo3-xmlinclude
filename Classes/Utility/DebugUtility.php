<?php

namespace Subugoe\Xmlinclude\Utility;

class DebugUtility
{
    /**
     * @var array
     */
    public static $data;

    public static $error;

    public static function addError(string $message, string $fileInfo = '')
    {
        self::$error[] = ['message' => $message, 'fileInfo' => $fileInfo];
    }
}
