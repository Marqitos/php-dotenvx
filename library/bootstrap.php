<?php
/**
 * This file is part of the Rodas\* libraries
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

// Add code libraries directory
set_include_path(
    realpath(__DIR__) . DIRECTORY_SEPARATOR . PATH_SEPARATOR .
    get_include_path()
);
