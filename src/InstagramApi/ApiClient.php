<?php

namespace ScriptDevelop\InstagramApiManager\InstagramApi;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use ScriptDevelop\InstagramApiManager\InstagramApi\Exceptions\ApiException;

class ApiClient
{
    protected Client $client;
    protected string $baseUrl;
    protected string $version;

    public function __construct(string $baseUrl, string $version = 'v19.0', int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->version = $version;
        $this->client = new Client([
            'base_uri' => $this->baseUrl . '/',
            'timeout' => $timeout,
        ]);
    }

    /**
     * Realiza una petición HTTP hacia la API, con manejo de parámetros, datos y headers.
     * 
     * @param string $method Método HTTP (GET, POST, etc).
     * @param string $endpoint Endpoint con o sin placeholders.
     * @param array $params Valores para sustituir placeholders en el endpoint.
     * @param mixed|null $data Cuerpo de la petición si aplica.
     * @param array $query Parámetros query string.
     * @param array $headers Headers adicionales.
     * @param bool $isMultimedia Si es true, indica petición para multimedia (sin versión).
     * 
     * @return mixed Array decodificado o string si multimedia.
     * @throws ApiException
     */
    public function request(
        string $method,
        string $endpoint,
        array $params = [],
        mixed $data = null,
        array $query = [],
        array $headers = [],
        bool $isMultimedia = false
    ): mixed {
        try {
            $url = $this->buildUrl($endpoint, $params, $query, $isMultimedia);

            $options = [
                'headers' => array_merge([
                    'Accept' => 'application/json',
                ], $headers),
            ];

            if (isset($data['multipart'])) {
                $options['multipart'] = $data['multipart'];
            } elseif (is_resource($data)) {
                $options['body'] = $data;
            } elseif (!empty($data)) {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, $url, $options);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $logArray = [
                    'URL' => $url,
                    'status_code' => $statusCode,
                ];

                if (!$isMultimedia) {
                    $logArray['response_body'] = $response->getBody()->getContents();
                }

                Log::channel('instagram')->info('Respuesta exitosa de la API.', $logArray);

                if ($isMultimedia) {
                    return $response->getBody()->getContents();
                }

                return json_decode($response->getBody(), true) ?: [];
            }

            Log::channel('instagram')->warning('ERROR Respuesta no exitosa de la API.', [
                'status_code' => $statusCode,
                'response_body' => $response->getBody()->getContents(),
            ]);

            throw new ApiException('Respuesta no exitosa de la API.', $statusCode);

        } catch (GuzzleException $e) {
            Log::channel('instagram')->error('API Error', [
                'url' => $url ?? $endpoint,
                'error' => $e->getMessage()
            ]);
            throw $this->handleException($e);
        }
    }

    /**
     * Request específico para multimedia, sin usar versión en URL.
     */
    public function requestMultimedia(
        string $method,
        string $endpoint,
        array $params = [],
        mixed $data = null,
        array $query = [],
        array $headers = []
    ) {
        return $this->request(
            $method,
            $endpoint,
            $params,
            $data,
            $query,
            $headers,
            true
        );
    }

    /**
     * Construye la URL con versión, parámetros y query string.
     */
    protected function buildUrl(string $endpoint, array $params, array $query = [], bool $isMultimedia = false): string
    {
        if ($isMultimedia) {
            $url = $endpoint;
        } else {
            $url = str_replace(
                array_map(fn ($k) => '{' . $k . '}', array_keys($params)),
                array_values($params),
                $this->version . '/' . $endpoint
            );
        }

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        Log::channel('instagram')->info('URL construida:', ['url' => $url]);

        return $url;
    }

    /**
     * Manejo avanzado de excepciones para ApiException.
     */
    protected function handleException(GuzzleException $e): ApiException
    {
        $statusCode = 500;
        $body = [];
        $message = $e->getMessage();

        if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);
            $message = $body['error']['message'] ?? $message;

            Log::channel('instagram')->error('Error en la respuesta de la API.', [
                'status_code' => $statusCode,
                'response_body' => $body,
                'headers' => $response->getHeaders(),
            ]);
        }

        return new ApiException($message, $statusCode, $body);
    }
}
