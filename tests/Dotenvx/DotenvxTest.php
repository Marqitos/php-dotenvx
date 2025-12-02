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
use Dotenv\Exception\InvalidPathException;
use Dotenv\Loader\Loader;
use Dotenv\Parser\Parser;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Store\StoreBuilder;
use PHPUnit\Framework\TestCase;
use Rodas\Dotenvx\Dotenvx;
use Rodas\Dotenvx\Adapter\ArrayAdapter;
use Rodas\Dotenvx\Adapter\ArrayMultiAdapter;
use Rodas\Test\Dotenvx\FakeDecrypt;

use function getenv;

require_once 'Dotenv/Exception/InvalidEncodingException.php';
require_once 'Dotenv/Exception/InvalidPathException.php';
require_once 'Dotenv/Loader/Loader.php';
require_once 'Dotenv/Parser/Parser.php';
require_once 'Dotenv/Repository/RepositoryBuilder.php';
require_once 'Dotenv/Store/StoreBuilder.php';
require_once 'Rodas/Dotenvx/Dotenvx.php';
require_once 'Rodas/Dotenvx/Adapter/ArrayMultiAdapter.php';
require_once 'Rodas/Dotenvx/Provider/StaticKeyProvider.php';
require_once __DIR__ . '/FakeDecrypt.php';

/**
 * Test class for Dotenvx
 *
 * @covers Rodas\Dotenvx\Dotenvx
 */
class DotenvxTest extends TestCase {

    const PATH = __DIR__ . '/../fixtures/env';
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

    /**
     * Test Dotenvx::loadEncrypted
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter
     */
    public function testLoadEncrypted() {
        $envFileExists      = file_exists(self::PATH . '/.env');
        $arrayAdapter       = new ArrayAdapter();
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenvx::create($repository, self::PATH)->loadEncrypted([FakeDecrypt::class, 'decrypt']);

            // Validate values
            $options            = $arrayAdapter->values;
            $this->assertEquals('pdo_mysql' , $options['DB_DRIVER']);
            $this->assertEquals('localhost' , $options['DB_HOST']);
            $this->assertEquals('3306'      , $options['DB_PORT']);
            $this->assertEquals('username'  , $options['DB_USER']);
            $this->assertEquals('pa$$w0rd'  , $options['DB_PASSWORD']);
            $this->assertEquals('utf8mb4'   , $options['DB_CHARSET']);
        }
    }

    /**
     * Test Dotenvx::loadEncrypted
     *
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter
     */
    public function testLoadEncryptedMulti() {
        $envFile            = 'multilevel.env';
        $envFileExists      = file_exists(self::PATH . '/' . $envFile);
        $arrayAdapter       = new ArrayMultiAdapter('.');
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenvx::create($repository, self::PATH, $envFile)->loadEncrypted([FakeDecrypt::class, 'decrypt']);

            // Validate values
            $options            = $arrayAdapter->values;
            $this->assertEquals('pdo_mysql' , $options['DB']['DRIVER']);
            $this->assertEquals('localhost' , $options['DB']['HOST']);
            $this->assertEquals('3306'      , $options['DB']['PORT']);
            $this->assertEquals('username'  , $options['DB']['USER']);
            $this->assertEquals('pa$$w0rd'  , $options['DB']['PASSWORD']);
            $this->assertEquals('utf8mb4'   , $options['DB']['CHARSET']);
            $this->assertTrue(in_array('primary', $options['DB']));
        }
    }

    public function testDotenvThrowsExceptionIfUnableToLoadFile() {
        $dotenv = Dotenvx::createMutable(__DIR__);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Unable to read any of the environment file(s) at');

        $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt']);
    }

    public function testDotenvThrowsExceptionIfUnableToLoadFiles() {
        $dotenv = Dotenvx::createMutable([__DIR__, __DIR__.'/foo/bar']);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Unable to read any of the environment file(s) at');

        $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt']);
    }

    public function testDotenvThrowsExceptionWhenNoFiles() {
        $dotenv = Dotenvx::createMutable([]);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('At least one environment file path must be provided.');

        $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt']);
    }

    public function testDotenvTriesPathsToLoad() {
        $dotenv = Dotenvx::createMutable([__DIR__, self::PATH]);
        $this->assertCount(9, $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt']));
        $this->assertEquals('pdo_mysql'     , $_SERVER['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_SERVER['DB_HOST']);
        $this->assertEquals('3306'          , $_SERVER['DB_PORT']);
        $this->assertEquals('username'      , $_SERVER['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_SERVER['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_SERVER['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvTriesPathsToLoadTwice() {
        $dotenv = Dotenvx::createMutable([__DIR__, self::PATH]);
        $this->assertCount(9, $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt']));

        $dotenv = Dotenvx::createImmutable([__DIR__, self::PATH]);
        $this->assertCount(0, $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt']));
    }

    public function testDotenvTriesPathsToSafeLoad() {
        $dotenv = Dotenvx::createMutable([__DIR__, self::PATH]);
        $this->assertCount(9, $dotenv->safeLoadEncrypted([FakeDecrypt::class, 'decrypt']));
        $this->assertEquals('pdo_mysql'     , $_SERVER['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_SERVER['DB_HOST']);
        $this->assertEquals('3306'          , $_SERVER['DB_PORT']);
        $this->assertEquals('username'      , $_SERVER['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_SERVER['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_SERVER['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvSkipsLoadingIfFileIsMissing() {
        $dotenv = Dotenvx::createMutable(__DIR__);
        $this->assertSame([], $dotenv->safeLoad());
    }

    public function testDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable(self::PATH);
        $this->assertSame(
            self::DEFAULT,
            $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt'])
        );
        $this->assertEquals('pdo_mysql'     , $_SERVER['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_SERVER['DB_HOST']);
        $this->assertEquals('3306'          , $_SERVER['DB_PORT']);
        $this->assertEquals('username'      , $_SERVER['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_SERVER['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_SERVER['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsEnvironmentVarsMultipleWithShortCircuitMode() {
        $dotenv = Dotenvx::createMutable(self::PATH, ['.env', 'example.env']);

        $this->assertSame(
            self::DEFAULT,
            $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt'])
        );
    }

    public function testDotenvLoadsEnvironmentVarsMultipleWithoutShortCircuitMode() {
        $dotenv = Dotenvx::createMutable(self::PATH, ['.env', 'example.env'], false);

        $this->assertSame(
            self::DEFAULT + ['EG' => 'example'],
            $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt'])
        );
    }

    public function testCommentedDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable(self::PATH, 'commented.env');
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['CFOO']);
        $this->assertFalse(isset($_SERVER['CBAR']));
        $this->assertFalse(isset($_SERVER['CZOO']));
        $this->assertSame('with spaces', $_SERVER['CSPACED']);
        $this->assertSame('a value with a # character', $_SERVER['CQUOTES']);
        $this->assertSame('a value with a # character & a quote " character inside quotes', $_SERVER['CQUOTESWITHQUOTE']);
        $this->assertEmpty($_SERVER['CNULL']);
        $this->assertEmpty($_SERVER['EMPTY']);
        $this->assertEmpty($_SERVER['EMPTY2']);
        $this->assertSame('foo', $_SERVER['FOOO']);
    }

    public function testQuotedDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable(self::PATH, 'quoted.env');
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
        $dotenv = Dotenvx::createMutable(self::PATH, 'large.env');
        $dotenv->load();
        $this->assertSame(2730, \strlen($_SERVER['LARGE']));
        $this->assertSame(8192, \strlen($_SERVER['HUGE']));
    }

    public function testDotenvLoadsMultibyteVars() {
        $dotenv = Dotenvx::createMutable(self::PATH, 'multibyte.env');
        $dotenv->load();
        $this->assertSame('Ā ā Ă ă Ą ą Ć ć Ĉ ĉ Ċ ċ Č č Ď ď Đ đ Ē ē Ĕ ĕ Ė ė Ę ę Ě ě', $_SERVER['MB1']);
        $this->assertSame('行内支付', $_SERVER['MB2']);
        $this->assertSame('🚀', $_SERVER['APP_ENV']);
    }

    public function testDotenvLoadsMultibyteUTF8Vars() {
        $dotenv = Dotenvx::createMutable(self::PATH, 'multibyte.env', false, 'UTF-8');
        $dotenv->load();
        $this->assertSame('Ā ā Ă ă Ą ą Ć ć Ĉ ĉ Ċ ċ Č č Ď ď Đ đ Ē ē Ĕ ĕ Ė ė Ę ę Ě ě', $_SERVER['MB1']);
        $this->assertSame('行内支付', $_SERVER['MB2']);
        $this->assertSame('🚀', $_SERVER['APP_ENV']);
    }

    public function testDotenvLoadWithInvalidEncoding() {
        $dotenv = Dotenvx::createMutable(self::PATH, 'multibyte.env', false, 'UTF-88');

        $this->expectException(InvalidEncodingException::class);
        $this->expectExceptionMessage('Illegal character encoding [UTF-88] specified.');

        $dotenv->load();
    }

    public function testDotenvLoadsMultibyteWindowsVars() {
        $dotenv = Dotenvx::createMutable(self::PATH, 'windows.env', false, 'Windows-1252');
        $dotenv->load();
        $this->assertSame('ñá', $_SERVER['MBW']);
    }

    public function testMultipleDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable(self::PATH, 'multiple.env');
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['MULTI1']);
        $this->assertSame('foo', $_SERVER['MULTI2']);
    }

    public function testExportedDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable(self::PATH, 'exported.env');
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['EFOO']);
        $this->assertSame('baz', $_SERVER['EBAR']);
        $this->assertSame('with spaces', $_SERVER['ESPACED']);
        $this->assertSame('123', $_SERVER['EDQUOTED']);
        $this->assertSame('456', $_SERVER['ESQUOTED']);
        $this->assertEmpty($_SERVER['ENULL']);
    }

    public function testDotenvLoadsEnvGlobals() {
        $dotenv = Dotenvx::createMutable(self::PATH);
        $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt']);
        $this->assertEquals('pdo_mysql'     , $_SERVER['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_SERVER['DB_HOST']);
        $this->assertEquals('3306'          , $_SERVER['DB_PORT']);
        $this->assertEquals('username'      , $_SERVER['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_SERVER['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_SERVER['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals() {
        $dotenv = Dotenvx::createMutable(self::PATH);
        $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt']);
        $this->assertEquals('pdo_mysql'     , $_ENV['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_ENV['DB_HOST']);
        $this->assertEquals('3306'          , $_ENV['DB_PORT']);
        $this->assertEquals('username'      , $_ENV['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_ENV['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_ENV['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_ENV['SPACED']);
        $this->assertEmpty($_ENV['NULL']);
    }

    public function testDotenvNestedEnvironmentVars() {
        $dotenv = Dotenvx::createMutable(self::PATH, 'nested.env');
        $dotenv->load();
        $this->assertSame('{$NVAR1} {$NVAR2}', $_ENV['NVAR3']); // not resolved
        $this->assertSame('Hellō World!', $_ENV['NVAR4']);
        $this->assertSame('$NVAR1 {NVAR2}', $_ENV['NVAR5']); // not resolved
        $this->assertSame('Special Value', $_ENV['N.VAR6']); // new '.' (dot) in var name
        $this->assertSame('Special Value', $_ENV['NVAR7']);  // nested '.' (dot) variable
        $this->assertSame('', $_ENV['NVAR8']); // nested variable is empty string
        $this->assertSame('', $_ENV['NVAR9']); // nested variable is empty string
        $this->assertSame('${NVAR888}', $_ENV['NVAR10']); // nested variable is not set
        $this->assertSame('NVAR1', $_ENV['NVAR11']);
        $this->assertSame('Hellō', $_ENV['NVAR12']);
        $this->assertSame('${${NVAR11}}', $_ENV['NVAR13']); // single quotes
        $this->assertSame('${NVAR1} ${NVAR2}', $_ENV['NVAR14']); // single quotes
        $this->assertSame('${NVAR1} ${NVAR2}', $_ENV['NVAR15']); // escaped
    }

    public function testDotenvNullFileArgumentUsesDefault() {
        $dotenv = Dotenvx::createMutable(self::PATH, null);
        $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt']);
        $this->assertEquals('pdo_mysql'     , $_SERVER['DB_DRIVER']);
        $this->assertEquals('localhost'     , $_SERVER['DB_HOST']);
        $this->assertEquals('3306'          , $_SERVER['DB_PORT']);
        $this->assertEquals('username'      , $_SERVER['DB_USER']);
        $this->assertEquals('pa$$w0rd'      , $_SERVER['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'       , $_SERVER['DB_CHARSET']);
        $this->assertEquals('with spaces'   , $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    /**
     * The fixture data has whitespace between the key and in the value string.
     *
     * Test that these keys are trimmed down.
     */
    public function testDotenvTrimmedKeys() {
        $dotenv = Dotenvx::createMutable(self::PATH, 'quoted.env');
        $dotenv->load();
        $this->assertSame('no space', $_SERVER['QWHITESPACE']);
    }

    public function testDotenvLoadDoesNotOverwriteEnv() {
        \putenv('IMMUTABLE=true');
        $dotenv = Dotenvx::createImmutable(self::PATH, 'immutable.env');
        $dotenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testEmptyLoading() {
        $dotenv = Dotenvx::createImmutable(self::PATH, 'empty.env');
        $this->assertSame(['EMPTY_VAR' => null], $dotenv->load());
    }

    public function testUnicodeVarNames() {
        $dotenv = Dotenvx::createImmutable(self::PATH, 'unicodevarnames.env');
        $dotenv->load();
        $this->assertSame('Skybert', $_SERVER['AlbertÅberg']);
        $this->assertSame('2022-04-01T00:00', $_SERVER['ДатаЗакрытияРасчетногоПериода']);
    }

    public function testDirectConstructor() {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->make();
        $store = StoreBuilder::createWithDefaultName()->addPath(self::PATH)->make();

        $dotenv = new Dotenvx($store, new Parser(), new Loader(), $repository);

        $this->assertSame(
            self::DEFAULT,
            $dotenv->loadEncrypted([FakeDecrypt::class, 'decrypt'])
        );
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
