<?php

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Weebly\Helper;

use GuzzleHttp\Client;
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
    public function getInfo(string $id, ?string $domain): array
    {
        $response = $this->makeRequest("user/$id/site");

        if ($domain !== null) {
            foreach ($response['sites'] as $s) {
                if ($s['domain'] === $domain) {
                    $site = $s;
                    break;
                }
            }
        } else {
            if (count($response['sites']) > 0) {
                $site = $response['sites'][0];
            }
        }

        if ($domain && !isset($site)) {
            throw ProvisionFunctionError::create("Domain $domain not found")
                ->withData([
                    'response' => $response,
                ]);
        }

        $plan = '-';
        if (isset($site)) {
            $siteId = $site['site_id'];
            $plans = $this->makeRequest("user/$id/site/$siteId/plan");
            foreach ($plans['plans'] as $p) {
                $plan = $p['plan_id'];
                break;
            }
        }

        return [
            'site_builder_user_id' => $id,
            'account_reference' => '-',
            'domain_name' => isset($site) ? $site['domain'] : null,
            'package_reference' => $plan,
            'suspended' => isset($site) ? $site['suspended'] : null,
            'ip_address' => null,
            'is_published' => isset($site) ? $site['publish_state'] == 'published' : null,
            'has_ssl' => isset($site) ? $site['allow_ssl'] : null,
            'site_count' => count($response['sites'])
        ];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function suspend(string $id): void
    {
        $this->makeRequest("user/$id/disable", null, null, 'POST');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function unsuspend(string $id): void
    {
        $this->makeRequest("user/$id/enable", null, null, 'POST');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function changePackage(string $id, string $domain, string $packageId, ?int $term): void
    {
        if ($term && $term != 1 && $term != 12) {
            throw ProvisionFunctionError::create('billing_cycle_months must be 1 or 12');
        }

        $siteId = $this->getSiteId($id, $domain);
        $this->makeRequest("user/$id/site/$siteId/plan", null, ['plan_id' => $packageId, 'term' => $term ?? 1], 'POST');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(string $id): string
    {
        $response = $this->makeRequest("user/$id/loginLink");

        return $response['link'];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function terminate(string $id): void
    {
        $this->makeRequest("user/$id", null, null, 'DELETE');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createDomain(string $userId, string $domain): void
    {
        $this->makeRequest("user/$userId/site", null, ['domain' => $domain], 'POST');
    }

    /**
     * @param string $id
     * @param string $domain
     * @return string
     */
    private function getSiteId(string $id, string $domain): string
    {
        $response = $this->makeRequest("user/$id/site");

        foreach ($response['sites'] as $s) {
            if ($s['domain'] === $domain) {
                $siteId = $s['site_id'];
                break;
            }
        }

        if (!isset($siteId)) {
            throw ProvisionFunctionError::create("Domain $domain not found")
                ->withData([
                    'response' => $response,
                ]);
        }

        return $siteId;
    }
}
