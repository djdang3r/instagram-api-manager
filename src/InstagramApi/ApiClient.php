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

    public function __construct(string $baseUrl, string $version = '', int $timeout = 30, ?Client $httpClient = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->version = $version;
        $this->client = $httpClient ?? new Client([
            'timeout' => $timeout,
        ]);
    }

    /**
     * Override base URL for this instance.
     */
    public function withBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Override API version for this instance.
     */
    public function withVersion(string $version): static
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Envía un mensaje con archivo multimedia, detectando automáticamente
     * si es URL (JSON) o archivo local (multipart/form-data con filedata).
     */
    public function sendMediaRequest(
        string $endpoint,
        array $recipient,
        string $mediaType,
        string|array $media,
        array $extraPayload = [],
        array $query = [],
        string $platform = 'instagram'
    ): mixed {
        if (is_array($media)) {
            return $this->request('POST', $endpoint, [], array_merge([
                'recipient' => $recipient,
                'message' => ['attachments' => array_map(fn($url) => [
                    'type' => 'image',
                    'payload' => ['url' => $url],
                ], $media)],
            ], $extraPayload), $query);
        }

        if ($this->isLocalFile($media)) {
            $filePath = $media instanceof \SplFileInfo ? $media->getRealPath() : $media;
            
            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new \RuntimeException("File not found or not readable: {$filePath}");
            }

            $this->validateMediaFile($filePath, $mediaType, $platform);

            $stream = fopen($filePath, 'r');
            if (!$stream) {
                throw new \RuntimeException("Failed to open file: {$filePath}");
            }

            return $this->request('POST', $endpoint, [], [
                'multipart' => [
                    ['name' => 'recipient', 'contents' => json_encode($recipient)],
                    ['name' => 'message', 'contents' => json_encode([
                        'attachment' => ['type' => $mediaType, 'payload' => ['is_reusable' => true]],
                    ])],
                    ['name' => 'filedata', 'contents' => $stream, 'filename' => basename($filePath)],
                ],
            ], $query);
        }

        return $this->request('POST', $endpoint, [], array_merge([
            'recipient' => $recipient,
            'message' => [
                'attachment' => ['type' => $mediaType, 'payload' => ['url' => $media, 'is_reusable' => true]],
            ],
        ], $extraPayload), $query);
    }

    protected function isLocalFile($media): bool
    {
        if ($media instanceof \SplFileInfo) return true;
        if (is_string($media) && !str_starts_with($media, 'http://') && !str_starts_with($media, 'https://') && file_exists($media)) return true;
        return false;
    }

    protected function getMimeType(string $type, string $path): string
    {
        $map = ['image' => 'image/jpeg', 'audio' => 'audio/mpeg', 'video' => 'video/mp4', 'file' => 'application/octet-stream'];
        if ($finfo = finfo_open(FILEINFO_MIME_TYPE)) {
            $detected = finfo_file($finfo, $path);
            finfo_close($finfo);
            return $detected ?: ($map[$type] ?? 'application/octet-stream');
        }
        return $map[$type] ?? 'application/octet-stream';
    }

    protected function validateMediaFile(string $filePath, string $mediaType, string $platform = 'instagram'): void
    {
        $maxSize = config("{$platform}.media.max_file_size.{$mediaType}");
        $allowedTypes = config("{$platform}.media.allowed_types.{$mediaType}");

        if (!is_array($allowedTypes)) {
            throw new \RuntimeException("MIME type configuration for '{$mediaType}' on '{$platform}' is invalid.");
        }

        $fileSize = filesize($filePath);
        if ($fileSize > $maxSize) {
            $maxMB = round($maxSize / 1024 / 1024, 1);
            $actualMB = round($fileSize / 1024 / 1024, 1);
            throw new \RuntimeException("File size ({$actualMB}MB) exceeds {$mediaType} limit of {$maxMB}MB for {$platform}.");
        }

        $mime = $this->getMimeType($mediaType, $filePath);
        if (!in_array($mime, $allowedTypes)) {
            throw new \RuntimeException("MIME type '{$mime}' not allowed for {$mediaType} on {$platform}. Allowed: " . implode(', ', $allowedTypes));
        }
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
        ?string $customBaseUrl = null
    ): mixed {
        try {
            $baseUrl = $customBaseUrl ?: $this->baseUrl;
            $url = $this->buildUrl($endpoint, $params, $isFullUrl, $baseUrl);

            $options = [
                'headers' => array_merge([
                    'Accept' => 'application/json',
                ], $headers),
            ];

            // Manejar diferentes tipos de datos
            if (isset($data['multipart'])) {
                $options['multipart'] = $data['multipart'];
            } elseif (isset($data['form_params'])) {
                $options['form_params'] = $data['form_params'];
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
    protected function buildUrl(string $endpoint, array $params, bool $isFullUrl = false, ?string $baseUrl = null): string
    {
        $baseUrl = $baseUrl ?: $this->baseUrl;

        if ($isFullUrl) {
            $url = $endpoint;
        } else {
            // Reemplazar placeholders
            $processedEndpoint = str_replace(
                array_map(fn($k) => "{${k}}", array_keys($params)),
                array_values($params),
                $endpoint
            );

            // Construir URL con versión si está definida
            $url = $this->version
                ? "{$baseUrl}/{$this->version}/{$processedEndpoint}"
                : "{$baseUrl}/{$processedEndpoint}";
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