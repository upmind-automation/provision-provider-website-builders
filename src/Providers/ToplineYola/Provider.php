<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\ToplineYola;

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
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\ToplineYola\Data\Configuration;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\ToplineYola\Helper\YolaApi;

/**
 * Yola provider.
 */
class Provider extends Category implements ProviderInterface
{
    protected Configuration $configuration;

    protected ?YolaApi $api = null;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('Topline Yola')
            ->setDescription('Create, manage and log into Yola site builder accounts via Topline Cloud Services')
            ->setLogoUrl('https://api.upmind.io/images/logos/provision/yola-logo@2x.png');
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function create(CreateParams $params): AccountInfo
    {
        if (!isset($params->domain_name)) {
            $this->errorResult('Domain name is required!');
        }

        try {
            if (!isset($params->site_builder_user_id)) {
                $userRef = $this->api()->createUser($params);
            } else {
                $userRef = $params->site_builder_user_id;
                $this->api()->addDomain($userRef, $params->domain_name);
            }

        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }

        try {
            $domainId = $this->api()->getDomainId($userRef, $params->domain_name);

            $this->api()->changePackage($domainId, $params->package_reference);
        } catch (\Throwable $e) {
            $errorMessage = "Unknown error";
            if (($e instanceof RequestException) && $e->hasResponse()) {
                $response = $e->getResponse();
                $errorMessage = $response->getReasonPhrase();
            }

            return $this->_getInfo($userRef, $params->domain_name, "Package  {$params->package_reference} error: {$errorMessage}");
        }

        return $this->_getInfo($userRef, $params->domain_name, 'Account data obtained');
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function getInfo(AccountIdentifier $params): AccountInfo
    {
        if (!isset($params->site_builder_user_id)) {
            $this->errorResult('Site builder user id is required!');
        }

        try {
            return $this->_getInfo($params->site_builder_user_id, $params->account_reference, 'Account data obtained');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function _getInfo(string $userId, string $domainId, string $message): AccountInfo
    {
        $accountInfo = $this->api()->getInfo($userId, $domainId);
        if (empty($accountInfo['site_builder_user_id'])) {
            $accountInfo['site_builder_user_id'] = null;
        }

        return AccountInfo::create($accountInfo)->setMessage($message);
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function login(AccountIdentifier $params): LoginResult
    {
        try {
            $url = $this->api()->login($params->account_reference);

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
        if (!isset($params->site_builder_user_id)) {
            $this->errorResult('Site builder user id is required!');
        }

        try {
            $this->api()->changePackage($params->account_reference, $params->package_reference);

            return $this->_getInfo($params->site_builder_user_id, $params->account_reference, 'Package changed');
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
        if (!isset($params->site_builder_user_id)) {
            $this->errorResult('Site builder user id is required!');
        }

        try {
            $this->api()->suspend($params->site_builder_user_id, $params->account_reference);

            return $this->_getInfo($params->site_builder_user_id, $params->account_reference, 'Account suspended');
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
        if (!isset($params->site_builder_user_id)) {
            $this->errorResult('Site builder user id is required!');
        }

        try {
            $this->api()->unsuspend($params->site_builder_user_id, $params->account_reference);

            $this->api()->changePackage($params->account_reference, $params->package_reference);

            return $this->_getInfo($params->site_builder_user_id, $params->account_reference, 'Account unsuspended');
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
        if (!isset($params->site_builder_user_id)) {
            $this->errorResult('Site builder user id is required!');
        }

        try {
            $this->api()->terminate($params->site_builder_user_id, $params->account_reference);

            return $this->okResult('Account Terminated');
        } catch (Throwable $e) {
            $this->handleException($e);
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

            $errorMessage = $response->getReasonPhrase();

            $this->errorResult(
                sprintf('Provider API Error: %s', $errorMessage),
                ['response_data' => $responseData],
                [],
                $e
            );
        }

        throw $e;
    }

    public function api(): YolaApi
    {
        if (isset($this->api)) {
            return $this->api;
        }

        $client = new Client([
            'base_uri' => $this->resolveAPIURL(),
            RequestOptions::HEADERS => [
                'User-Agent' => 'upmind/provision-provider-website-builders v1.0',
                'SBS-AgentID' => $this->configuration->agent_id ?? null
            ],
            RequestOptions::TIMEOUT => 30, // seconds
            RequestOptions::CONNECT_TIMEOUT => 5, // seconds
            'handler' => $this->getGuzzleHandlerStack()
        ]);

        return $this->api = new YolaApi($client, $this->configuration);
    }

    private function resolveAPIURL(): string
    {
        return $this->configuration->sandbox
            ? 'https://sandbox.sbsapi.com'
            : 'https://sbsapi.com';
    }
}
