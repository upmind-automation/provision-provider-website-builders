<?php

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Yola\Helper;

use GuzzleHttp\Client;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\CreateParams;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Yola\Data\Configuration;
use Upmind\ProvisionBase\Exception\ProvisionFunctionError;

class YolaApi
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

        if ($params) {
            $requestParams['query'] = $params;
        }

        if ($body) {
            $requestParams['body'] = json_encode($body);
        }

        $expiry = time() + (5 * 60);
        $authKey = $this->configuration->auth_key;

        if (!$body) {
            $contentType = "application/json";
            $baseString = $method . "\n" . "\n" . $contentType . "\n" . $expiry . "\n";
        } else {
            $contentType = "application/json;charset=utf-8";
            $baseString = $method . "\n" . md5($requestParams['body']) . "\n" . $contentType . "\n" . $expiry . "\n";
            $requestParams['headers']['Content-Length'] = strlen($requestParams['body']);
        }
        $signature = base64_encode(hash_hmac('sha1', $baseString, $authKey, true));

        $requestParams['headers']['Content-Type'] = $contentType;
        $requestParams['headers']['SBS-Expires'] = $expiry;
        $requestParams['headers']['SBS-Signature'] = $signature;


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

        return $parsedResult;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createUser(CreateParams $params): string
    {
        @[$firstName, $lastName] = explode(' ', $params->customer_name, 2);

        /**
         * @var string $firstName
         * @var string|null $lastName
         */
        $body = [
            'userID' => $params->customer_id ?? null,
            'domain' => $params->domain_name,
            'email' => $params->customer_email,
            'firstName' => $firstName,
            'lastName' => $lastName ?? 'UNKNOWN',
            'language' => $params->language ?? 'en',
        ];

        $brandId = $params->site_builder_user_id;

        $response = $this->makeRequest("/$brandId/users", null, $body, 'POST');
        return (string)$response['detail']['userID'];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInfo(string $brandId, string $userId): array
    {
        $response = $this->makeRequest("/$brandId/users/$userId", ['extras' => "subs"]);
        foreach ($response['detail']['subs'] as $subscription) {
            if (in_array($subscription['status'], [1, 2, 8])) {
                $planId = $subscription['planID'];
            }
        }

        return [
            'site_builder_user_id' => $userId,
            'account_reference' => (string)$response['detail']['brandID'],
            'domain_name' => $response['detail']['domain'],
            'package_reference' => $planId ?? "-",
            'suspended' => $response['detail']['status'] == 3,
        ];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function suspend(string $brandId, string $userId): void
    {
        $this->makeRequest("/$brandId/users/$userId/suspend", null, null, "PUT");
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function unsuspend(string $brandId, string $userId): void
    {
        $this->makeRequest("/$brandId/users/$userId/reactivate-all", null, null, "PUT");
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function terminate(string $brandId, string $userId): void
    {
        $this->makeRequest("/$brandId/users/$userId", null, null, "DELETE");
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function changePackage(string $brandId, string $userId, string $packageId): void
    {
        $body = [
            'planID' => $packageId,
        ];

        $response = $this->makeRequest("/$brandId/users/$userId", ['extras' => "subs"]);
        foreach ($response['detail']['subs'] as $subscription) {
            if (in_array($subscription['status'], [1, 2, 8])) {
                $planId = $subscription['ID'];
            }
        }

        if (isset($planId)) {
            $this->makeRequest("/$brandId/users/$userId/subscriptions/$planId", null, $body, 'DELETE');
        }

        $this->makeRequest("/$brandId/users/$userId/subscriptions", null, $body, 'POST');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(string $brandId, string $userId): string
    {
        $response = $this->makeRequest("/$brandId/users/$userId/sso/yola", ['noredir' => 1]);

        return $response['detail']['ssoURL'];
    }
}
