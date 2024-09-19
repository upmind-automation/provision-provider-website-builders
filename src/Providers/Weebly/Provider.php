<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Weebly;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Str;
use Throwable;
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
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Weebly\Data\Configuration;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Weebly\Helper\WeeblyApi;

/**
 * Weebly provider.
 */
class Provider extends Category implements ProviderInterface
{
    protected Configuration $configuration;

    protected WeeblyApi|null $api = null;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('Weebly')
            ->setDescription('Create, manage and log into Weebly site builder accounts')
            ->setLogoUrl('https://api.upmind.io/images/logos/provision/weebly-logo@2x.png');
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function create(CreateParams $params): AccountInfo
    {
        try {
            $userId = $this->api()->createUser($params);
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }

        try {
            if ($params->domain_name != null) {
                $this->api()->createDomain($userId, $params->domain_name);
                $this->api()->changePackage($userId, $params->domain_name, $params->package_reference, (int)$params->billing_cycle_months);
            }

            return $this->_getInfo($userId, null, 'Account data obtained');
        } catch (\Throwable $e) {
            if (($e instanceof RequestException) && $e->hasResponse()) {
                $response = $e->getResponse();

                $body = trim($response === null ? '' : $response->getBody()->__toString());
                $responseData = json_decode($body, true);

                $errorMessage = $responseData["error"]['message'] ?? $response->getReasonPhrase();
            }
            return $this->_getInfo($userId, null, $errorMessage ?? $e->getMessage());
        }
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function getInfo(AccountIdentifier $params): AccountInfo
    {
        try {
            if (!isset($params->site_builder_user_id)) {
                $this->errorResult('User id is required!');
            }

            return $this->_getInfo($params->site_builder_user_id, $params->domain_name, 'Account data obtained');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function _getInfo(string $id, ?string $site, string $message): AccountInfo
    {
        $accountInfo = $this->api()->getInfo($id, $site);

        return AccountInfo::create($accountInfo)->setMessage($message);
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function login(AccountIdentifier $params): LoginResult
    {
        try {
            if (!isset($params->site_builder_user_id)) {
                $this->errorResult('User id is required!');
            }

            $url = $this->api()->login($params->site_builder_user_id);

            return new LoginResult(['login_url' => $url]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function changePackage(ChangePackageParams $params): AccountInfo
    {
        try {
            if (!isset($params->site_builder_user_id)) {
                $this->errorResult('User id is required!');
            }

            if (!isset($params->domain_name)) {
                $this->errorResult('Domain name is required!');
            }

            $this->api()->changePackage($params->site_builder_user_id, $params->domain_name, $params->package_reference, (int)$params->billing_cycle_months);

            return $this->_getInfo($params->site_builder_user_id, null, 'Package changed');
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function suspend(AccountIdentifier $params): AccountInfo
    {
        try {
            if (!isset($params->site_builder_user_id)) {
                $this->errorResult('User id is required!');
            }

            $this->api()->suspend($params->site_builder_user_id);

            return $this->_getInfo($params->site_builder_user_id, null, 'Account suspended');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function unSuspend(UnSuspendParams $params): AccountInfo
    {
        try {
            if (!isset($params->site_builder_user_id)) {
                $this->errorResult('User id is required!');
            }

            $this->api()->unsuspend($params->site_builder_user_id);

            return $this->_getInfo($params->site_builder_user_id, null, 'Account unsuspended');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function terminate(AccountIdentifier $params): ResultData
    {
        try {
            if (!isset($params->site_builder_user_id)) {
                $this->errorResult('User id is required!');
            }

            $this->api()->terminate($params->site_builder_user_id);

            return $this->okResult('Account Terminated');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }

    /**
     * @return no-return
     *
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    protected function handleException(\Throwable $e, $params = null): void
    {
        if (($e instanceof RequestException) && $e->hasResponse()) {
            $response = $e->getResponse();

            $body = trim($response === null ? '' : $response->getBody()->__toString());
            $responseData = json_decode($body, true);

            $errorMessage = $responseData["error"]['message'] ?? $response->getReasonPhrase();

            $this->errorResult(
                sprintf('Provider API Error: %s', $errorMessage),
                ['response_data' => $responseData],
                [],
                $e
            );
        }

        throw $e;
    }

    public function api(): WeeblyApi
    {
        if (isset($this->api)) {
            return $this->api;
        }

        $client = new Client([
            'base_uri' => 'https://api.weeblycloud.com/',
            RequestOptions::HEADERS => [
                'User-Agent' => 'upmind/provision-provider-website-builders v1.0',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Public-Key' => $this->configuration->api_key,
            ],
            RequestOptions::TIMEOUT => 30, // seconds
            RequestOptions::CONNECT_TIMEOUT => 5, // seconds
            'handler' => $this->getGuzzleHandlerStack()
        ]);

        return $this->api = new WeeblyApi($client, $this->configuration);
    }
}
