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

use PHPUnit\Framework\TestCase;
use Rodas\Dotenvx\Decryptor;

require_once __DIR__ . '/../../src/Dotenvx/Decryptor.php';

/**
 * Test class for Decryptor
 *
 * @covers Rodas\Dotenvx\Decryptor
 */
class DecryptorTest extends TestCase {

    const BASE64_REGEX = '/^([A-Za-z0-9+\/]{4})*([A-Za-z0-9+\/]{4}|[A-Za-z0-9+\/]{3}=|[A-Za-z0-9+\/]{2}==)$/';

    /**
     * Test Decryptor::createKeyPair and their results
     *
     * @covers Rodas\Dotenvx\Decryptor::createKeyPair
     */
    public function testCreateKeyPair() {
        list($privateKey, $publicKey) = Decryptor::createKeyPair();
        $privateKeyIsBase64 = preg_match(self::BASE64_REGEX, $privateKey) === 1;
        $publicKeyIsBase64 = preg_match(self::BASE64_REGEX, $publicKey) === 1;

        // Are strings
        $this->assertIsString($privateKey);
        $this->assertIsString($publicKey);
        // Not empty
        $this->assertNotEmpty($privateKey);
        $this->assertNotEmpty($publicKey);
        // As base64
        $this->assertTrue($privateKeyIsBase64);
        $this->assertTrue($publicKeyIsBase64);
    }

    /**
     * Test Decryptor::cryptoBase64Encode and cryptoBase64Decode
     *
     * @covers Rodas\Dotenvx\Decryptor::cryptoBase64Encode
     * @covers Rodas\Dotenvx\Decryptor::cryptoBase64Decode
     */
    public function testBase64() {
        $initialText = 'textToEncode:123456789+$ç=*';
        $base64 = Decryptor::cryptoBase64Encode($initialText);
        $isBase64 = preg_match(self::BASE64_REGEX, $base64) === 1;
        $finalText = Decryptor::cryptoBase64Decode($base64);

        $this->assertEquals($initialText, $finalText);
        $this->assertNotEmpty($base64);
        $this->assertTrue($isBase64);
    }

    /**
     * Test Decryptor::encrypt and encrypt
     *
     * @covers Rodas\Dotenvx\Decryptor::createKeyPair
     * @covers Rodas\Dotenvx\Decryptor::encrypt
     * @covers Rodas\Dotenvx\Decryptor::decrypt
     */
    public function testEncrypt() {
        $initialValue = "https://encryptor.marcospor.to";
        // Create key pairs
        list($privateKey, $publicKey) = Decryptor::createKeyPair();
        // Encrypt
        $encryptedValue = Decryptor::encrypt($initialValue, $publicKey);
        $encrypted      = substr($encryptedValue, 10);
        $isBase64       = preg_match(self::BASE64_REGEX, $encrypted) === 1;
        $isEncrypted    = substr($encryptedValue, 0, 10) === 'encrypted:' &&
            $isBase64;
        // Decrypt
        $finalValue     = Decryptor::decrypt($encryptedValue, $privateKey, $publicKey);

        $this->assertTrue($isBase64);
        $this->assertTrue($isEncrypted);
        $this->assertEquals($initialValue, $finalValue);
    }

}
