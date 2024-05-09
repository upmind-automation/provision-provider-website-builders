<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Example;

use GuzzleHttp\Client;
use Upmind\ProvisionBase\Provider\Contract\ProviderInterface;
use Upmind\ProvisionBase\Provider\DataSet\AboutData;
use Upmind\ProvisionBase\Provider\DataSet\ResultData;
use Upmind\ProvisionProviders\WebsiteBuilders\Category;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\AccountIdentifier;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\AccountInfo;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\ChangePackageParams;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\CreateParams;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\LoginResult;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\UnSuspendParams;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Example\Data\Configuration;

/**
 * Empty provider for demonstration purposes.
 */
class Provider extends Category implements ProviderInterface
{
    protected Configuration $configuration;
    protected Client $client;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @inheritDoc
     */
    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('Example Provider')
            // ->setLogoUrl('https://example.com/logo.png')
            ->setDescription('Empty provider for demonstration purposes');
    }

    /**
     * @inheritDoc
     */
    public function create(CreateParams $params): AccountInfo
    {
        throw $this->errorResult('Not Implemented');
    }

    /**
     * @inheritDoc
     */
    public function getInfo(AccountIdentifier $params): AccountInfo
    {
        throw $this->errorResult('Not Implemented');
    }

    /**
     * @inheritDoc
     */
    public function login(AccountIdentifier $params): LoginResult
    {
        throw $this->errorResult('Not Implemented');
    }

    /**
     * @inheritDoc
     */
    public function changePackage(ChangePackageParams $params): AccountInfo
    {
        throw $this->errorResult('Not Implemented');
    }

    /**
     * @inheritDoc
     */
    public function suspend(AccountIdentifier $params): AccountInfo
    {
        throw $this->errorResult('Not Implemented');
    }

    /**
     * @inheritDoc
     */
    public function unSuspend(UnSuspendParams $params): AccountInfo
    {
        throw $this->errorResult('Not Implemented');
    }

    /**
     * @inheritDoc
     */
    public function terminate(AccountIdentifier $params): ResultData
    {
        throw $this->errorResult('Not Implemented');
    }

    /**
     * Get a Guzzle HTTP client instance.
     */
    protected function client(): Client
    {
        return $this->client ??= new Client([
            'handler' => $this->getGuzzleHandlerStack(),
            'base_uri' => 'https://example.com/api/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->configuration->api_token,
            ],
        ]);
    }
}
