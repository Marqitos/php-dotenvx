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
use PhpOption\Option;
use PhpOption\Some;
use function count;
use function explode;

/**
 * Read or write de values on a multilevel array
 */
class ArrayMultiAdapter implements AdapterInterface {
    /**
     * The variables and their values.
     *
     * @var array<string, string>
     */
    private array $variables;

    private string $separator;

    public static string $defaultSeparator = '.';

    /**
     * Create a new array adapter instance.
     *
     * @return void
     */
    private function __construct(string $separator) {
        $this->variables = [];
        $this->separator = $separator;
    }

    /**
     * Create a new instance of the adapter.
     *
     * @return \PhpOption\Option<\Dotenv\Repository\Adapter\AdapterInterface>
     */
    public static function create() {
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

        $value = $this->variables;
        foreach ($parts as $key) {
            if (!isset($value[$key])) {
                Option::none();
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

