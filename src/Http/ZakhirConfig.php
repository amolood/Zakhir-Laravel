<?php

namespace Zakhir\LaravelZakhir\Http;

use Zakhir\LaravelZakhir\Exceptions\ZakhirException;

class ZakhirConfig
{
    public function __construct(private readonly array $config) {}

    public function environment(): string
    {
        return $this->config['environment'] === 'staging' ? 'staging' : 'production';
    }

    public function isStaging(): bool
    {
        return $this->environment() === 'staging';
    }

    public function baseUrl(): string
    {
        $raw = $this->isStaging()
            ? ($this->config['staging_base_url'] ?? '')
            : ($this->config['base_url'] ?? '');

        return rtrim((string) $raw, '/').'/api/ecommerce/';
    }

    public function tenant(): string
    {
        return (string) ($this->isStaging()
            ? ($this->config['staging_tenant'] ?? '')
            : ($this->config['tenant'] ?? ''));
    }

    public function profile(): string
    {
        return (string) ($this->isStaging()
            ? ($this->config['staging_profile'] ?? '')
            : ($this->config['profile'] ?? ''));
    }

    public function apiKey(): string
    {
        return (string) ($this->isStaging()
            ? ($this->config['staging_api_key'] ?? '')
            : ($this->config['api_key'] ?? ''));
    }

    public function timeout(): int
    {
        return (int) ($this->config['timeout'] ?? 15);
    }

    public function webhookUrl(): string
    {
        return (string) ($this->config['webhook_url'] ?? '');
    }

    public function returnUrl(): string
    {
        return (string) ($this->config['return_url'] ?? '');
    }

    public function loggingEnabled(): bool
    {
        return (bool) ($this->config['logging'] ?? true);
    }

    public function assertConfigured(): void
    {
        $emptyBase = rtrim((string) ($this->isStaging()
            ? ($this->config['staging_base_url'] ?? '')
            : ($this->config['base_url'] ?? '')), '/') === '';

        if ($emptyBase) {
            throw ZakhirException::notConfigured('base_url');
        }

        if ($this->tenant() === '') {
            throw ZakhirException::notConfigured('tenant');
        }

        if ($this->profile() === '') {
            throw ZakhirException::notConfigured('profile');
        }

        if ($this->apiKey() === '') {
            throw ZakhirException::notConfigured('api_key');
        }
    }
}
