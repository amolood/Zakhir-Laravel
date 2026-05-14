<?php

namespace Zakhir\LaravelZakhir\Tests\Unit;

use Zakhir\LaravelZakhir\Exceptions\ZakhirException;
use Zakhir\LaravelZakhir\Http\ZakhirConfig;
use Zakhir\LaravelZakhir\Tests\TestCase;

class ZakhirConfigTest extends TestCase
{
    private function makeConfig(array $overrides = []): ZakhirConfig
    {
        return new ZakhirConfig(array_merge([
            'environment' => 'production',
            'base_url'    => 'https://zakhir.cloud/api/',
            'tenant'      => 'tenant-1',
            'profile'     => 'profile-1',
            'api_key'     => 'key-abc',
            'timeout'     => 10,
        ], $overrides));
    }

    public function test_production_environment_is_default(): void
    {
        $config = $this->makeConfig(['environment' => 'anything']);
        $this->assertSame('production', $config->environment());
    }

    public function test_staging_environment_is_recognized(): void
    {
        $config = $this->makeConfig(['environment' => 'staging']);
        $this->assertSame('staging', $config->environment());
        $this->assertTrue($config->isStaging());
    }

    public function test_base_url_has_ecommerce_suffix(): void
    {
        $config = $this->makeConfig(['base_url' => 'https://zakhir.cloud/api']);
        $this->assertStringEndsWith('/api/ecommerce/', $config->baseUrl());
    }

    public function test_staging_credentials_used_in_staging(): void
    {
        $config = $this->makeConfig([
            'environment'      => 'staging',
            'staging_base_url' => 'https://staging.zakhir.cloud/api/',
            'staging_tenant'   => 'stg-tenant',
            'staging_profile'  => 'stg-profile',
            'staging_api_key'  => 'stg-key',
        ]);

        $this->assertStringContainsString('staging', $config->baseUrl());
        $this->assertSame('stg-tenant', $config->tenant());
        $this->assertSame('stg-key', $config->apiKey());
    }

    public function test_assert_configured_throws_when_base_url_missing(): void
    {
        $this->expectException(ZakhirException::class);
        $this->makeConfig(['base_url' => ''])->assertConfigured();
    }

    public function test_assert_configured_throws_when_tenant_missing(): void
    {
        $this->expectException(ZakhirException::class);
        $this->makeConfig(['tenant' => ''])->assertConfigured();
    }

    public function test_assert_configured_throws_when_api_key_missing(): void
    {
        $this->expectException(ZakhirException::class);
        $this->makeConfig(['api_key' => ''])->assertConfigured();
    }

    public function test_assert_configured_passes_with_valid_config(): void
    {
        $this->expectNotToPerformAssertions();
        $this->makeConfig()->assertConfigured();
    }
}
