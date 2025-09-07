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

    public function __construct(string $baseUrl, string $version = '', int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->version = $version;
        $this->client = new Client([
            'timeout' => $timeout,
        ]);
    }

    /**
     * Realiza una petición HTTP hacia la API
     */
    public function request(
        string $method,
        string $endpoint,
        array $params = [],
        mixed $data = null,
        array $query = [],
        array $headers = [],
        bool $isFullUrl = false,
        string $customBaseUrl = null
    ): mixed {
        try {
            $baseUrl = $customBaseUrl ?: $this->baseUrl;
            $url = $this->buildUrl($endpoint, $params, $query, $isFullUrl, $baseUrl);

            $options = [
                'headers' => array_merge([
                    'Accept' => 'application/json',
                ], $headers),
            ];

            // Manejar diferentes tipos de datos
            if (isset($data['multipart'])) {
                $options['multipart'] = $data['multipart'];
            } elseif (is_resource($data)) {
                $options['body'] = $data;
            } elseif (!empty($data)) {
                if ($method === 'GET') {
                    // Para GET, los datos van en query string
                    $query = array_merge($query, $data);
                } else {
                    // Para POST, determinar si usar form_params o json
                    $isFormData = isset($data['multipart']) || (isset($headers['Content-Type']) && 
                                strpos($headers['Content-Type'], 'multipart/form-data') !== false);
                    
                    if ($isFormData) {
                        $options['form_params'] = $data;
                    } else {
                        $options['json'] = $data;
                        $options['headers']['Content-Type'] = 'application/json';
                    }
                }
            }

            // Agregar query parameters a la URL
            if (!empty($query)) {
                $separator = strpos($url, '?') === false ? '?' : '&';
                $url .= $separator . http_build_query($query);
            }

            Log::channel('instagram')->debug('Solicitud API:', [
                'method' => $method,
                'url' => $url,
                'options' => $options
            ]);

            $response = $this->client->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::channel('instagram')->info('Respuesta exitosa:', [
                    'url' => $url,
                    'status' => $statusCode,
                    'response' => $content
                ]);

                return json_decode($content, true) ?? $content;
            }

            Log::channel('instagram')->warning('Error en respuesta:', [
                'status' => $statusCode,
                'response' => $content
            ]);

            throw new ApiException('Error en la API', $statusCode);

        } catch (GuzzleException $e) {
            Log::channel('instagram')->error('Error API:', [
                'url' => $url ?? $endpoint,
                'error' => $e->getMessage()
            ]);
            throw $this->handleException($e);
        }
    }

    /**
     * Construye la URL correctamente
     */
    protected function buildUrl(string $endpoint, array $params, array $query = [], bool $isFullUrl = false, string $baseUrl = null): string
    {
        $baseUrl = $baseUrl ?: $this->baseUrl;
        
        if ($isFullUrl) {
            $url = $endpoint;
        } else {
            // Reemplazar placeholders
            $processedEndpoint = str_replace(
                array_map(fn($k) => '{' . $k . '}', array_keys($params)),
                array_values($params),
                $endpoint
            );
            
            // Construir URL con versión si está definida
            $url = $this->version ? $baseUrl . '/' . $this->version . '/' . $processedEndpoint 
                                  : $baseUrl . '/' . $processedEndpoint;
        }

        Log::channel('instagram')->debug('URL construida:', ['url' => $url]);
        return $url;
    }

    /**
     * Manejo de excepciones
     */
    protected function handleException(GuzzleException $e): ApiException
    {
        $statusCode = 500;
        $body = [];
        $message = $e->getMessage();

        if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            $body = json_decode($content, true) ?? [];
            $message = $body['error']['message'] ?? $message;

            Log::channel('instagram')->error('Error detallado:', [
                'status' => $statusCode,
                'body' => $body,
                'headers' => $response->getHeaders(),
            ]);
        }

        return new ApiException($message, $statusCode, $body);
    }
}