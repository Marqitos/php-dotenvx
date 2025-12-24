<?php
/**
 * This file is part of the Rodas\Dotenvx library
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

namespace Rodas\Dotenvx\Middleware;

interface MiddlewareInterface {
    /**
     * Process all entries
     *
     * @param Entry[] $entries The entries to process
     *
     * @return Entry[] The processed entries
     */
    public function process(array $entries): array;
}
