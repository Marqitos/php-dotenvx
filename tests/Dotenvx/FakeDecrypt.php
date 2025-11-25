<?php
/**
 * This file is part of the Rodas\Doventx library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Rodas\Doventx
 * @subpackage Test
 * @copyright 2025 Marcos Porto <php@marcospor.to>
 * @license https://opensource.org/license/bsd-3-clause BSD-3-Clause
 * @link https://marcospor.to/repositories/dotenvx
 */

declare(strict_types=1);

namespace Rodas\Test\Dotenvx;

use Exception;

class FakeDecrypt {

    private static array $data = [
        'Ek1Krd8QRcG2B20p1iwM6IHgUVGHyCcudqjqoAgqMQA=' => [
            'kpOFCd76bsEMvgk7iJ1a7oHbQdGITAMAtUppEIBgRmUjinhWoxaKJD9Xz1SqKEwSGAlnuWhXksv1'  => 'localhost',
            'k4hknNltlTjry3LFPsM3dtHkQdfJhWRRCK+X21JE6xAjg0xI3bT3rSXfJ9rdesIXWxYFzw=='      => '3306',
            'XZA6xt1uXF1OdDrROuvC5+zVD/3OwXaj9dgPGdkF0QFUaNfCFTcCsmJl7V5e9I7w39egprAOXJg='  => 'username',
            'iRJUQ3XaVQnhsUfea2i1NgZWb593oWXhjksHDeC2yzZFPKTsU7UC+D/vxDksSkDFff12oAqzVXk='  => 'pa$$w0rd'
        ]
    ];

    public static function decrypt(string $publicKey, $encryptedValues) {
        $decryptedValues = [];
        foreach ($encryptedValues as $encryptedValue) {
            if (isset(self::$data[$publicKey][$encryptedValue])) {
                $decryptedValues[$encryptedValue] = self::$data[$publicKey][$encryptedValue];
            } else {
                throw new Exception("Desencriptado fallido");
            }
        }
        return $decryptedValues;
    }
}