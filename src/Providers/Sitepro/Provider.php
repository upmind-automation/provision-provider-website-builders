<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Sitepro;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
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
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Sitepro\Data\Configuration;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Sitepro\Helper\SiteproApi;

/**
 * Site.pro provider.
 */
class Provider extends Category implements ProviderInterface
{
    protected Configuration $configuration;

    protected SiteproApi|null $api = null;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('Site.pro')
            ->setDescription('Create, manage and log into Site.pro site builder accounts')
            ->setLogoUrl('https://api.upmind.io/images/logos/provision/sitepro-logo@2x.png');
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function create(CreateParams $params): AccountInfo
    {
        if (empty($params->domain_name)) {
            $this->errorResult('Domain name is required!');
        }

        try {
            $this->api()->createSite($params);

            return AccountInfo::create([
                'account_reference' => $params->domain_name,
                'domain_name' => $params->domain_name,
                'package_reference' => $params->package_reference,
                'suspended' => false,
                'ip_address' => null,
                'is_published' => false,
                'has_ssl' => null,
            ])->setMessage('Account created');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function getInfo(AccountIdentifier $params): AccountInfo
    {
        try {
            $domain = $params->domain_name ?? $params->account_reference;

            return $this->_getInfo($domain, 'Account data obtained');
        } catch (\Throwable $e) {
            $this->handleException($e, $params);
        }
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function _getInfo(string $domain, string $message): AccountInfo
    {
        $accountInfo = $this->api()->getInfo($domain);

        return AccountInfo::create($accountInfo)->setMessage($message);
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function login(AccountIdentifier $params): LoginResult
    {
        try {
            $resellerClientAccountId = isset($params->site_builder_user_id)
                ? (int)$params->site_builder_user_id
                : null;

            $url = $this->api()->login(
                $params->domain_name ?? (string)$params->account_reference,
                $resellerClientAccountId
            );

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
        $this->errorResult('Operation not supported');
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    public function suspend(AccountIdentifier $params): AccountInfo
    {
        try {
            $domain = $params->domain_name ?? $params->account_reference;

            $this->api()->suspend($domain);

            return $this->_getInfo($domain, 'Website blocked');
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
            $domain = $params->domain_name ?? $params->account_reference;

            $this->api()->unsuspend($domain);

            return $this->_getInfo($domain, 'Website unblocked');
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
            $domain = $params->domain_name ?? $params->account_reference;

            $this->api()->terminate($domain);

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
        if ($e instanceof TransferException) {
            $errorMessage = 'Provider Connection Failed';
            $errorData = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ];

            if (($e instanceof RequestException) && $e->hasResponse()) {
                $response = $e->getResponse();
                $errorMessage = 'Provider API Error';

                $body = trim($response === null ? '' : $response->getBody()->__toString());
                $responseData = json_decode($body, true);

                $error = $responseData['error'] ?? null;
                $errorMessage =  $error ?? $response->getReasonPhrase();
                $errorData = [
                    'response_data' => $responseData
                ];
            }

            $this->errorResult($errorMessage, $errorData, [], $e);
        }

        throw $e;
    }

    public function api(): SiteproApi
    {
        if (isset($this->api)) {
            return $this->api;
        }

        $baseUri = 'https://site.pro/api/';

        $credentials = base64_encode("{$this->configuration->username}:{$this->configuration->password}");

        $client = new Client([
            'base_uri' => $baseUri,
            RequestOptions::HEADERS => [
                'User-Agent' => 'upmind/provision-provider-website-builders v1.0',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials,
            ],
            RequestOptions::COOKIES => new \GuzzleHttp\Cookie\CookieJar(),
            RequestOptions::TIMEOUT => 30, // seconds
            RequestOptions::CONNECT_TIMEOUT => 5, // seconds
            'handler' => $this->getGuzzleHandlerStack()
        ]);

        return $this->api = new SiteproApi($client, $this->configuration);
    }
}
