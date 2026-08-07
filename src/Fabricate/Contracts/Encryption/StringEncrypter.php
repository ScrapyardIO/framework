<?php

namespace Fabricate\Contracts\Encryption;

interface StringEncrypter
{
    /**
     * Encrypt a string without serialization.
     *
     * @param  string  $value
     * @return string
     *
     * @throws \Fabricate\Contracts\Encryption\EncryptException
     */
    public function encryptString(#[\SensitiveParameter] $value);

    /**
     * Decrypt the given string without unserialization.
     *
     * @param  string  $payload
     * @return string
     *
     * @throws \Fabricate\Contracts\Encryption\DecryptException
     */
    public function decryptString($payload);
}
