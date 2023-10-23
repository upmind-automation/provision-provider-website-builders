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

    public function createUser(CreateParams $params): string
    {
        @[$firstName, $lastName] = explode(' ', $params->customer_name, 2);

        $body = [
            'clientId' => $params->customer_id,
            'domainName' => $params->domain_name,
            'planId' => $params->package_reference,
            'email' => $params->customer_email,
            'firstName' => $firstName,
            'lastName' => $lastName ?? 'UNKNOWN',
        ];

        $response = $this->makeRequest("create", null, $body, 'POST');

        return (string)$response['data']['userGuid'];
    }

    public function getInfo(string $id): array
    {
        $query = [
            'clientId' => $this->configuration->client_id,
            'userGuid' => $id,
        ];

        $response = $this->makeRequest("getInfo", $query);

        return [
            'account_reference' => (string)$response['data']['userGuid'],
            'domain_name' => $response['data']['siteDomain'],
            'package_reference' => $response['data']['planId'],
            'suspended' => $response['data']['userStatus'] == 'S',
        ];
    }

    public function suspend(string $id): void
    {
        $query = [
            'clientId' => $this->configuration->client_id,
            'userGuid' => $id,
        ];

        $this->makeRequest("suspend", $query);
    }

    public function unsuspend(string $id): void
    {
        $query = [
            'clientId' => $this->configuration->client_id,
            'userGuid' => $id,
        ];

        $this->makeRequest("unsuspend", $query);
    }

    public function terminate(string $id): void
    {
        $query = [
            'clientId' => $this->configuration->client_id,
            'userGuid' => $id,
        ];

        $this->makeRequest("terminate", $query);
    }

    public function changePackage(string $id, string $packageId): void
    {
        $query = [
            'clientId' => $this->configuration->client_id,
            'userGuid' => $id,
            'planId' => $packageId,
        ];

        $this->makeRequest("changePackage", $query);
    }

    public function login(string $id): string
    {
        $query = [
            'clientId' => $this->configuration->client_id,
            'userGuid' => $id,
        ];

        $response = $this->makeRequest("login", $query);

        return $response['data']['loginUrl'];
    }
}
