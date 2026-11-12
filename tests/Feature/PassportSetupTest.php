<?php

namespace Tests\Feature;

use App\Support\PassportSetup;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PassportSetupTest extends TestCase
{
    private ?string $previousKeyPath;

    private mixed $previousPrivateKey;

    private mixed $previousPublicKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousKeyPath = Passport::$keyPath;
        $this->previousPrivateKey = config('passport.private_key');
        $this->previousPublicKey = config('passport.public_key');
    }

    protected function tearDown(): void
    {
        Passport::$keyPath = $this->previousKeyPath;
        config([
            'passport.private_key' => $this->previousPrivateKey,
            'passport.public_key' => $this->previousPublicKey,
        ]);

        parent::tearDown();
    }

    public function test_a_server_without_passport_keys_creates_a_usable_pair(): void
    {
        $directory = storage_path('framework/testing-passport-keys');
        $this->removeDirectory($directory);
        mkdir($directory, 0775, true);

        Passport::loadKeysFrom($directory);
        config([
            'passport.private_key' => 'not-a-real-key',
            'passport.public_key' => null,
        ]);

        PassportSetup::ensure();

        $private = file_get_contents($directory.'/oauth-private.key');
        $public = file_get_contents($directory.'/oauth-public.key');

        $this->assertIsString($private);
        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, openssl_pkey_get_private($private));
        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, openssl_pkey_get_public($public));
        $this->assertNull(config('passport.private_key'));
        $this->assertNull(config('passport.public_key'));

        PassportSetup::ensure();

        $this->assertSame($private, file_get_contents($directory.'/oauth-private.key'));

        $this->removeDirectory($directory);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
