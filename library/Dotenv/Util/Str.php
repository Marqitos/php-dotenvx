<?php

declare(strict_types=1);

namespace Dotenv\Util;

use GrahamCampbell\ResultType\Error;
use GrahamCampbell\ResultType\Success;
use PhpOption\Option;

use function mb_convert_encoding;
use function mb_list_encodings;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;

/**
 * @internal
 */
final class Str
{
    const R_GRAHAM_CAMPBELL_RESULT_TYPE_ERROR   = 'GrahamCampbell/ResultType/Error.php';
    const R_SYMFONY_POLYFILL_MBSTRING_BOOTSTRAP = 'Symfony/Polyfill/Mbstring/bootstrap.php';

    /**
     * This class is a singleton.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Convert a string to UTF-8 from the given encoding.
     *
     * @param string      $input
     * @param string|null $encoding
     *
     * @return \GrahamCampbell\ResultType\Result<string, string>
     */
    public static function utf8(string $input, ?string $encoding = null)
    {
        if (!is_callable('mb_list_encodings')) {
            require_once self::R_SYMFONY_POLYFILL_MBSTRING_BOOTSTRAP;
        }
        if ($encoding !== null && !\in_array($encoding, mb_list_encodings(), true)) {
            require_once self::R_GRAHAM_CAMPBELL_RESULT_TYPE_ERROR;
            /** @var \GrahamCampbell\ResultType\Result<string, string> */
            return Error::create(
                \sprintf('Illegal character encoding [%s] specified.', $encoding)
            );
        }

        if (!is_callable('mb_convert_encoding')) {
            require_once self::R_SYMFONY_POLYFILL_MBSTRING_BOOTSTRAP;
        }
        $converted = $encoding === null ?
            @mb_convert_encoding($input, 'UTF-8') :
            @mb_convert_encoding($input, 'UTF-8', $encoding);

        if (!is_string($converted)) {
            require_once self::R_GRAHAM_CAMPBELL_RESULT_TYPE_ERROR;
            /** @var \GrahamCampbell\ResultType\Result<string, string> */
            return Error::create(
                \sprintf('Conversion from encoding [%s] failed.', $encoding ?? 'NULL')
            );
        }

        /**
         * this is for support UTF-8 with BOM encoding
         * @see https://en.wikipedia.org/wiki/Byte_order_mark
         * @see https://github.com/vlucas/phpdotenv/issues/500
         */
        if (\substr($converted, 0, 3) == "\xEF\xBB\xBF") {
            $converted = \substr($converted, 3);
        }

        require_once 'GrahamCampbell/ResultType/Success.php';
        /** @var \GrahamCampbell\ResultType\Result<string, string> */
        return Success::create($converted);
    }

    /**
     * Search for a given substring of the input.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return \PhpOption\Option<int>
     */
    public static function pos(string $haystack, string $needle)
    {
        if (!is_callable('mb_strpos')) {
            require_once self::R_SYMFONY_POLYFILL_MBSTRING_BOOTSTRAP;
        }
        /** @var \PhpOption\Option<int> */
        return Option::fromValue(mb_strpos($haystack, $needle, 0, 'UTF-8'), false);
    }

    /**
     * Grab the specified substring of the input.
     *
     * @param string   $input
     * @param int      $start
     * @param int|null $length
     *
     * @return string
     */
    public static function substr(string $input, int $start, ?int $length = null)
    {
        if (!is_callable('mb_substr')) {
            require_once self::R_SYMFONY_POLYFILL_MBSTRING_BOOTSTRAP;
        }
        return mb_substr($input, $start, $length, 'UTF-8');
    }

    /**
     * Compute the length of the given string.
     *
     * @param string $input
     *
     * @return int
     */
    public static function len(string $input)
    {
        if (!is_callable('mb_strlen')) {
            require_once self::R_SYMFONY_POLYFILL_MBSTRING_BOOTSTRAP;
        }
        return mb_strlen($input, 'UTF-8');
    }
}
