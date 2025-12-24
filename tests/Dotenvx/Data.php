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

use PHPUnit\Framework\TestCase;

use function realpath;

/**
 * Base class for test and fixtures constants
 */
abstract class Data extends TestCase {

    protected string $path {
        get => realpath(__DIR__ . '/../fixtures/env');
    }

    protected array $data {
        get {
            if (!isset($this->data)) {

                $this->data = include __DIR__ . '/../fixtures/data.php';
            }
            return $this->data;
        }
    }
}
