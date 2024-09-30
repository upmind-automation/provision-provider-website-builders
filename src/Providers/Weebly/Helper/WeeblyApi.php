<?php

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Weebly\Helper;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\CreateParams;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Weebly\Data\Configuration;
use Upmind\ProvisionBase\Exception\ProvisionFunctionError;

class WeeblyApi
{
    protected Client $client;

    protected Configuration $configuration;

    public function __construct(Client $client, Configuration $configuration)
    {
        $this->client = $client;
        $this->configuration = $configuration;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function makeRequest(string $command, ?array $params = null, ?array $body = null, ?string $method = 'GET'): ?array
    {
        $requestParams = [];

        $hashData = $method . "\n" . $command . "\n";

        if ($body) {
            $body = json_encode($body);
            $requestParams['body'] = $body;
            $hashData .= $body;
        }

        $hash = hash_hmac('SHA256', $hashData, $this->configuration->api_secret);
        $hash = base64_encode($hash);

        $requestParams['headers'] = [
            'X-Signed-Request-Hash' => $hash
        ];

        $response = $this->client->request($method, $command, $requestParams);
        $result = $response->getBody()->getContents();

        $response->getBody()->close();

        if ($result === "") {
            return null;
        }

        return $this->parseResponseData($result);
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     */
    private function parseResponseData(string $result): array
    {
        $parsedResult = json_decode($result, true);

        if (!$parsedResult) {
            throw ProvisionFunctionError::create('Unknown Provider API Error')
                ->withData([
                    'response' => $result,
                ]);
        }

        if ($error = $this->getResponseErrorMessage($parsedResult)) {
            throw ProvisionFunctionError::create($error)
                ->withData([
                    'response' => $parsedResult,
                ]);
        }

        return $parsedResult;
    }

    protected function getResponseErrorMessage($responseData): ?string
    {
        if (isset($responseData['error'])) {
            return $responseData['error'];
        }

        return null;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createUser(CreateParams $params): string
    {
        @[$firstName, $lastName] = explode(' ', $params->customer_name, 2);

        $body = [
            'email' => $params->customer_email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            "language" => $params->language_code ?? 'en',
        ];

        if ($params->password != null) {
            $body['password'] = $params->password;
        }

        $response = $this->makeRequest('user', null, $body, 'POST');
        return $response['user']['user_id'];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInfo(string $userId, string $siteId): array
    {
        if (!is_numeric($siteId)) {
            // siteId is domain - lookup real site id
            $siteId = $this->getSiteId($userId, $siteId);
        }

        $site = $this->makeRequest("user/$userId/site/$siteId")['site'];
        $plan = $this->getSitePlan($userId, $siteId);

        return [
            'site_builder_user_id' => $userId,
            'account_reference' => $siteId,
            'domain_name' => $site['domain'],
            'package_reference' => $plan['name'],
            'suspended' => $site['suspended'],
            'ip_address' => null, // can we get this somehow ?
            'is_published' => $site['publish_state'] == 'published',
            'has_ssl' => $site['allow_ssl'],
        ];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function suspend(string $userId, string $siteId): void
    {
        if (!is_numeric($siteId)) {
            // siteId is domain - lookup real site id
            $siteId = $this->getSiteId($userId, $siteId);
        }

        $this->makeRequest("user/$userId/site/$siteId/disable", null, null, 'POST');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function unsuspend(string $userId, string $siteId): void
    {
        if (!is_numeric($siteId)) {
            // siteId is domain - lookup real site id
            $siteId = $this->getSiteId($userId, $siteId);
        }

        $this->makeRequest("user/$userId/site/$siteId/enable", null, null, 'POST');
    }

    public function getSitePlan(string $userId, string $siteId): array
    {
        $plans = $this->makeRequest("user/$userId/site/$siteId/plan")['plans'];

        return current($plans) ?: [];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function changePackage(string $userId, string $siteId, string $planId, int $term): void
    {
        if (!is_numeric($siteId)) {
            // siteId is domain - lookup real site id
            $siteId = $this->getSiteId($userId, $siteId);
        }

        $body = ['plan_id' => $planId, 'term' => $term];

        $this->makeRequest("user/$userId/site/$siteId/plan", null, $body, 'POST');
    }

    public function changeDomain(string $userId, string $siteId, string $domain): void
    {
        if (!is_numeric($siteId)) {
            // siteId is domain - lookup real site id
            $siteId = $this->getSiteId($userId, $siteId);
        }

        $body = ['domain' => $domain];

        $this->makeRequest("user/$userId/site/$siteId", null, $body, 'PATCH');
    }

    public function findPlan(string $packageReference): array
    {
        $plans = $this->makeRequest('plan')['plans'];

        $matchBy = ['name'];
        if (is_numeric($packageReference)) {
            array_unshift($matchBy, 'plan_id');
        }

        foreach ($matchBy as $field) {
            foreach ($plans as $plan) {
                if ($plan[$field] === $packageReference) {
                    return $plan;
                }
            }
        }

        throw ProvisionFunctionError::create("Plan $packageReference not found");
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(string $userId): string
    {
        $response = $this->makeRequest("user/$userId/loginLink");

        return $response['link'];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function terminate(string $userId, string $siteId): void
    {
        if (!is_numeric($siteId)) {
            // siteId is domain - lookup real site id
            $siteId = $this->getSiteId($userId, $siteId);
        }

        $this->makeRequest("user/$userId/site/$siteId", null, null, 'DELETE');
    }

    /**
     * @return string Site id
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createDomain(string $userId, string $domain, string $planId, int $term): string
    {
        $body = [
            'domain' => $domain,
            'plan_id' => $planId,
            'term' => $term,
        ];

        $site = $this->makeRequest("user/$userId/site", null, $body, 'POST')['site'];
        return $site['site_id'];
    }

    /**
     * Since Weebly sometimes prepends www. and toggles to lowercase, check the 2
     * given domains for equality with this in mind.
     */
    public function domainsAreEqual(string $domain1, string $domain2): bool
    {
        return Str::start(strtolower($domain1), 'www.') === Str::start(strtolower($domain2), 'www.');
    }

    /**
     * @param string $userId
     * @param string $domain
     * @return string
     */
    private function getSiteId(string $userId, string $domain): string
    {
        $response = $this->makeRequest("user/$userId/site");

        foreach ($response['sites'] as $s) {
            if ($this->domainsAreEqual($s['domain'], $domain)) {
                return $s['site_id'];
            }
        }

        throw ProvisionFunctionError::create("Domain $domain not found")
        ->withData([
            'response' => $response,
        ]);
    }
}
