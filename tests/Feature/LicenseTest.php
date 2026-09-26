<?php

namespace Tests\Feature;

use App\Core\Services\LicenseService;
use App\Http\Middleware\RequirePair;
use App\Providers\License\MarketplaceLicenseProvider;
use App\Services\LicenseClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Coverage for the v3 marketplace pairing kit plus the local license rows.
 *
 * The kit authenticates a payload with an RSA signature from the marketplace
 * PRIVATE key, which by design never reaches the client. So these tests stand
 * in a throwaway keypair via config('license.public_key_path') and drive the
 * kit's real activate() -> writeLock() -> verify() path over Http::fake. That
 * exercises the genuine crypto code, not a re-implementation, and it lets the
 * forgery direction be asserted properly: a payload signed by any other key
 * must be rejected.
 *
 * What is NOT covered here: a real activation against whitelabel.co.id, and
 * the real shipped public/marketplace.public.pem (only checked for integrity,
 * since its matching private key is unavailable).
 */
class LicenseTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    private string $publicKeyPath;

    /**
     * Fixture keypairs. openssl_pkey_new() is unavailable on some Windows
     * OpenSSL builds (no openssl.cnf), and these are test-only throwaway keys
     * that never leave the repo's test run.
     */
    private const TRUSTED_PRIVATE = <<<'PEM'
        -----BEGIN PRIVATE KEY-----
        MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCo/5lqkvcBgjHn
        WS0xuqxs0B7FO2fAlGlV2Bjk8dagb9srs6BKFQnhzjtziAcUk87BM9KkAzsWVTzJ
        wF0YnkNcgOOV4hlWNrh8Y8fGpSgyeKgvVbffWrw49wxanLK9KD2lgjIxLuc7C6AL
        6xsKKOFIOrozOiVPk2oEH9rBpbRqW81wnftFtNDeY3AraaAItWMNvZDChEohGN8j
        Pw9NhMmvXt56MQLbCpq7oXOxFZXvv17KxTP/s6ZhjFPp3coRjaldHw/IMjFcsRQC
        IBppaI/JktgRHFAa1VsMjJKLZS2v5qNqQIQCpy0dD+yuQ3XENQ2DwVXlTKMQbKCS
        w+37R6V5AgMBAAECggEACmUGS67UcIxQg3lRtVBVELBQZDM+M3Mtc3FCPrq9R8r5
        gRugTU4z+GaV84o3XUWmHu4QE7R7Kul9Pq+NSllZrVPkK7DnfA0LleMRQ5+e9FPF
        jHvKPnu3Pg27/crLl6Tk/cwfSDUpVdFmO7VvSVWwXZ/3GfWGm/lmOPC4pNaLXUtE
        oLIv2VeWvsA9q/uUtrmGvTPevA1deqV/Ph2JzJ3ihdEhMGfHXRim3uvaR9Yae+36
        xJc9Z0tfDlVQt3mEA3sEGITrJO7l3YEboKYyagdTlpNt+OMtyQ+HUgblwvCP3HzJ
        5X1INmJoHloX3beGJA2HTX3+GPyemfpQBEhZiIYjAQKBgQDYwNeNLz45dPNHmJUv
        unia2AIYuZZvU0g662xLHRhWQ0pm692d9Is7S/BWhUDhk5nUCB8L+seE59SvL51+
        MHc3WpwRHsvaNB5MEDDxbofcQp0iGTu36Dmxs/r8DkoL11rrlZl0Hd9weBkJhi2/
        zZa17e+YL6IzR7z1UGUuHf5umQKBgQDHmS2YejTKtGDThbsujjzxcZTcMQ9KIeaf
        SgAdyfEsYDnfa7dOfXRjVl6rAQCRbbV4To5nnsCrTmGmlSXYnODfJVAleXLdQu5J
        lHY8t3LrrJ5qkHodGrWMaiWCz8XKVDe8gzlnLLH8OyWdgCU3OoeFLro0qTx3nveP
        Z7B+52eZ4QKBgBbGPF/DRQB4f09YguRe7WknpSC/70SHNaGrNte1mOcHbvvdcI22
        MiLq2bfdjHGnNpSGvexeTzRxv3EgyaWGpiAUzy0lVTn6G+zWDq5vdKr5/NSmXhX2
        uoknZgmx3qnb2NvD/jmrId0JYWgUxx3OFjXLaE9PQfWtZfdImTj9QcyRAoGARf/q
        hbcWHKD19DjKVKF9rg9vbWmnOxB4mRSSxd+0vSNiKIDWYKiO0OfRe5d2Y4peQjsK
        pjx+xZVPmeRkyXr7QkcLvJjDN+XpO9TdQp3zp8N6K1VP/jUHxp7TWPUVIMg4Y9yB
        nTWHljYIExyF8MCOFp80npNbqXgOyjRLKbZuFwECgYBMbGK4Jdwr+jrX79aJaNmm
        8uQOPB1zPPUZqbjMw9OLEBfxQGBrwBMexoCqf5wfZz+8TrFVo0iaI4ADDQrJH2HA
        hUrXGHtUbjFsIFGfBaFitw1LqPwEHrKWYrZi2pP1qkJhKITluA8GimKvNMamWJTE
        ol3Z71GBEeaFgP63MzfaQA==
        -----END PRIVATE KEY-----
        PEM;

    private const TRUSTED_PUBLIC = <<<'PEM'
        -----BEGIN PUBLIC KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqP+ZapL3AYIx51ktMbqs
        bNAexTtnwJRpVdgY5PHWoG/bK7OgShUJ4c47c4gHFJPOwTPSpAM7FlU8ycBdGJ5D
        XIDjleIZVja4fGPHxqUoMnioL1W331q8OPcMWpyyvSg9pYIyMS7nOwugC+sbCijh
        SDq6MzolT5NqBB/awaW0alvNcJ37RbTQ3mNwK2mgCLVjDb2QwoRKIRjfIz8PTYTJ
        r17eejEC2wqau6FzsRWV779eysUz/7OmYYxT6d3KEY2pXR8PyDIxXLEUAiAaaWiP
        yZLYERxQGtVbDIySi2Utr+ajakCEAqctHQ/srkN1xDUNg8FV5UyjEGygksPt+0el
        eQIDAQAB
        -----END PUBLIC KEY-----
        PEM;

    private const FOREIGN_PRIVATE = <<<'PEM'
        -----BEGIN PRIVATE KEY-----
        MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDPE2nmBNIsKKdh
        DothRgj2BpP5rXTFUjiAQ6KAozFCGZqioZvVtczLwgNndrVDG8yPF3pPMtSQAsgm
        UElYQocGSNIVhDKM015nLIlAxcP400sFUNDf8FqY8Nl1JEfnCx3pR7lBMAYQCx1j
        HQDd8a0B3qznE1A7GtCuBO7B1x508XA1gISOdKxUgAY9j8lP9BZQ95JYHPPJwggP
        rKWotE3j/5OlwzoTiigjbWmo3NHpCpX3nizMZ9uUeSw9Bm0X0KF5l52oCi3GEC7f
        pPxftIx6K505+9CVLxRmfuQnOgdwPFX05Tj4NqJIvclbI3HI4tED256OHJGfVL6r
        VaqWyKIPAgMBAAECggEARORdEYEeGUHnOcOYfGLL/Wn/1guusy0hDg8yY6CndSnG
        h//DNCz5NvrTnhrgwDRh8GMrtmifTlAWnaSNWjc768vTVQQ3uyFhIWswOKPzCHfn
        WBvkefRhd8t9VVseLtBEgcVybS0Yf0LrYnuWO8C5Qct+85u50AgiUBrlAglbISVO
        qv8FuXcrcVcmi5wQrBDyIivbp2kzB8MuAka0pUOPcGTwNemf16FdptleuFyT1e4w
        r3OUV+thwszFsFCyGO5LEGlKYCoHbfb8AzChws6YKnFrvKoDywXNEv48Qy9rhi39
        drhXik/aZDuhHRnobKFVALLXScKR3ahfp7otKyFcOQKBgQD0RYxS25MPEzSsfotN
        1MA0q2NRHguU4IOX7SLaV/yxU5XLz8kHnFjeQx6PYz2oRdUrbf0XGD4Mqgx4IY4X
        ZIEDiNI7RghBdgrZXpPNBI7XeENWnOxw8iMvkyj8F9T8IFLRvNHbHGBACFq1+DeV
        EPw+K2tO8KvOkcNpB0Z6WFuqWQKBgQDZBKzIBx05wPwrRXQOVUzyVei5KQ54qnwK
        hVsYJ/qFwd5ksFG7HOrohvB5zCG2C4R5coN1S4dWObZ5TmGj1jyRHd7uC5xavSya
        UsgPp8o2vGRv8RDM9wfIhROcyzwVGmm/JtX/fh1B4sMdbacO6WSRNovqL+n+m5S8
        Xp4mpExSpwKBgEozpkC1OqLlrqaHekGWUxysw2qsuc/rs42/F0tEVxp2zZYv9F0/
        fS9nLC1adCxdqjebHbqaPp8SON91MfihKx+rvFENIQzhksIdHMC2lb2WZr40xQ46
        P73/8f9CLgy4tO/Jb+YjZImPAB8u25OIqVcpUVuVeFszpCyPbTVVCeNhAoGAd1Xw
        xRXUZlvOzuSkSvVxGJlRHfCLuqLVDtwCGahyRHc1Gd0zNFdUfYUmW3N63iY7NKVZ
        0Hg19Z5Kzy3g1z0JlSr92Zyc3/DCxCHdTW6Q7cRu3neLK4pzxzoWbNP9OAWMPMbY
        SmRJJl/Rty8C/FovKQL7sU2juJRJF8RX5xvVtGUCgYEAssUODl2QcDkrSu8xmzcF
        lIbS7BCWMzvnR7HN9zXQuTlyEsIpK8funk+jj8mJxzqpe0Bs3ssGXHbADGYggudG
        0bLsKU0ualdXXdV0wuHRuV0XoSpNSbrAkGfwwJ0Mly8jq8Ad1tcWnDRRu6xHHKrl
        MwO/04SF+rOsgyYGwZA3l+w=
        -----END PRIVATE KEY-----
        PEM;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // A blanket Http::fake() here would shadow the per-test activation
        // stub, so only the heartbeat endpoint is faked up front.
        Http::fake([
            '*/api/license/heartbeat' => Http::response(['ok' => true, 'status' => 'active'], 200),
        ]);

        // Never touch a real installation's lock file or the shipped key.
        $this->lockPath = storage_path('framework/testing/license-test.lock');
        $this->publicKeyPath = storage_path('framework/testing/license-test-public.pem');
        @unlink($this->lockPath);

        config([
            'license.lock_file' => $this->lockPath,
            'license.public_key_path' => $this->publicKeyPath,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        @unlink($this->publicKeyPath);
        parent::tearDown();
    }

    // ── local LicenseService ──────────────────────────────────────────

    public function test_unlicensed_install_reports_unlicensed_instead_of_claiming_valid(): void
    {
        $status = app(LicenseService::class)->status();

        $this->assertFalse($status['licensed']);
        $this->assertSame('unlicensed', $status['state']);
        $this->assertArrayNotHasKey('license', $status);
    }

    public function test_has_feature_is_false_when_unlicensed(): void
    {
        $service = app(LicenseService::class);

        $this->assertFalse($service->hasFeature('modules'));
        $this->assertFalse($service->hasFeature('*'));
    }

    public function test_activate_and_deactivate_against_a_license_row(): void
    {
        $service = app(LicenseService::class);

        $license = $service->issue([
            'product' => 'Lindu CMS',
            'customer' => 'Acme',
            'domain' => 'acme.test',
            'features' => ['modules', 'white-label'],
            'max_activations' => 2,
        ]);

        $result = $service->activate($license->license_key, 'acme.test');

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $license->activations()->count());

        $removed = $service->deactivate($license->license_key, 'acme.test');

        $this->assertTrue($removed['ok']);
        $this->assertSame(0, $license->activations()->count());
    }

    public function test_installed_license_is_valid_and_reports_features(): void
    {
        $service = app(LicenseService::class);

        $license = $service->issue([
            'product' => 'Lindu CMS',
            'customer' => 'Acme',
            'domain' => 'acme.test',
            'features' => ['modules'],
            'max_activations' => 1,
        ]);

        $service->install([
            'license_key' => $license->license_key,
            'domain' => 'acme.test',
        ]);

        $status = $service->status();

        $this->assertTrue($status['licensed']);
        $this->assertSame('valid', $status['state']);
        $this->assertTrue($service->hasFeature('modules'));
        $this->assertFalse($service->hasFeature('white-label'));
    }

    public function test_activation_limit_is_enforced(): void
    {
        $service = app(LicenseService::class);

        $license = $service->issue([
            'product' => 'Lindu CMS',
            'customer' => 'Acme',
            'max_activations' => 1,
        ]);

        $this->assertTrue($service->activate($license->license_key, 'one.test')['ok']);

        $second = $service->activate($license->license_key, 'two.test');

        $this->assertFalse($second['ok']);
        $this->assertStringContainsString('Activation limit', $second['message']);
        $this->assertSame(1, $license->activations()->count());

        // The already-activated domain stays re-activatable.
        $this->assertTrue($service->activate($license->license_key, 'one.test')['ok']);
    }

    public function test_expired_license_transitions_to_expired_status(): void
    {
        $service = app(LicenseService::class);

        $license = $service->issue([
            'product' => 'Lindu CMS',
            'customer' => 'Acme',
            'max_activations' => 1,
        ]);

        // Install while still valid, then let the clock pass the expiry.
        $service->install(['license_key' => $license->license_key, 'domain' => 'acme.test']);
        $this->assertSame('valid', $service->status()['state']);

        $license->update(['expires_at' => now()->subDay()]);

        $status = $service->status();

        $this->assertSame('expired', $status['state']);
        $this->assertFalse($status['licensed']);
        $this->assertFalse($service->hasFeature('modules'));
        $this->assertSame('expired', $license->fresh()->status);
    }

    public function test_suspended_banned_and_inactive_licenses_are_not_licensed(): void
    {
        $service = app(LicenseService::class);

        foreach (['suspended', 'banned', 'inactive'] as $status) {
            $license = $service->issue([
                'product' => 'Lindu CMS',
                'customer' => 'Acme',
                'max_activations' => 1,
            ]);

            // install() refuses a non-active license, which is itself correct
            // behaviour, so pair first and then flip the status underneath.
            $service->install(['license_key' => $license->license_key, 'domain' => 'acme.test']);
            $license->update(['status' => $status]);

            $this->assertSame($status, $service->status()['state'], "state for {$status}");
            $this->assertFalse($service->status()['licensed']);
        }
    }

    public function test_non_active_licenses_cannot_be_activated(): void
    {
        $service = app(LicenseService::class);

        foreach (['suspended', 'banned', 'inactive'] as $status) {
            $license = $service->issue([
                'product' => 'Lindu CMS',
                'customer' => 'Acme',
                'status' => $status,
                'max_activations' => 1,
            ]);

            $result = $service->activate($license->license_key, 'acme.test');

            $this->assertFalse($result['ok'], $status);
            $this->assertStringContainsString($status, $result['message']);
        }
    }

    public function test_outdated_release_is_reported_when_entitlement_is_lower(): void
    {
        $service = app(LicenseService::class);

        $license = $service->issue([
            'product' => 'Lindu CMS',
            'customer' => 'Acme',
            'max_activations' => 1,
            'version_entitlement' => '0.0.1',
        ]);
        $service->install(['license_key' => $license->license_key, 'domain' => 'acme.test']);

        $this->assertSame('outdated', $service->status()['state']);
    }

    // ── kit: shipped assets ───────────────────────────────────────────

    public function test_shipped_marketplace_public_key_is_a_public_key(): void
    {
        $path = public_path('marketplace.public.pem');

        $this->assertFileExists($path, 'kit public key must be installed');
        $this->assertNotFalse(openssl_pkey_get_public('file://'.$path));
        $this->assertStringNotContainsString(
            'PRIVATE KEY',
            (string) file_get_contents($path),
            'the marketplace private key must never ship to the client'
        );
    }

    // ── kit: activation + lock handling ───────────────────────────────

    public function test_missing_lock_file_means_not_paired(): void
    {
        $client = app(LicenseClient::class);

        $this->assertFalse($client->isPaired('acme.test'));
        $this->assertNull($client->verify('acme.test'));
    }

    public function test_activate_writes_an_encrypted_lock_and_verifies(): void
    {
        $client = app(LicenseClient::class);

        $result = $this->fakeActivate('acme.test');

        $this->assertTrue($result['ok'], (string) ($result['error'] ?? ''));
        $this->assertFileExists($this->lockPath);

        // The payload must not be readable as plaintext on disk.
        $raw = (string) file_get_contents($this->lockPath);
        $this->assertStringNotContainsString('acme.test', $raw);
        $this->assertStringNotContainsString('MK-TEST-0001', $raw);
        $this->assertStringNotContainsString('signed_payload', $raw);

        $data = $client->verify('acme.test');
        $this->assertIsArray($data);
        $this->assertSame('acme.test', $data['domain']);
        $this->assertTrue($client->isPaired('acme.test'));
    }

    public function test_lock_file_is_written_with_owner_only_permissions(): void
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $this->markTestSkipped('POSIX permission bits are not enforced by chmod() on Windows.');
        }

        $this->fakeActivate('acme.test');

        clearstatcache();
        $this->assertSame(0600, fileperms($this->lockPath) & 0777);
    }

    public function test_lock_file_is_not_written_under_public(): void
    {
        $this->fakeActivate('acme.test');

        $lock = str_replace('\\', '/', (string) config('license.lock_file'));
        $public = rtrim(str_replace('\\', '/', public_path()), '/').'/';
        $storage = rtrim(str_replace('\\', '/', storage_path()), '/').'/';

        $this->assertStringStartsWith($storage, $lock, 'lock must live on the app disk');
        $this->assertStringStartsNotWith($public, $lock, 'lock must not be web-accessible');
        $this->assertFileDoesNotExist(public_path('.license.lock'));
    }

    public function test_tampered_lock_file_is_rejected(): void
    {
        $client = app(LicenseClient::class);

        $this->assertTrue($this->fakeActivate('acme.test')['ok']);
        $this->assertIsArray($client->verify('acme.test'), 'baseline: untouched lock verifies');

        $blob = (string) file_get_contents($this->lockPath);
        $tampered = $blob;
        $tampered[40] = $tampered[40] === 'a' ? 'b' : 'a';
        file_put_contents($this->lockPath, $tampered);

        $this->assertNull($client->verify('acme.test'), 'tampered ciphertext must not verify');
        $this->assertFalse($client->isPaired('acme.test'));
    }

    public function test_lock_file_signed_with_a_foreign_key_is_rejected(): void
    {
        $client = app(LicenseClient::class);

        // Genuine AES-256-GCM under the correct derived key, so decryption would
        // succeed — only the signature check can catch this forgery. The kit
        // verifies before writing, so nothing may reach disk at all.
        $result = $this->fakeActivate('acme.test', self::FOREIGN_PRIVATE);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('signature', strtolower((string) ($result['error'] ?? '')));
        $this->assertFileDoesNotExist($this->lockPath, 'a forged payload must never be written');
        $this->assertNull($client->verify('acme.test'));
        $this->assertFalse($client->isPaired('acme.test'));
    }

    public function test_lock_file_copied_to_another_domain_is_rejected(): void
    {
        $client = app(LicenseClient::class);

        $this->fakeActivate('acme.test');

        $this->assertNull($client->verify('evil.test'), 'lock must not follow the file to another host');
    }

    public function test_expired_signed_payload_is_rejected(): void
    {
        $client = app(LicenseClient::class);

        $this->fakeActivate('acme.test', null, now()->subDay()->toIso8601String());

        $this->assertNull($client->verify('acme.test'));
    }

    // ── kit: provider bridge ──────────────────────────────────────────

    public function test_provider_bridge_reports_unpaired_install(): void
    {
        $provider = app(MarketplaceLicenseProvider::class);

        $this->assertFalse($provider->isPaired('acme.test'));
        $this->assertNull($provider->payload('acme.test'));
        $this->assertNull($provider->currentKey());
        $this->assertFalse($provider->deactivate('ANY-KEY', 'acme.test'));
    }

    public function test_provider_bridge_fails_closed_without_a_domain_or_key(): void
    {
        $provider = app(MarketplaceLicenseProvider::class);

        $this->assertFalse($provider->verify('ANY-KEY', '')['ok']);
        $this->assertFalse($provider->verify('ANY-KEY', 'acme.test')['ok']);
        $this->assertFileDoesNotExist($this->lockPath, 'a failed activation must leave no lock');
    }

    public function test_provider_bridge_pairs_and_reports_entitlements(): void
    {
        $provider = app(MarketplaceLicenseProvider::class);

        $this->fakeActivate('acme.test');

        $result = $provider->verify('MK-TEST-0001', 'acme.test');

        $this->assertTrue($result['ok']);
        $this->assertSame('Paired', $result['message']);
        $this->assertSame(['modules'], $result['features']);
        $this->assertSame('1.0.0', $result['entitled_version']);
        $this->assertNotNull($result['expires_at']);
    }

    public function test_provider_bridge_deactivate_removes_a_valid_lock(): void
    {
        $provider = app(MarketplaceLicenseProvider::class);

        $this->fakeActivate('acme.test');
        $this->assertTrue($provider->isPaired('acme.test'));

        $this->assertTrue($provider->deactivate('ANY-KEY', 'acme.test'));
        $this->assertFileDoesNotExist($this->lockPath);
        $this->assertFalse($provider->isPaired('acme.test'));
    }

    // ── kit: middleware ──────────────────────────────────────────────

    public function test_require_pair_redirects_unpaired_hosts_to_the_wizard(): void
    {
        $middleware = new RequirePair(app(LicenseClient::class));
        $request = Request::create('https://acme.test/admin/dashboard');

        $response = $middleware->handle($request, fn () => response('app-body'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/__pair', (string) $response->headers->get('Location'));
    }

    public function test_require_pair_allows_the_wizard_itself(): void
    {
        $middleware = new RequirePair(app(LicenseClient::class));

        foreach (['/__pair', '/__pair/success', '/up'] as $path) {
            $response = $middleware->handle(
                Request::create('https://acme.test'.$path),
                fn () => response('allowed')
            );

            $this->assertSame(200, $response->getStatusCode(), $path);
        }
    }

    public function test_require_pair_lets_a_paired_host_through(): void
    {
        $this->fakeActivate('acme.test');

        $middleware = new RequirePair(app(LicenseClient::class));
        $request = Request::create('https://acme.test/admin/dashboard');

        $response = $middleware->handle($request, fn () => response('app-body'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('app-body', $response->getContent());
        $this->assertIsArray($request->attributes->get('license'));
    }

    // ── helpers ───────────────────────────────────────────────────────

    /**
     * Stand in for the marketplace activation endpoint. The response is signed
     * with $privatePem so the kit's own verifySignature() decides the outcome.
     */
    private function fakeActivate(
        string $domain,
        ?string $privatePem = null,
        ?string $expiresAt = null
    ): array {
        file_put_contents($this->publicKeyPath, self::TRUSTED_PUBLIC);

        $privatePem ??= self::TRUSTED_PRIVATE;
        $expiresAt ??= now()->addYear()->toIso8601String();

        $data = [
            'domain' => $domain,
            'product' => ['name' => 'Lindu CMS', 'version' => '1.0.0'],
            'license' => ['key' => 'MK-TEST-0001', 'features' => ['modules']],
            'expires_at' => $expiresAt,
            'installation_id' => 'inst-'.substr(hash('sha256', $domain), 0, 12),
        ];

        $private = openssl_pkey_get_private($privatePem);
        $this->assertNotFalse($private, 'signing key must load');

        openssl_sign(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $signature,
            $private,
            OPENSSL_ALGO_SHA256
        );

        Http::fake([
            '*/api/license/activate' => Http::response([
                'activated' => true,
                'signed_payload' => [
                    'data' => $data,
                    'signature' => base64_encode($signature),
                ],
            ], 200),
            '*/api/license/heartbeat' => Http::response(['ok' => true, 'status' => 'active'], 200),
        ]);

        return app(LicenseClient::class)->activate('MK-TEST-0001', $domain);
    }
}
