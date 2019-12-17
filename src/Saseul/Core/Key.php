<?php

namespace Saseul\Core;

use Saseul\Constant\Account;
use Saseul\Util\DateTime;

class Key
{
    public static function makePrivateKey()
    {
        return bin2hex(random_bytes(24)) . str_pad(dechex(DateTime::microtime()), 16, 0, 0);
    }

    public static function makePublicKey($private_key)
    {
        return bin2hex(sodium_crypto_sign_publickey(sodium_crypto_sign_seed_keypair(hex2bin($private_key))));
    }

    public static function makeAddress($public_key)
    {
        $p0 = Account::ADDRESS_PREFIX[0];
        $p1 = Account::ADDRESS_PREFIX[1];
        $s1 = $p1 . hash('ripemd160', hash('sha256', $p0 . $public_key));

        return $s1 . substr(hash('sha256', hash('sha256', $s1)), 0, 4);
    }

    public static function makeSignature($str, $private_key, $public_key)
    {
        return bin2hex(sodium_crypto_sign_detached($str, hex2bin($private_key . $public_key)));
    }

    public static function isValidSignature($str, $public_key, $signature)
    {
        if (strlen(hex2bin($signature)) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached(hex2bin($signature), $str, hex2bin($public_key));
    }

    public static function isValidKeySize(string $key): bool
    {
        return mb_strlen($key) === Account::PRIVATE_KEY_SIZE;
    }

    public static function isValidAddressSize(string $address): bool
    {
        return mb_strlen($address) === Account::ADDRESS_SIZE;
    }

    public static function isValidAddress(string $address, string $public_key): bool
    {
        return self::isValidAddressSize($address)
            && (self::makeAddress($public_key) === $address);
    }

    public static function isValidAccount(string $private_key, string $public_key, string $address): bool
    {
        return self::isValidKeySize($private_key)
            && self::isValidKeySize($public_key)
            && self::isValidAddressSize($address)
            && (self::makeAddress($public_key) === $address)
            && (self::makePublicKey($private_key) === $public_key);
    }
}
