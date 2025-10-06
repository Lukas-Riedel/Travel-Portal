<?php
    namespace Iam\Client\Encryption;

    class EncryptionClient {
        
        private const CIPHER = "aes-256-cbc";
        
        private readonly string $privateKey;

        public function __construct(string $privateKey) {
            $this->privateKey = $privateKey;
        }

        public function encrypt(string $plainText) : string {
            $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
            $cipherText = openssl_encrypt($plainText, self::CIPHER, $this->privateKey, OPENSSL_RAW_DATA, $iv);
            return base64_encode($iv . $cipherText);
        }

        public function decrypt(string $encoded) : string {
            $data = base64_decode($encoded);
            $ivLength = openssl_cipher_iv_length(self::CIPHER);
            $iv = substr($data, 0, $ivLength);
            $cipherText = substr($data, $ivLength);
            return openssl_decrypt($cipherText, self::CIPHER, $this->privateKey, OPENSSL_RAW_DATA, $iv);
        }
    }
?>