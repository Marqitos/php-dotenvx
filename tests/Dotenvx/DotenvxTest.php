<?php
/**
 * This file is part of the Rodas\Dotenvx library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Rodas\Dotenvx
 * @subpackage Test
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Test\Dotenvx;

use Dotenv\Exception\InvalidEncodingException;
use Rodas\Dotenvx\Dotenvx;

use function getenv;
use function strlen;

/**
 * Test class for Dotenvx
 *
 * @covers Rodas\Dotenvx\Dotenvx
 */
class DotenvxTest extends Data {

    const DEFAULT = [
        'DOTENV_PUBLIC_KEY' => 'Ek1Krd8QRcG2B20p1iwM6IHgUVGHyCcudqjqoAgqMQA=',
        'DB_DRIVER' => 'pdo_mysql',
        'DB_HOST' => 'localhost',
        'DB_PORT' => '3306',
        'DB_USER' => 'username',
        'DB_PASSWORD' => 'pa$$w0rd',
        'DB_CHARSET' => 'utf8mb4',
        'SPACED' => 'with spaces',
        'NULL' => '',
    ];

    public function testDotenvSkipsLoadingIfFileIsMissing() {
        $dotenv = Dotenvx::createMutable(__DIR__);
        $this->assertSame([], $dotenv->safeLoad());
    }

    public function testCommentedDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable($this->path, 'commented.env');
        $dotenv->load();

        $this->assertSame('bar', $_SERVER['CFOO']);
        $this->assertFalse(isset($_SERVER['CBAR']));
        $this->assertFalse(isset($_SERVER['CZOO']));
        $this->assertSame('with spaces', $_SERVER['CSPACED']);
        $this->assertSame('a value with a # character', $_SERVER['CQUOTES']);
        $this->assertSame('a value with a # character & a quote " character inside quotes', $_SERVER['CQUOTESWITHQUOTE']);
        $this->assertSame('foo', $_SERVER['FOOO']);
        $this->assertEmpty($_SERVER['CNULL']);
        $this->assertEmpty($_SERVER['EMPTY']);
        $this->assertEmpty($_SERVER['EMPTY2']);
    }

    public function testQuotedDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable($this->path, 'quoted.env');
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['QFOO']);
        $this->assertSame('baz', $_SERVER['QBAR']);
        $this->assertSame('with spaces', $_SERVER['QSPACED']);
        $this->assertEmpty(getenv('QNULL'));

        $this->assertSame('pgsql:host=localhost;dbname=test', $_SERVER['QEQUALS']);
        $this->assertSame('test some escaped characters like a quote (") or maybe a backslash (\\)', $_SERVER['QESCAPED']);
        $this->assertSame('iiiiviiiixiiiiviiii\\n', $_SERVER['QSLASH']);
        $this->assertSame('iiiiviiiixiiiiviiii\\\\n', $_SERVER['SQSLASH']);
    }

    public function testLargeDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable($this->path, 'large.env');
        $dotenv->load();
        $this->assertSame(2730, strlen($_SERVER['LARGE']));
        $this->assertSame(8192, strlen($_SERVER['HUGE']));
    }

    public function testDotenvLoadsMultibyteVars() {
        $dotenv = Dotenvx::createMutable($this->path, 'multibyte.env');
        $dotenv->load();
        $this->assertSame('Ā ā Ă ă Ą ą Ć ć Ĉ ĉ Ċ ċ Č č Ď ď Đ đ Ē ē Ĕ ĕ Ė ė Ę ę Ě ě', $_SERVER['MB1']);
        $this->assertSame('行内支付', $_SERVER['MB2']);
        $this->assertSame('🚀', $_SERVER['APP_ENV']);
    }

    public function testDotenvLoadsMultibyteUTF8Vars() {
        $dotenv = Dotenvx::createMutable($this->path, 'multibyte.env', false, 'UTF-8');
        $dotenv->load();
        $this->assertSame('Ā ā Ă ă Ą ą Ć ć Ĉ ĉ Ċ ċ Č č Ď ď Đ đ Ē ē Ĕ ĕ Ė ė Ę ę Ě ě', $_SERVER['MB1']);
        $this->assertSame('行内支付', $_SERVER['MB2']);
        $this->assertSame('🚀', $_SERVER['APP_ENV']);
    }

    public function testDotenvLoadWithInvalidEncoding() {
        $dotenv = Dotenvx::createMutable($this->path, 'multibyte.env', false, 'UTF-88');

        $this->expectException(InvalidEncodingException::class);
        $this->expectExceptionMessage('Illegal character encoding [UTF-88] specified.');

        $dotenv->load();
    }

    public function testDotenvLoadsMultibyteWindowsVars() {
        $dotenv = Dotenvx::createMutable($this->path, 'windows.env', false, 'Windows-1252');
        $dotenv->load();
        $this->assertSame('ñá', $_SERVER['MBW']);
    }

    public function testMultipleDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable($this->path, 'multiple.env');
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['MULTI1']);
        $this->assertSame('foo', $_SERVER['MULTI2']);
    }

    public function testExportedDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable($this->path, 'exported.env');
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['EFOO']);
        $this->assertSame('baz', $_SERVER['EBAR']);
        $this->assertSame('with spaces', $_SERVER['ESPACED']);
        $this->assertSame('123', $_SERVER['EDQUOTED']);
        $this->assertSame('456', $_SERVER['ESQUOTED']);
        $this->assertEmpty($_SERVER['ENULL']);
    }

    public function testDotenvNestedEnvironmentVars() {
        $dotenv = Dotenvx::createMutable($this->path, 'nested.env');
        $dotenv->load();
        $this->assertSame('{$NVAR1} {$NVAR2}', $_ENV['NVAR3']); // not resolved
        $this->assertSame('Hellō World!', $_ENV['NVAR4']);
        $this->assertSame('$NVAR1 {NVAR2}', $_ENV['NVAR5']); // not resolved
        $this->assertSame('Special Value', $_ENV['N.VAR6']); // new '.' (dot) in var name
        $this->assertSame('Special Value', $_ENV['NVAR7']);  // nested '.' (dot) variable
        $this->assertSame('${NVAR888}', $_ENV['NVAR10']); // nested variable is not set
        $this->assertSame('NVAR1', $_ENV['NVAR11']);
        $this->assertSame('Hellō', $_ENV['NVAR12']);
        $this->assertSame('${${NVAR11}}', $_ENV['NVAR13']); // single quotes
        $this->assertSame('${NVAR1} ${NVAR2}', $_ENV['NVAR14']); // single quotes
        $this->assertSame('${NVAR1} ${NVAR2}', $_ENV['NVAR15']); // escaped
        $this->assertEmpty($_ENV['NVAR8']); // nested variable is empty string
        $this->assertEmpty($_ENV['NVAR9']); // nested variable is empty string
    }

    /**
     * The fixture data has whitespace between the key and in the value string.
     *
     * Test that these keys are trimmed down.
     */
    public function testDotenvTrimmedKeys() {
        $dotenv = Dotenvx::createMutable($this->path, 'quoted.env');
        $dotenv->load();
        $this->assertSame('no space', $_SERVER['QWHITESPACE']);
    }

    public function testDotenvLoadDoesNotOverwriteEnv() {
        \putenv('IMMUTABLE=true');
        $dotenv = Dotenvx::createImmutable($this->path, 'immutable.env');
        $dotenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testEmptyLoading() {
        $dotenv = Dotenvx::createImmutable($this->path, 'empty.env');
        $this->assertSame(['EMPTY_VAR' => null], $dotenv->load());
    }

    public function testUnicodeVarNames() {
        $dotenv = Dotenvx::createImmutable($this->path, 'unicodevarnames.env');
        $dotenv->load();
        $this->assertSame('Skybert', $_SERVER['AlbertÅberg']);
        $this->assertSame('2022-04-01T00:00', $_SERVER['ДатаЗакрытияРасчетногоПериода']);
    }

    public function testDotenvParseExample1() {
        $output = Dotenvx::parse(
            "BASE_DIR=\"/var/webroot/project-root\"\nCACHE_DIR=\"\${BASE_DIR}/cache\"\nTMP_DIR=\"\${BASE_DIR}/tmp\"\n"
        );

        $this->assertSame($output, [
            'BASE_DIR'  => '/var/webroot/project-root',
            'CACHE_DIR' => '/var/webroot/project-root/cache',
            'TMP_DIR'   => '/var/webroot/project-root/tmp',
        ]);
    }

    public function testDotenvParseExample2() {
        $output = Dotenvx::parse("FOO=Bar\nBAZ=\"Hello \${FOO}\"");

        $this->assertSame($output, ['FOO' => 'Bar', 'BAZ' => 'Hello Bar']);
    }

    public function testDotenvParseEmptyCase() {
        $output = Dotenvx::parse('');

        $this->assertSame($output, []);
    }
}
