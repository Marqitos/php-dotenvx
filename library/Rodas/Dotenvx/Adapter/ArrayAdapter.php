<?php
/**
 * This file is part of the Rodas\Doventx library
 *
 * Based on Dotenv\Repository\Adapter\ArrayAdapter.php
 * vlucas/phpdotenv from Vance Lucas and Graham Campbell.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Rodas\Doventx
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Dotenvx\Adapter;

use Dotenv\Repository\Adapter\AdapterInterface;
use PhpOption\Option;
use PhpOption\Some;

require_once 'Dotenv/Repository/Adapter/AdapterInterface.php';

class ArrayAdapter implements AdapterInterface {
# Fields
    /**
     * The variables and their values.
     *
     * @var array<string, string>
     */
    private array $variables;
# -- Fields

# Properties
    /**
     * Gets de stored values
     *
     * @var array<string, mixed>
     */
    public array $values {
        get => $this->variables;
    }
# -- Properties

# Constructor
    /**
     * Create a new array adapter instance.
     */
    public function __construct() {
        $this->variables = [];
    }
# -- Constructor

# Methods
    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return \PhpOption\Option<\Dotenv\Repository\Adapter\AdapterInterface>
     */
    public static function create(): Some {
        require_once 'PhpOption/Some.php';
        /** @var \PhpOption\Option<AdapterInterface> */
        return Some::create(new self());
    }

    /**
     * Read an environment variable, if it exists.
     *
     * @param non-empty-string $name
     *
     * @return \PhpOption\Option<string>
     */
    public function read(string $name) {
        require_once 'PhpOption/Option.php';
        return Option::fromArraysValue($this->variables, $name);
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
        $this->variables[$name] = $value;

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
        unset($this->variables[$name]);

        return true;
    }
# -- Methods
}
