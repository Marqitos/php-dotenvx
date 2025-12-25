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

namespace Rodas\Test\Dotenvx\Middleware;

use Dotenv\Loader\Loader;
use Dotenv\Parser\Parser;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Store\StoreBuilder;
use Rodas\Dotenvx\Dotenvx;
use Rodas\Dotenvx\Adapter\ArrayAdapter;
use Rodas\Dotenvx\Adapter\ArrayMultiAdapter;
use Rodas\Dotenvx\Middleware\DecryptorMiddleware;
use Rodas\Test\Dotenvx\Data;
use Rodas\Test\Dotenvx\FakeDecrypt;

use function array_flip;
use function array_intersect_key;
use function file_exists;
use function in_array;

class DecryptorMiddlewareTest extends Data {

    protected array $default {
        get {
            if (!isset($this->default)) {

                $flip = array_flip([
                    'DOTENV_PUBLIC_KEY',
                    'DB_DRIVER',
                    'DB_HOST',
                    'DB_PORT',
                    'DB_USER',
                    'DB_PASSWORD',
                    'DB_CHARSET',
                    'SPACED',
                    'NULL']);
                $this->default = array_intersect_key($this->data, $flip);
            }
            return $this->default;
        }
    }

    /**
     * Test Dotenvx::load with DecryptorMiddleware and ArrayAdapter
     *
     * @covers Rodas\Dotenvx\Dotenvx::load
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter
     * @covers Rodas\Dotenvx\Middleware\DecryptorMiddleware
     */
    public function testLoadEncrypted() {
        $this->assertTrue(file_exists($this->path . '/.env'));
        $arrayAdapter       = new ArrayAdapter();
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            $middleware         = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
            Dotenvx::create($repository, $this->path)
                ->addMiddleware($middleware)
                ->load();

            // Validate values
            $options            = $arrayAdapter->values;
            foreach(['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD', 'DB_CHARSET'] as $key) {
                $this->assertEquals($this->data[$key], $options[$key]);
            }
    }

    /**
     * Test Dotenvx::load with DecryptorMiddleware and ArrayMultiAdapter
     *
     * @covers Rodas\Dotenvx\Dotenvx::load
     * @covers Rodas\Dotenvx\Adapter\ArrayMultiAdapter
     * @covers Rodas\Dotenvx\Middleware\DecryptorMiddleware
     */
    public function testLoadEncryptedMulti() {
        $envFile            = 'multilevel.env';
        $this->assertTrue(file_exists($this->path . '/' . $envFile));
        $arrayAdapter       = new ArrayMultiAdapter('.');
        $repository         = RepositoryBuilder::createWithNoAdapters()
            ->addAdapter($arrayAdapter)
            ->make();
        $middleware         = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        Dotenvx::create($repository, $this->path, $envFile)
            ->addMiddleware($middleware)
            ->load();

        // Validate values
        $options            = $arrayAdapter->values;
        $this->assertEquals($this->data['DB_DRIVER']    , $options['DB']['DRIVER']);
        $this->assertEquals($this->data['DB_HOST']      , $options['DB']['HOST']);
        $this->assertEquals($this->data['DB_PORT']      , $options['DB']['PORT']);
        $this->assertEquals($this->data['DB_USER']      , $options['DB']['USER']);
        $this->assertEquals($this->data['DB_PASSWORD']  , $options['DB']['PASSWORD']);
        $this->assertEquals($this->data['DB_CHARSET']   , $options['DB']['CHARSET']);
        $this->assertTrue(in_array($this->data['DB']    , $options['DB']));
    }

    public function testDotenvTriesPathsToLoad() {
        $dotenv = Dotenvx::createMutable([__DIR__, $this->path]);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();

        $this->assertCount(9                , $options);
        foreach(['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD', 'DB_CHARSET', 'SPACED'] as $key) {
            $this->assertEquals($this->data[$key], $_SERVER[$key]);
        }
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvTriesPathsToLoadTwice() {
        $dotenv = Dotenvx::createMutable([__DIR__, $this->path]);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();
        $this->assertCount(9, $options);

        $dotenv     = Dotenvx::createImmutable([__DIR__, $this->path]);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();
        $this->assertCount(0, $options);
    }

    public function testDotenvTriesPathsToSafeLoad() {
        $dotenv = Dotenvx::createMutable([__DIR__, $this->path]);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->safeLoad();
        $this->assertCount(9                , $options);
        foreach(['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD', 'DB_CHARSET', 'SPACED'] as $key) {
            $this->assertEquals($this->data[$key], $_SERVER[$key]);
        }
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsEnvironmentVars() {
        $dotenv = Dotenvx::createMutable($this->path);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();

        $this->assertSame($this->default     , $options);
        foreach(['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD', 'DB_CHARSET', 'SPACED'] as $key) {
            $this->assertEquals($this->data[$key], $_SERVER[$key]);
        }
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsEnvironmentVarsMultipleWithShortCircuitMode() {
        $dotenv = Dotenvx::createMutable($this->path, ['.env', 'example.env']);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();

        $this->assertSame($this->default     , $options);
    }

    public function testDotenvLoadsEnvironmentVarsMultipleWithoutShortCircuitMode() {
        $dotenv = Dotenvx::createMutable($this->path, ['.env', 'example.env'], false);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();

        $this->assertSame(
            $this->default + ['EG' => 'example'],
            $options
        );
    }

    public function testDotenvLoadsEnvGlobals() {
        $dotenv = Dotenvx::createMutable($this->path);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $dotenv->load();

        foreach(['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD', 'DB_CHARSET', 'SPACED'] as $key) {
            $this->assertEquals($this->data[$key], $_SERVER[$key]);
        }
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals() {
        $dotenv = Dotenvx::createMutable($this->path);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $dotenv->load();

        foreach(['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD', 'DB_CHARSET', 'SPACED'] as $key) {
            $this->assertEquals($this->data[$key], $_ENV[$key]);
        }
        $this->assertEmpty($_ENV['NULL']);
    }

    public function testDotenvNullFileArgumentUsesDefault() {
        $dotenv = Dotenvx::createMutable($this->path, null);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $dotenv->load();

        foreach(['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_USER', 'DB_PASSWORD', 'DB_CHARSET', 'SPACED'] as $key) {
            $this->assertEquals($this->data[$key], $_SERVER[$key]);
        }
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDirectConstructor() {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->make();
        $store = StoreBuilder::createWithDefaultName()->addPath($this->path)->make();

        $dotenv = new Dotenvx($store, new Parser(), new Loader(), $repository);
        $middleware = new DecryptorMiddleware([FakeDecrypt::class, 'decrypt']);
        $dotenv->addMiddleware($middleware);
        $options    = $dotenv->load();

        $this->assertSame($this->default, $options);
    }

}
