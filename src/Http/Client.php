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
    private array $requestInterceptors = [];
    private array $responseInterceptors = [];
    private array $errorInterceptors = [];
    private array $cache = [];
    private bool $cacheEnabled;
    private int $defaultCacheTtl;
    private bool $debugEnabled = false;

    public function __construct(
        string $apiKey,
        string $baseUrl,
        array $options = []
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cacheEnabled = $options['enable_cache'] ?? true;
        $this->defaultCacheTtl = $options['cache_ttl'] ?? 300; // 5 minutes
        $this->debugEnabled = $options['debug'] ?? false;

        $defaultOptions = [
            'timeout' => $options['timeout'] ?? 30,
            'connect_timeout' => $options['connect_timeout'] ?? 10,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'creeble-php/1.1',
            ],
        ];

        $this->httpClient = new GuzzleClient(array_merge($defaultOptions, $options['guzzle'] ?? []));
    }

    /**
     * Add request interceptor
     */
    public function addRequestInterceptor(callable $interceptor): int
    {
        $this->requestInterceptors[] = $interceptor;
        return count($this->requestInterceptors) - 1;
    }

    /**
     * Add response interceptor
     */
    public function addResponseInterceptor(callable $interceptor): int
    {
        $this->responseInterceptors[] = $interceptor;
        return count($this->responseInterceptors) - 1;
    }

    /**
     * Add error interceptor
     */
    public function addErrorInterceptor(callable $interceptor): int
    {
        $this->errorInterceptors[] = $interceptor;
        return count($this->errorInterceptors) - 1;
    }

    /**
     * Enable/disable debug mode
     */
    public function setDebug(bool $enabled): void
    {
        $this->debugEnabled = $enabled;
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Make a GET request
     */
    public function get(string $uri, array $params = []): array
    {
        if (empty($uri)) {
            throw new CreebleException('URI cannot be empty');
        }

        // Check cache first for GET requests
        $cacheKey = $this->getCacheKey('GET', $uri, $params);
        if ($this->cacheEnabled && isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if ($cached['expires'] > time()) {
                if ($this->debugEnabled) {
                    error_log("Creeble PHP SDK: Cache hit for {$uri}");
                }
                return $cached['data'];
            }
            unset($this->cache[$cacheKey]);
        }

        $options = [];
        
        if (!empty($params)) {
            $options['query'] = $this->processQueryParams($params);
        }

        $result = $this->makeRequest('GET', $uri, $options);

        // Cache successful GET responses
        if ($this->cacheEnabled) {
            $this->cache[$cacheKey] = [
                'data' => $result,
                'expires' => time() + $this->defaultCacheTtl
            ];
        }

        return $result;
    }

    /**
     * Process query parameters to handle arrays properly
     */
    private function processQueryParams(array $params): array
    {
        $processed = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $processed["{$key}[]"] = (string) $item;
                }
            } elseif ($value !== null) {
                $processed[$key] = (string) $value;
            }
        }
        return $processed;
    }

    /**
     * Generate cache key
     */
    private function getCacheKey(string $method, string $uri, array $params = []): string
    {
        return md5($method . ':' . $uri . ':' . serialize($params));
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
        $startTime = microtime(true);
        $fullUrl = $this->baseUrl . '/api' . $uri;
        
        // Add API key to headers
        $options['headers'] = array_merge(
            $options['headers'] ?? [],
            ['X-API-Key' => $this->apiKey]
        );

        // Apply request interceptors
        foreach ($this->requestInterceptors as $interceptor) {
            [$fullUrl, $options] = $interceptor($fullUrl, $options);
        }

        if ($this->debugEnabled) {
            error_log("Creeble PHP SDK: {$method} {$fullUrl}");
        }

        try {
            $response = $this->httpClient->request($method, $fullUrl, $options);
            $result = $this->parseResponse($response);

            // Apply response interceptors
            foreach ($this->responseInterceptors as $interceptor) {
                $result = $interceptor($result, $response);
            }

            if ($this->debugEnabled) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $dataCount = is_array($result['data'] ?? null) ? count($result['data']) : 1;
                error_log("Creeble PHP SDK: {$method} {$fullUrl} completed in {$duration}ms ({$dataCount} items)");
            }

            return $result;
        } catch (ClientException $e) {
            $this->handleException($e, $method, $fullUrl, $startTime);
        } catch (ServerException $e) {
            $this->handleException($e, $method, $fullUrl, $startTime);
        } catch (\Exception $e) {
            $this->handleException($e, $method, $fullUrl, $startTime);
        }

        return [];
    }

    /**
     * Handle exceptions with interceptors and logging
     */
    private function handleException(\Exception $e, string $method, string $url, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($this->debugEnabled) {
            error_log("Creeble PHP SDK: {$method} {$url} failed in {$duration}ms - {$e->getMessage()}");
        }

        // Apply error interceptors
        foreach ($this->errorInterceptors as $interceptor) {
            $e = $interceptor($e) ?? $e;
        }

        if ($e instanceof ClientException) {
            $this->handleClientException($e);
        } elseif ($e instanceof ServerException) {
            throw new CreebleException(
                'Server error: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        } else {
            throw new CreebleException(
                'Request failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
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