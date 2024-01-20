<?php

namespace Upmind\ProvisionProviders\WebsiteBuilders\Providers\Websitecom\Helper;

use GuzzleHttp\Client;
use Upmind\ProvisionProviders\WebsiteBuilders\Data\CreateParams;
use Upmind\ProvisionProviders\WebsiteBuilders\Providers\Websitecom\Data\Configuration;
use Upmind\ProvisionBase\Exception\ProvisionFunctionError;

class WebsitecomApi
{
    protected Client $client;

    protected Configuration $configuration;

    public function __construct(Client $client, Configuration $configuration)
    {
        $this->client = $client;
        $this->configuration = $configuration;
    }

    public function makeRequest(string $command, ?array $params = null, ?array $body = null, ?string $method = 'GET'): ?array
    {
        $requestParams = [];

        if ($params) {
            $requestParams['query'] = $params;
        }

        if ($body) {
            $requestParams['body'] = json_encode($body);
        }

        $response = $this->client->request($method, $command, $requestParams);
        $result = $response->getBody()->getContents();

        $response->getBody()->close();

        if ($result === "") {
            return null;
        }

        return $this->parseResponseData($result);
    }

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
        if (!$responseData['success']) {
            if (isset($responseData['message'])) {
                $errorMessage = $responseData['message'];
            }
        }

        return $errorMessage ?? null;
    }

    /**
     * @return array [(int)site_builder_user_id, (string)account_reference]
     */
    public function createUser(CreateParams $params): array
    {
        @[$firstName, $lastName] = explode(' ', $params->customer_name, 2);

        $body = [
            'clientId' => $params->site_builder_user_id ?: 0,
            'domainName' => $params->domain_name,
            'planId' => $params->package_reference,
            'email' => $params->customer_email,
            'firstName' => $firstName,
            'lastName' => $lastName ?? 'UNKNOWN',
        ];

        $response = $this->makeRequest("create", null, $body, 'POST');

        return [
            (int)($response['data']['clientId'] ?? $params->site_builder_user_id) ?: null,
            (string)$response['data']['userGuid']
        ];
    }

    public function getInfo(?int $siteBuilderUserId, string $id): array
    {
        $query = [
            'clientId' => $siteBuilderUserId,
            'userGuid' => $id,
        ];

        $response = $this->makeRequest("getInfo", $query);

        return [
            'site_builder_user_id' => $siteBuilderUserId,
            'account_reference' => (string)$response['data']['userGuid'],
            'domain_name' => $response['data']['siteDomain'],
            'package_reference' => $response['data']['planId'],
            'suspended' => $response['data']['userStatus'] == 'S',
        ];
    }

    public function suspend(int $siteBuilderUserId, string $id): void
    {
        $query = [
            'clientId' => $siteBuilderUserId,
            'userGuid' => $id,
        ];

        $this->makeRequest("suspend", $query);
    }

    public function unsuspend(int $siteBuilderUserId, string $id): void
    {
        $query = [
            'clientId' => $siteBuilderUserId,
            'userGuid' => $id,
        ];

        $this->makeRequest("unsuspend", $query);
    }

    public function terminate(int $siteBuilderUserId, string $id): void
    {
        $query = [
            'clientId' => $siteBuilderUserId,
            'userGuid' => $id,
        ];

        $this->makeRequest("terminate", $query);
    }

    public function changePackage(int $siteBuilderUserId, string $id, string $packageId): void
    {
        $query = [
            'clientId' => $siteBuilderUserId,
            'userGuid' => $id,
            'planId' => $packageId,
        ];

        $this->makeRequest("changePackage", $query);
    }

    public function login(int $siteBuilderUserId, string $id): string
    {
        $query = [
            'clientId' => $siteBuilderUserId,
            'userGuid' => $id,
        ];

        $response = $this->makeRequest("login", $query);

        return $response['data']['loginUrl'];
    }
}
