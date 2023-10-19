<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Websitecom;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Str;
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
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Websitecom\Data\Configuration;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Websitecom\Helper\WebsitecomApi;

/**
 * Website.com provider.
 */
class Provider extends Category implements ProviderInterface
{
    protected Configuration $configuration;

    protected WebsitecomApi $api;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('Website.com')
            ->setDescription('Create, manage and log into Website.com site builder accounts')
            ->setLogoUrl('https://api.upmind.io/images/logos/provision/websitecom-logo@2x.png');
    }


    public function create(CreateParams $params): AccountInfo
    {
        if (!isset($params->domain_name)) {
            throw $this->errorResult('Domain name is required!');
        }

        try {
            $userRef = $this->api()->createUser($params);

            return $this->_getInfo($userRef, 'Account data obtained');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }


    public function getInfo(AccountIdentifier $params): AccountInfo
    {
        try {
            return $this->_getInfo($params->account_reference, 'Account data obtained');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }

    private function _getInfo(string $id, string $message): AccountInfo
    {
        $accountInfo = $this->api()->getInfo($id);

        return AccountInfo::create($accountInfo)->setMessage($message);
    }


    public function login(AccountIdentifier $params): LoginResult
    {
        try {
            $url = $this->api()->login($params->account_reference);

            return new LoginResult(['login_url' => $url]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }


    public function changePackage(ChangePackageParams $params): AccountInfo
    {
        try {
            $this->api()->changePackage($params->account_reference, $params->package_reference);

            return $this->_getInfo($params->account_reference, 'Package changed');
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }


    public function suspend(AccountIdentifier $params): AccountInfo
    {
        try {
            $this->api()->suspend($params->account_reference);

            return $this->_getInfo($params->account_reference, 'Account suspended');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }

    public function unSuspend(UnSuspendParams $params): AccountInfo
    {
        try {
            $this->api()->unsuspend($params->account_reference);

            $this->api()->changePackage($params->account_reference, $params->package_reference);

            return $this->_getInfo($params->account_reference, 'Account unsuspended');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }


    public function terminate(AccountIdentifier $params): ResultData
    {
        try {
            $this->api()->terminate($params->account_reference);

            return $this->okResult('Account Terminated');
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * @return no-return
     * @throws ProvisionFunctionError
     */
    protected function handleException(\Throwable $e, $params = null): void
    {
        if ($e instanceof RequestException) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();

                $body = trim($response->getBody()->__toString());
                $responseData = json_decode($body, true);

                $errorMessage = $responseData['message'] ?? 'unknown error';

                throw $this->errorResult(
                    sprintf('Provider API Error: %s', $errorMessage),
                    ['response_data' => $responseData],
                    [],
                    $e
                );
            }
        }

        throw $e;
    }

    public function api(): WebsitecomApi
    {
        if (isset($this->api)) {
            return $this->api;
        }

        $client = $this->client = new Client([
            'base_uri' => 'https://api.websiteserver.cloud/site-builder/v1de/',
            RequestOptions::HEADERS => [
                'User-Agent' => 'upmind/provision-provider-website-builders v1.0',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'resellerKey' => $this->configuration->reseller_key,
            ],
            RequestOptions::TIMEOUT => 10, // seconds
            RequestOptions::CONNECT_TIMEOUT => 5, // seconds
            'handler' => $this->getGuzzleHandlerStack(boolval($this->configuration->debug))
        ]);

        return $this->api = new WebsitecomApi($client, $this->configuration);
    }
}
