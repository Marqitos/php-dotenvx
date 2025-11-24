<?php
declare(strict_types=1);

if (class_exists('ParagonIE_Sodium_Core_Poly1305', false)) {
    return;
}

require_once __DIR__ . '/Util.php';

/**
 * Class ParagonIE_Sodium_Core_Poly1305
 */
abstract class ParagonIE_Sodium_Core_Poly1305 extends ParagonIE_Sodium_Core_Util
{

    const R_PARAGONIE_SODIUM_CORE_POLY1305_STATE    = __DIR__ . '/Poly1305/State.php';

    const BLOCK_SIZE = 16;

    /**
     * @internal You should not use this directly from another application
     *
     * @param string $m
     * @param string $key
     * @return string
     * @throws SodiumException
     * @throws TypeError
     */
    public static function onetimeauth(
        string $m,
        #[SensitiveParameter]
        string $key
    ): string {
        if (self::strlen($key) < 32) {
            throw new InvalidArgumentException(
                'Key must be 32 bytes long.'
            );
        }
        require_once self::R_PARAGONIE_SODIUM_CORE_POLY1305_STATE;
        $state = new ParagonIE_Sodium_Core_Poly1305_State(
            self::substr($key, 0, 32)
        );
        return $state
            ->update($m)
            ->finish();
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param string $mac
     * @param string $m
     * @param string $key
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    public static function onetimeauth_verify(
        string $mac,
        string $m,
        #[SensitiveParameter]
        string $key
    ): bool {
        if (self::strlen($key) !== 32) {
            throw new InvalidArgumentException(
                'Key must be 32 bytes long.'
            );
        }
        require_once self::R_PARAGONIE_SODIUM_CORE_POLY1305_STATE;
        $state = new ParagonIE_Sodium_Core_Poly1305_State(
            self::substr($key, 0, 32)
        );
        $calc = $state
            ->update($m)
            ->finish();
        return self::verify_16($calc, $mac);
    }
}
