<?php

namespace Zakhir\LaravelZakhir\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Zakhir\LaravelZakhir\ZakhirServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ZakhirServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('zakhir', [
            'environment' => 'production',
            'base_url'    => 'https://zakhir.net/api/',
            'tenant'      => 'test-tenant',
            'profile'     => 'test-profile',
            'api_key'     => 'test-api-key',
            'staging_base_url' => '',
            'staging_tenant'   => '',
            'staging_profile'  => '',
            'staging_api_key'  => '',
            'webhook_url'   => 'https://example.com/api/zakhir/webhook',
            'return_url'    => 'https://example.com/return',
            'webhook_secret'=> '',
            'timeout'       => 15,
            'logging'       => false,
            'routes'        => ['enabled' => false],
        ]);
    }
}
