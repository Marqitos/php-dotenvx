<?php
/**
 * This file is part of the Rodas\Dotenvx library
 *
 * Based on Dotenv\Dotenv.php
 * vlucas/phpdotenv from Vance Lucas and Graham Campbell.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Rodas\Dotenvx
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Dotenvx;

use Dotenv\Exception\InvalidPathException;
use Dotenv\Loader\Loader;
use Dotenv\Loader\LoaderInterface;
use Dotenv\Parser\Parser;
use Dotenv\Parser\ParserInterface;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Store\StoreBuilder;
use Dotenv\Store\StoreInterface;
use Dotenv\Store\StringStore;
use Rodas\Dotenvx\Adapter\ArrayAdapter;
use Rodas\Dotenvx\Parser\EntriesExtensions;

use function call_user_func;
use function is_string;

class Dotenvx {

    /**
     * The store instance.
     *
     * @var \Dotenv\Store\StoreInterface
     */
    protected $store;

    /**
     * The parser instance.
     *
     * @var \Dotenv\Parser\ParserInterface
     */
    protected $parser;

    /**
     * The loader instance.
     *
     * @var \Dotenv\Loader\LoaderInterface
     */
    protected $loader;

    /**
     * The repository instance.
     *
     * @var \Dotenv\Repository\RepositoryInterface
     */
    protected $repository;

    /**
     * Create a new dotenvx instance.
     *
     * @param \Dotenv\Store\StoreInterface           $store
     * @param \Dotenv\Parser\ParserInterface         $parser
     * @param \Dotenv\Loader\LoaderInterface         $loader
     * @param \Dotenv\Repository\RepositoryInterface $repository
     */
    public function __construct(
        StoreInterface $store,
        ParserInterface $parser,
        LoaderInterface $loader,
        RepositoryInterface $repository
    ) {
        $this->store = $store;
        $this->parser = $parser;
        $this->loader = $loader;
        $this->repository = $repository;
    }

    /**
     * Create a new dotenvx instance.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string|string[]                        $paths
     * @param string|string[]|null                   $names
     * @param bool                                   $shortCircuit
     * @param string|null                            $fileEncoding
     *
     * @return Rodas\Dotenvx\Dotenvx
     */
    public static function create(RepositoryInterface $repository, $paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null) {
        $builder = $names === null ? StoreBuilder::createWithDefaultName() : StoreBuilder::createWithNoNames();

        foreach ((array) $paths as $path) {
            $builder = $builder->addPath($path);
        }

        foreach ((array) $names as $name) {
            $builder = $builder->addName($name);
        }

        if ($shortCircuit) {
            $builder = $builder->shortCircuit();
        }

        return new static($builder->fileEncoding($fileEncoding)->make(), new Parser(), new Loader(), $repository);
    }

    /**
     * Create a new mutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return Rodas\Dotenvx\Dotenvx
     */
    public static function createMutable($paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null) {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->make();

        return static::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new immutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return Rodas\Dotenvx\Dotenvx
     */
    public static function createImmutable($paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null) {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

        return static::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new dotenv instance with an array backed repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return Rodas\Dotenvx\Dotenvx
     */
    public static function createArrayBacked($paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null) {
        $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();

        return static::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Parse the given content and resolve nested variables.
     *
     * This method behaves just like load(), only without mutating your actual
     * environment. We do this by using an array backed repository.
     *
     * @param string $content
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string, string|null>
     */
    public static function parse(string $content): array {
        $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();

        $phpdotenv = new static(new StringStore($content), new Parser(), new Loader(), $repository);

        return $phpdotenv->load();
    }

    /**
     * Read and load environment file(s).
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidEncodingException|\Dotenv\Exception\InvalidFileException
     *
     * @return array<string, string|null>
     */
    public function load(): array {
        $entries = $this->parser->parse($this->store->read());

        return $this->loader->load($this->repository, $entries);
    }

    /**
     * Read, decrypt and load environment file(s).
     *
     * @param  callable $decryptor Callable with the signature `function(string $publicKey, array $encryptedValues): array`
     * @return array<string, string|null>
     */
    public function loadEncrypted(callable $decryptor): array {
        $entries = $this->parser->parse($this->store->read());
        $publicKey = EntriesExtensions::isEncrypted($entries);

        if (is_string($publicKey)) {
            $encryptedValues    = EntriesExtensions::getEncryptedValues($entries);
            $decryptedValues    = call_user_func($decryptor, $publicKey, $encryptedValues);
            $entries            = EntriesExtensions::replaceEncryptedValues($entries, $decryptedValues);
        }

        return $this->loader->load($this->repository, $entries);
    }

    /**
     * Read and load environment file(s), silently failing if no files can be read.
     *
     * @throws \Dotenv\Exception\InvalidEncodingException|\Dotenv\Exception\InvalidFileException
     *
     * @return array<string, string|null>
     */
    public function safeLoad(): array {
        try {
            return $this->load();
        } catch (InvalidPathException $e) {
            // suppressing exception
            return [];
        }
    }

    /**
     * Read, decrypt and load environment file(s), silently failing if no files can be read.
     *
     * @param  callable $decryptor Callable with the signature `function(string $publicKey, array $encryptedValues): array`
     * @throws \Dotenv\Exception\InvalidEncodingException|\Dotenv\Exception\InvalidFileException
     *
     * @return array<string, string|null>
     */
    public function safeLoadEncrypted(callable $decryptor): array {
        try {
            return $this->loadEncrypted($decryptor);
        } catch (InvalidPathException $e) {
            // suppressing exception
            return [];
        }
    }

    /**
     * Required ensures that the specified variables exist, and returns a new validator object.
     *
     * @param string|string[] $variables
     *
     * @return \Dotenv\Validator
     */
    public function required($variables) {
        return (new Validator($this->repository, (array) $variables))->required();
    }

    /**
     * Returns a new validator object that won't check if the specified variables exist.
     *
     * @param string|string[] $variables
     *
     * @return \Dotenv\Validator
     */
    public function ifPresent($variables) {
        return new Validator($this->repository, (array) $variables);
    }
}
