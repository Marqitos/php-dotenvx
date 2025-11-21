<?php

/*
 * This file is based on bootstrap.php, part of the Symfony package,
 * symfony/polyfill-ctype from Fabien Potencier <fabien@symfony.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Symfony
 * @subpackage Symfony\Polyfill\Ctype
 * @license https://opensource.org/license/mit The MIT License
 */

use Symfony\Polyfill\Iconv;

if (extension_loaded('iconv')) {
    return;
}

if (!defined('ICONV_IMPL')) {
    define('ICONV_IMPL', 'Symfony');
}
if (!defined('ICONV_VERSION')) {
    define('ICONV_VERSION', '1.0');
}
if (!defined('ICONV_MIME_DECODE_STRICT')) {
    define('ICONV_MIME_DECODE_STRICT', 1);
}
if (!defined('ICONV_MIME_DECODE_CONTINUE_ON_ERROR')) {
    define('ICONV_MIME_DECODE_CONTINUE_ON_ERROR', 2);
}

if (\PHP_VERSION_ID >= 80000) {
    return require_once __DIR__.'/bootstrap80.php';
}

require_once __DIR__ . '/../Iconv.php';

if (!function_exists('iconv')) {
    function iconv($from_encoding, $to_encoding, $string) { return Iconv::iconv($from_encoding, $to_encoding, $string); }
}
if (!function_exists('iconv_get_encoding')) {
    function iconv_get_encoding($type = 'all') { return Iconv::iconv_get_encoding($type); }
}
if (!function_exists('iconv_set_encoding')) {
    function iconv_set_encoding($type, $encoding) { return Iconv::iconv_set_encoding($type, $encoding); }
}
if (!function_exists('iconv_mime_encode')) {
    function iconv_mime_encode($field_name, $field_value, $options = []) { return Iconv::iconv_mime_encode($field_name, $field_value, $options); }
}
if (!function_exists('iconv_mime_decode_headers')) {
    function iconv_mime_decode_headers($headers, $mode = 0, $encoding = null) { return Iconv::iconv_mime_decode_headers($headers, $mode, $encoding); }
}

if (extension_loaded('mbstring')) {
    if (!function_exists('iconv_strlen')) {
        function iconv_strlen($string, $encoding = null) { null === $encoding && $encoding = Iconv::$internalEncoding; return mb_strlen($string, $encoding); }
    }
    if (!function_exists('iconv_strpos')) {
        function iconv_strpos($haystack, $needle, $offset = 0, $encoding = null) { null === $encoding && $encoding = Iconv::$internalEncoding; return mb_strpos($haystack, $needle, $offset, $encoding); }
    }
    if (!function_exists('iconv_strrpos')) {
        function iconv_strrpos($haystack, $needle, $encoding = null) { null === $encoding && $encoding = Iconv::$internalEncoding; return mb_strrpos($haystack, $needle, 0, $encoding); }
    }
    if (!function_exists('iconv_substr')) {
        function iconv_substr($string, $offset, $length = 2147483647, $encoding = null) { null === $encoding && $encoding = Iconv::$internalEncoding; return mb_substr($string, $offset, $length, $encoding); }
    }
    if (!function_exists('iconv_mime_decode')) {
        function iconv_mime_decode($string, $mode = 0, $encoding = null) { $currentMbEncoding = mb_internal_encoding(); null === $encoding && $encoding = Iconv::$internalEncoding; mb_internal_encoding($encoding); $decoded = mb_decode_mimeheader($string); mb_internal_encoding($currentMbEncoding); return $decoded; }
    }
} else {
    if (!function_exists('iconv_strlen')) {
        function iconv_strlen($string, $encoding = null) { return Iconv::iconv_strlen($string, $encoding); }
    }

    if (!function_exists('iconv_strpos')) {
        function iconv_strpos($haystack, $needle, $offset = 0, $encoding = null) { return Iconv::iconv_strpos($haystack, $needle, $offset, $encoding); }
    }
    if (!function_exists('iconv_strrpos')) {
        function iconv_strrpos($haystack, $needle, $encoding = null) { return Iconv::iconv_strrpos($haystack, $needle, $encoding); }
    }
    if (!function_exists('iconv_substr')) {
        function iconv_substr($string, $offset, $length = 2147483647, $encoding = null) { return Iconv::iconv_substr($string, $offset, $length, $encoding); }
    }
    if (!function_exists('iconv_mime_decode')) {
        function iconv_mime_decode($string, $mode = 0, $encoding = null) { return Iconv::iconv_mime_decode($string, $mode, $encoding); }
    }
}
