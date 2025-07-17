<?php

declare(strict_types=1);

namespace Creeble\Http;

use Creeble\Exceptions\AuthenticationException;
use Creeble\Exceptions\CreebleException;
use Creeble\Exceptions\RateLimitException;
use Creeble\Exceptions\ValidationException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Client for Creeble API
 */
class Client
{
    private GuzzleClient $httpClient;
    private string $apiKey;
    private string $baseUrl;

    public function __construct(
        string $apiKey,
        string $baseUrl,
        array $options = []
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');

        $defaultOptions = [
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'creeble-php/1.0',
            ],
        ];

        $this->httpClient = new GuzzleClient(array_merge($defaultOptions, $options));
    }

    /**
     * Make a GET request
     */
    public function get(string $uri, array $params = []): array
    {
        $options = [];
        
        if (!empty($params)) {
            $options['query'] = $params;
        }

        return $this->makeRequest('GET', $uri, $options);
    }

    /**
     * Make a POST request
     */
    public function post(string $uri, array $data = []): array
    {
        $options = [];
        
        if (!empty($data)) {
            $options['json'] = $data;
        }

        return $this->makeRequest('POST', $uri, $options);
    }

    /**
     * Make a PUT request
     */
    public function put(string $uri, array $data = []): array
    {
        $options = [];
        
        if (!empty($data)) {
            $options['json'] = $data;
        }

        return $this->makeRequest('PUT', $uri, $options);
    }

    /**
     * Make a DELETE request
     */
    public function delete(string $uri): array
    {
        return $this->makeRequest('DELETE', $uri);
    }

    /**
     * Make an HTTP request
     *
     * @throws CreebleException When the request fails
     * @return array The parsed response data
     */
    private function makeRequest(string $method, string $uri, array $options = []): array
    {
        // Add API key to headers
        $options['headers'] = array_merge(
            $options['headers'] ?? [],
            ['X-API-Key' => $this->apiKey]
        );

        try {
            $response = $this->httpClient->request(
                $method,
                $this->baseUrl . '/api' . $uri,
                $options
            );

            return $this->parseResponse($response);
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (ServerException $e) {
            throw new CreebleException(
                'Server error: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            throw new CreebleException(
                'Request failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return [];
    }

    /**
     * Parse the response from Creeble API
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new CreebleException('Invalid JSON response from API');
        }

        return $data;
    }

    /**
     * Handle client exceptions (4xx errors)
     */
    private function handleClientException(ClientException $e): void
    {
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        $data = json_decode($body, true) ?? [];
        $message = $data['message'] ?? $e->getMessage();

        switch ($statusCode) {
            case 401:
                throw new AuthenticationException($message);
            case 422:
                throw new ValidationException($message, $data['errors'] ?? []);
            case 429:
                $retryAfter = $response->getHeaderLine('Retry-After');
                throw new RateLimitException($message, (int) $retryAfter);
            default:
                throw new CreebleException($message, $statusCode, $e);
        }
    }
}