<?php
namespace App\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;

class TokenService {
    private ?Key $encryptionKey = null;
    private string $keyFile;

    public function __construct() {
        $this->keyFile = __DIR__ . '/../../storage/encryption.key';
        $this->initializeKey();
    }

    /**
     * Initialize or load the encryption key
     */
    private function initializeKey(): void {
        $storageDir = dirname($this->keyFile);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0700, true);
        }

        if (file_exists($this->keyFile)) {
            $keyAscii = file_get_contents($this->keyFile);
            $this->encryptionKey = Key::loadFromAsciiSafeString($keyAscii);
        } else {
            $this->encryptionKey = Key::createNewRandomKey();
            file_put_contents($this->keyFile, $this->encryptionKey->saveToAsciiSafeString());
            chmod($this->keyFile, 0600);
        }
    }

    /**
     * Encrypt a token
     */
    public function encrypt(string $plaintext): string {
        return Crypto::encrypt($plaintext, $this->encryptionKey);
    }

    /**
     * Decrypt a token
     */
    public function decrypt(string $ciphertext): string {
        try {
            return Crypto::decrypt($ciphertext, $this->encryptionKey);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to decrypt token: ' . $e->getMessage());
        }
    }

    /**
     * Encrypt token data (JSON)
     */
    public function encryptTokenData(array $tokenData): string {
        return $this->encrypt(json_encode($tokenData));
    }

    /**
     * Decrypt token data (JSON)
     */
    public function decryptTokenData(string $encrypted): array {
        $json = $this->decrypt($encrypted);
        return json_decode($json, true) ?: [];
    }
}
