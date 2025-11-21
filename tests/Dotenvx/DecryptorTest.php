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

use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;
use PHPUnit\Framework\TestCase;
use Rodas\Dotenvx\Adapter\ArrayAdapter;
use Rodas\Dotenvx\Decryptor;
use Rodas\Dotenvx\Provider\StaticKeyProvider;

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
     * @covers Rodas\Dotenvx\Provider\StaticKeyProvider
     */
    public function testEncrypt() {
        $initialValue = "https://encryptor.marcospor.to";
        // Create key pairs
        list($privateKey, $publicKey) = Decryptor::createKeyPair();
        $keyProvider    = new StaticKeyProvider($publicKey, $privateKey);
        // Encrypt
        $encryptedValue = Decryptor::encrypt($initialValue, $publicKey);
        $encrypted      = substr($encryptedValue, 10);
        $isBase64       = preg_match(self::BASE64_REGEX, $encrypted) === 1;
        $isEncrypted    = substr($encryptedValue, 0, 10) === 'encrypted:' &&
            $isBase64;
        // Decrypt
        $finalValue     = Decryptor::decrypt($encryptedValue, $keyProvider);

        $this->assertTrue($isBase64);
        $this->assertTrue($isEncrypted);
        $this->assertEquals($initialValue, $finalValue);
    }

    /**
     * Test Decryptor::decryptArrayAdapter
     *
     * @covers Rodas\Dotenvx\Decryptor::decryptArrayAdapter
     * @covers Rodas\Dotenvx\Adapter\ArrayAdapter
     * @covers Rodas\Dotenvx\Provider\StaticKeyProvider
     */
    public function testDecryptArrayAdapter() {
        $path = __DIR__ . '/../fixtures/env';
        // Find private key
        $privateEnvKeyFile  = '.env.key';
        $privateKey         = false;
        $privateFileExists  = file_exists($path . '/' . $privateEnvKeyFile);
        $this->assertTrue($privateFileExists);
        if ($privateFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter(ArrayAdapter::class)
                ->make();
            $dotenv             = Dotenv::create($repository, $path, $privateEnvKeyFile);
            $privateData        = $dotenv->load();
            $containsPrivateKey = isset($privateData['DOTENV_PRIVATE_KEY']);
            $this->assertTrue($containsPrivateKey);
            if ($containsPrivateKey) {
                $privateKey     = $privateData['DOTENV_PRIVATE_KEY'];
            }
            unset($repository, $dotenv, $privateData);
            $this->assertEquals('/llTiaDfwfYIuVaRI1Ah9T3mWgy2FJVuyRUV0CvPVk8=', $privateKey);
        }
        // Find public key
        $envFile            = '/.env';
        $publicKey          = false;
        $envFileExists      = file_exists($path . '/' . $envFile);
        $arrayAdapter       = new ArrayAdapter();
        $this->assertTrue($envFileExists);
        if ($envFileExists) {
            $repository         = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter($arrayAdapter)
                ->make();
            Dotenv::create($repository, $path, $envFile)->load();
            $containsPublicKey  = isset($arrayAdapter->values['DOTENV_PUBLIC_KEY']);
            $this->assertTrue($containsPrivateKey);
            if ($containsPublicKey) {
                $publicKey     = $arrayAdapter->values['DOTENV_PUBLIC_KEY'];
            }
            $this->assertEquals('Ek1Krd8QRcG2B20p1iwM6IHgUVGHyCcudqjqoAgqMQA=', $publicKey);
            $this->assertEquals('encrypted:kpOFCd76bsEMvgk7iJ1a7oHbQdGITAMAtUppEIBgRmUjinhWoxaKJD9Xz1SqKEwSGAlnuWhXksv1', $arrayAdapter->values['DB_HOST']);        // localhost
            $this->assertEquals('encrypted:k4hknNltlTjry3LFPsM3dtHkQdfJhWRRCK+X21JE6xAjg0xI3bT3rSXfJ9rdesIXWxYFzw=='    , $arrayAdapter->values['DB_PORT']);        // 3306
            $this->assertEquals('encrypted:XZA6xt1uXF1OdDrROuvC5+zVD/3OwXaj9dgPGdkF0QFUaNfCFTcCsmJl7V5e9I7w39egprAOXJg=', $arrayAdapter->values['DB_USER']);        // username
            $this->assertEquals('encrypted:iRJUQ3XaVQnhsUfea2i1NgZWb593oWXhjksHDeC2yzZFPKTsU7UC+D/vxDksSkDFff12oAqzVXk=', $arrayAdapter->values['DB_PASSWORD']);    // 'pa$$w0rd'
        }

        // Decrypt data
        $this->assertTrue($privateKey !== false && $publicKey !== false);
        if ($privateKey !== false && $publicKey !== false) {
            $staticKeyProvider  = new StaticKeyProvider($publicKey, $privateKey);
            Decryptor::decryptArrayAdapter($arrayAdapter, $staticKeyProvider);
        }

        // Validate values
        $options            = $arrayAdapter->values;
        $this->assertEquals('pdo_mysql' , $options['DB_DRIVER']);
        $this->assertEquals('localhost' , $options['DB_HOST']);
        $this->assertEquals('3306'      , $options['DB_PORT']);
        $this->assertEquals('username'  , $options['DB_USER']);
        $this->assertEquals('pa$$w0rd'  , $options['DB_PASSWORD']);
        $this->assertEquals('utf8mb4'   , $options['DB_CHARSET']);
    }

}
