<?php

namespace App\Utils;

/**
 * Test stub for TurboSms that prevents real HTTP calls in unit tests
 * This file is autoloaded before the real TurboSms in test environment
 */
class TurboSms
{
    public static bool $wasCalled = false;
    public static ?string $lastRecipient = null;
    public static ?string $lastMessage = null;

    /**
     * @param string $to
     * @param string $text
     * @return void
     */
    public static function send(string $to, string $text): void
    {
        // Record that the method was called for assertions
        self::$wasCalled = true;
        self::$lastRecipient = $to;
        self::$lastMessage = $text;

        // Do NOT make any HTTP requests - this is a test stub
    }

    public static function reset(): void
    {
        self::$wasCalled = false;
        self::$lastRecipient = null;
        self::$lastMessage = null;
    }
}
