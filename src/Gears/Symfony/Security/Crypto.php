<?php

namespace Cosmologist\Gears\Symfony\Security;

use Cosmologist\Gears\StringType;

/**
 * The service Crypto provides functions for the simple symmetric encryption.
 *
 * The framework.secret used as a key to encryption.
 *
 * @todo comment and readme and codestyle
 */
class Crypto
{
    /**
     * A secret key to encryption
     *
     * @var string
     */
    private string $secret;

    /**
     * @param string $secret A secret key to encryption
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Simple symmetric encryption of a string
     *
     * @link StringType::encrypt()
     * @link Crypto::decrypt()
     *
     * @param string $string A string to encrypt
     *
     * @return string A base64-encoded encrypted string
     */
    public function encrypt(string $string): string
    {
        return StringType::encrypt($string, $this->secret);
    }

    /**
     * Simple symmetric decryption of a string
     *
     * @link Crypto::encrypt()
     * @link StringType::decrypt()
     *
     * @param string $encrypted A base64-encoded encrypted string (via {@link Crypto::encrypt()} to decrypt
     *
     * @return string A decrypted original string
     */
    public function decrypt(string $encrypted): string
    {
        return StringType::decrypt($encrypted, $this->secret);
    }
}
