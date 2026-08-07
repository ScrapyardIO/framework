<?php

namespace Fabricate\Encryption;

use Fabricate\Contracts\Encryption\DecryptException;
use Fabricate\Contracts\Encryption\Encrypter as EncrypterContract;
use Fabricate\Contracts\Encryption\EncryptException;
use Fabricate\Contracts\Encryption\StringEncrypter;
use RuntimeException;

class Encrypter implements EncrypterContract, StringEncrypter
{
    /**
     * The encryption key.
     */
    protected string $key;

    /**
     * The previous / legacy encryption keys.
     *
     * @var list<string>
     */
    protected array $previousKeys = [];

    /**
     * The algorithm used for encryption.
     */
    protected string $cipher;

    /**
     * Create a new encrypter instance.
     *
     * @throws \RuntimeException
     */
    public function __construct(#[\SensitiveParameter] string $key, string $cipher = 'aes-256-cbc')
    {
        if (! static::supported($key, $cipher)) {
            $ciphers = implode(', ', array_column(Cipher::cases(), 'value'));

            throw new RuntimeException("Unsupported cipher or incorrect key length. Supported ciphers are: {$ciphers}.");
        }

        $this->key = $key;
        $this->cipher = $cipher;
    }

    /**
     * Determine if the given key and cipher combination is valid.
     */
    public static function supported(string $key, string $cipher): bool
    {
        $resolved = Cipher::tryFromName($cipher);

        if (is_null($resolved)) {
            return false;
        }

        return mb_strlen($key, '8bit') === $resolved->keySize();
    }

    /**
     * Create a new encryption key for the given cipher.
     */
    public static function generateKey(string $cipher): string
    {
        $resolved = Cipher::tryFromName($cipher) ?? Cipher::AES_256_CBC;

        return random_bytes($resolved->keySize());
    }

    /**
     * Encrypt the given value.
     *
     * @throws \Fabricate\Contracts\Encryption\EncryptException
     */
    public function encrypt(#[\SensitiveParameter] $value, $serialize = true)
    {
        $iv = random_bytes(openssl_cipher_iv_length(strtolower($this->cipher)));

        $value = openssl_encrypt(
            $serialize ? serialize($value) : $value,
            strtolower($this->cipher),
            $this->key,
            0,
            $iv,
            $tag
        );

        if ($value === false) {
            throw new EncryptException('Could not encrypt the data.');
        }

        $iv = base64_encode($iv);
        $tag = base64_encode($tag ?? '');

        $mac = $this->cipherEnum()->isAead()
            ? ''
            : $this->hash($iv, $value, $this->key);

        $json = json_encode(compact('iv', 'value', 'mac', 'tag'), JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EncryptException('Could not encrypt the data.');
        }

        return base64_encode($json);
    }

    /**
     * Encrypt a string without serialization.
     *
     * @throws \Fabricate\Contracts\Encryption\EncryptException
     */
    public function encryptString(#[\SensitiveParameter] $value)
    {
        return $this->encrypt($value, false);
    }

    /**
     * Decrypt the given value.
     *
     * @throws \Fabricate\Contracts\Encryption\DecryptException
     */
    public function decrypt($payload, $unserialize = true)
    {
        $payload = $this->getJsonPayload($payload);

        $iv = base64_decode($payload['iv']);

        $this->ensureTagIsValid(
            $tag = empty($payload['tag']) ? null : base64_decode($payload['tag'])
        );

        $foundValidMac = false;
        $decrypted = false;

        foreach ($this->getAllKeys() as $key) {
            if (
                $this->shouldValidateMac() &&
                ! ($foundValidMac = $foundValidMac || $this->validMacForKey($payload, $key))
            ) {
                continue;
            }

            $decrypted = openssl_decrypt(
                $payload['value'],
                strtolower($this->cipher),
                $key,
                0,
                $iv,
                $tag ?? ''
            );

            if ($decrypted !== false) {
                break;
            }
        }

        if ($this->shouldValidateMac() && ! $foundValidMac) {
            throw new DecryptException('The MAC is invalid.');
        }

        if ($decrypted === false) {
            throw new DecryptException('Could not decrypt the data.');
        }

        return $unserialize ? unserialize($decrypted) : $decrypted;
    }

    /**
     * Decrypt the given string without unserialization.
     *
     * @throws \Fabricate\Contracts\Encryption\DecryptException
     */
    public function decryptString($payload)
    {
        return $this->decrypt($payload, false);
    }

    /**
     * Create a MAC for the given value.
     */
    protected function hash(
        #[\SensitiveParameter] string $iv,
        #[\SensitiveParameter] string $value,
        #[\SensitiveParameter] string $key
    ): string {
        return hash_hmac('sha256', $iv.$value, $key);
    }

    /**
     * Get the JSON array from the given payload.
     *
     * @return array{iv: string, value: string, mac: string, tag?: string}
     *
     * @throws \Fabricate\Contracts\Encryption\DecryptException
     */
    protected function getJsonPayload(string $payload): array
    {
        $payload = json_decode(base64_decode($payload), true);

        if (! $this->validPayload($payload)) {
            throw new DecryptException('The payload is invalid.');
        }

        return $payload;
    }

    /**
     * Verify that the encryption payload is valid.
     */
    protected function validPayload(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        foreach (['iv', 'value', 'mac'] as $item) {
            if (! isset($payload[$item]) || ! is_string($payload[$item])) {
                return false;
            }
        }

        if (isset($payload['tag']) && ! is_string($payload['tag'])) {
            return false;
        }

        return strlen(base64_decode($payload['iv'], true)) === openssl_cipher_iv_length(strtolower($this->cipher));
    }

    /**
     * Determine if the MAC is valid for the given payload and key.
     *
     * @param  array{iv: string, value: string, mac: string}  $payload
     */
    protected function validMacForKey(#[\SensitiveParameter] array $payload, string $key): bool
    {
        return hash_equals(
            $this->hash($payload['iv'], $payload['value'], $key),
            $payload['mac']
        );
    }

    /**
     * Ensure the given tag is a valid tag given the selected cipher.
     *
     * @throws \Fabricate\Contracts\Encryption\DecryptException
     */
    protected function ensureTagIsValid(?string $tag): void
    {
        if ($this->cipherEnum()->isAead() && strlen((string) $tag) !== 16) {
            throw new DecryptException('Could not decrypt the data.');
        }

        if (! $this->cipherEnum()->isAead() && is_string($tag) && $tag !== '') {
            throw new DecryptException('Unable to use tag because the cipher algorithm does not support AEAD.');
        }
    }

    /**
     * Determine if we should validate the MAC while decrypting.
     */
    protected function shouldValidateMac(): bool
    {
        return ! $this->cipherEnum()->isAead();
    }

    /**
     * Determine if the given value appears to be encrypted by this encrypter.
     */
    public static function appearsEncrypted(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload)
            && isset($payload['iv'], $payload['value'], $payload['mac']);
    }

    /**
     * Get the encryption key that the encrypter is currently using.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get the current encryption key and all previous encryption keys.
     *
     * @return list<string>
     */
    public function getAllKeys()
    {
        return [$this->key, ...$this->previousKeys];
    }

    /**
     * Get the previous encryption keys.
     *
     * @return list<string>
     */
    public function getPreviousKeys()
    {
        return $this->previousKeys;
    }

    /**
     * Set the previous / legacy encryption keys that should be utilized if decryption fails.
     *
     * @param  list<string>  $keys
     * @return $this
     *
     * @throws \RuntimeException
     */
    public function previousKeys(array $keys)
    {
        foreach ($keys as $key) {
            if (! static::supported($key, $this->cipher)) {
                $ciphers = implode(', ', array_column(Cipher::cases(), 'value'));

                throw new RuntimeException("Unsupported cipher or incorrect key length. Supported ciphers are: {$ciphers}.");
            }
        }

        $this->previousKeys = $keys;

        return $this;
    }

    protected function cipherEnum(): Cipher
    {
        return Cipher::tryFromName($this->cipher) ?? Cipher::AES_256_CBC;
    }
}
