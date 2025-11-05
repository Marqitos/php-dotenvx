<?php
/**
 * This file is part of the Rodas\Doventx library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Dotenvx\Adapter;

use Dotenv\Repository\Adapter\AdapterInterface;
use PhpOption\{ None, Option, Some};

use function array_merge_recursive;
use function count;
use function explode;

/**
 * Read or write de values on a multilevel array
 */
class ArrayMultiAdapter implements AdapterInterface {
    /**
     * The variables and their values.
     *
     * @var array<string, mixed>
     */
    private array $variables;
    /**
     * Char to split the name into keys
     *
     * @var string
     */
    public private(set) string $separator {
        get => $this->separator;
        set (string $value) {
            if (!empty($value)) {
                $this->separator = $value;
            }
        }
    }
    /**
     * Gets de stored values
     *
     * @var array<string, mixed>
     */
    public array $values {
        get => array_merge_recursive($this->variables);
    }

    public static string $defaultSeparator = '.';

    /**
     * Create a new array adapter instance.
     */
    public function __construct(string $separator) {
        $this->variables = [];
        if (empty($separator)) {
            $separator = self::$defaultSeparator;
        }
        $this->separator = $separator;
    }

    /**
     * Create a new instance of the adapter.
     *
     * @return \PhpOption\Option<\Dotenv\Repository\Adapter\AdapterInterface>
     */
    public static function create(): Some {
        /** @var \PhpOption\Option<AdapterInterface> */
        return Some::create(new self(self::$defaultSeparator));
    }

    /**
     * Read a variable from array, if it exists.
     *
     * @param non-empty-string $name
     *
     * @return \PhpOption\Option<string>
     */
    public function read(string $name) {
        $parts = explode($this->separator, $name);
        
        if (empty($parts)) {
            return None::create();
        }

        $value = $this->variables;
        foreach ($parts as $key) {
            if (!isset($value[$key])) {
                return None::create();
            }

            $value = $value[$key];
        }

        return Option::fromValue($value);
    }

    /**
     * Write to an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @param string           $value
     *
     * @return bool
     */
    public function write(string $name, string $value) {
        $parts = explode($this->separator, $name);

        $array = $this->variables;
        foreach ($parts as $key) {
            if (!isset($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        return true;
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param non-empty-string $name
     *
     * @return bool
     */
    public function delete(string $name) {
        $parts = explode($this->separator, $name);
        $count = count($parts);

        $depth = 0;
        $array = $this->variables;
        foreach ($parts as $key) {
            $depth++;
            if (!isset($array[$key])) {
                break;
            } elseif ($depth === $count) {
                unset($array[$key]);
            }

            $array = &$array[$key];
        }

        return true;
    }
}

