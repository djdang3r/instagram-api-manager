<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;
use ScriptDevelop\InstagramApiManager\Support\GraphBaseUrlResolver;
use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\Eloquent\Model;

class InstagramCommentService
{
    protected ApiClient $apiClient;
    protected ?string $accessToken = null;
    protected ?string $instagramBusinessAccountId = null;

    public function __construct()
    {
        $this->apiClient = app(ApiClient::class)
            ->withBaseUrl(config('instagram.api.graph_base_url', 'https://graph.instagram.com'))
            ->withVersion(config('instagram.api.version'));
    }

    public function withAccessToken(string $token): self
    {
        $this->accessToken = $token;
        $this->apiClient->withBaseUrl(GraphBaseUrlResolver::forToken($token));

        return $this;
    }

    public function withBusinessAccountId(string $instagramBusinessAccountId): self
    {
        $this->instagramBusinessAccountId = $instagramBusinessAccountId;
        return $this;
    }

    protected function validateToken(): void
    {
        if (!$this->accessToken) {
            throw new Exception('Access token must be set.');
        }
    }

    /**
     * Obtiene y persiste comentarios de un media en la base de datos.
     *
     * @param string $mediaId ID del media de Instagram
     * @param int $limit Límite de comentarios a obtener (max 100 por запрос)
     * @return array{comments: array, saved_count: int, updated_count: int}
     */
    public function syncComments(string $mediaId, int $limit = 100): array
    {
        $this->validateToken();

        $result = $this->getComments($mediaId, $limit);

        if (!$result || empty($result['data'])) {
            return ['comments' => [], 'saved_count' => 0, 'updated_count' => 0];
        }

        $savedCount = 0;
        $updatedCount = 0;

        foreach ($result['data'] as $commentData) {
            $saved = $this->saveComment($commentData, $mediaId);
            if ($saved === 'created') {
                $savedCount++;
            } elseif ($saved === 'updated') {
                $updatedCount++;
            }
        }

        Log::channel('instagram')->info('Comments synced', [
            'media_id' => $mediaId,
            'total_fetched' => count($result['data']),
            'saved' => $savedCount,
            'updated' => $updatedCount,
        ]);

        return [
            'comments' => $result['data'],
            'saved_count' => $savedCount,
            'updated_count' => $updatedCount,
        ];
    }

    /**
     * Guarda o actualiza un comentario en la base de datos.
     *
     * @param array $commentData Datos del comentario desde la API
     * @param string $mediaId ID del media asociado
     * @param string|null $parentCommentId ID del comentario padre (para replies)
     * @return string 'created', 'updated', or 'no_changes'
     */
    public function saveComment(array $commentData, string $mediaId, ?string $parentCommentId = null): string
    {
        $commentId = $commentData['id'] ?? null;

        if (!$commentId) {
            return 'no_changes';
        }

        $existing = InstagramModelResolver::instagram_comment()
            ->where('comment_id', $commentId)
            ->first();

        $timestamp = isset($commentData['timestamp'])
            ? date('Y-m-d H:i:s', is_numeric($commentData['timestamp'])
                ? $commentData['timestamp']
                : strtotime($commentData['timestamp']))
            : now();

        $data = [
            'comment_id' => $commentId,
            'instagram_media_id' => $mediaId,
            'instagram_business_account_id' => $this->instagramBusinessAccountId,
            'text' => $commentData['text'] ?? null,
            'username' => $commentData['username'] ?? null,
            'profile_picture_url' => $commentData['profile_picture_url'] ?? null,
            'created_time' => $timestamp,
            'message_type' => $parentCommentId ? 'reply' : 'comment',
            'parent_comment_id' => $parentCommentId ?? $commentData['parent_comment_id'] ?? null,
            'like_count' => $commentData['like_count'] ?? 0,
            'raw_data' => $commentData,
        ];

        if ($existing) {
            $changed = false;
            foreach (['text', 'like_count'] as $field) {
                if ($existing->$field !== ($commentData[$field] ?? null)) {
                    $changed = true;
                    break;
                }
            }

            if ($changed) {
                $existing->update($data);
                Log::channel('instagram')->debug('Comment updated', ['comment_id' => $commentId]);
                return 'updated';
            }
            return 'no_changes';
        }

        InstagramModelResolver::instagram_comment()->create($data);
        Log::channel('instagram')->debug('Comment saved', ['comment_id' => $commentId]);
        return 'created';
    }

    /**
     * Sincroniza comentarios incluyendo replies (respuestas anidadas).
     *
     * @param string $mediaId ID del media
     * @param int $limit Límite total de comentarios
     * @return array{comments: array, saved_count: int, updated_count: int}
     */
    public function syncCommentsWithReplies(string $mediaId, int $limit = 200): array
    {
        $result = $this->syncComments($mediaId, $limit);

        // Procesar replies si vienen en la respuesta
        foreach ($result['comments'] as $comment) {
            if (!empty($comment['replies'])) {
                foreach ($comment['replies'] as $reply) {
                    $this->saveComment($reply, $mediaId, $comment['id']);
                }
            }
        }

        return $result;
    }

    public function getComments(string $mediaId, int $limit = 100): ?array
    {
        $this->validateToken();
        try {
            $allComments = [];
            $after = null;

            do {
                $query = [
                    'fields' => 'id,text,timestamp,username,like_count,replies{id,text,username,like_count,timestamp}',
                    'access_token' => $this->accessToken,
                    'limit' => min($limit, 100),
                ];
                if ($after) $query['after'] = $after;

                $response = $this->apiClient->request('GET', "{$mediaId}/comments", [], null, $query);

                foreach ($response['data'] ?? [] as $comment) {
                    $allComments[] = $comment;
                }

                $after = $response['paging']['cursors']['after'] ?? null;
            } while ($after && count($allComments) < $limit);

            return ['data' => $allComments, 'paging' => $response['paging'] ?? null, 'total' => count($allComments)];
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error getting comments:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getComment(string $commentId): ?array
    {
        $this->validateToken();
        try {
            return $this->apiClient->request('GET', $commentId, [], null, [
                'fields' => 'id,text,timestamp,username,like_count',
                'access_token' => $this->accessToken,
            ]);
        } catch (Exception $e) {
            return null;
        }
    }

    public function replyToComment(string $commentId, string $message): ?array
    {
        $this->validateToken();
        try {
            $response = $this->apiClient->request('POST', "{$commentId}/replies", [], [
                'message' => $message,
            ], ['access_token' => $this->accessToken]);

            // Persistir la respuesta como comentario
            if (isset($response['id'])) {
                $parentComment = InstagramModelResolver::instagram_comment()
                    ->where('comment_id', $commentId)
                    ->first();

                $this->saveComment([
                    'id' => $response['id'],
                    'text' => $message,
                    'timestamp' => time(),
                    'username' => 'me',
                    'like_count' => 0,
                ], $parentComment?->instagram_media_id ?? '', $commentId);
            }

            return $response;
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error replying to comment:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function hideComment(string $commentId): bool
    {
        $this->validateToken();
        try {
            $response = $this->apiClient->request('POST', $commentId, [], ['hide' => true], ['access_token' => $this->accessToken]);

            if ($response) {
                InstagramModelResolver::instagram_comment()
                    ->where('comment_id', $commentId)
                    ->update(['hidden_at' => now()]);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function unhideComment(string $commentId): bool
    {
        $this->validateToken();
        try {
            $response = $this->apiClient->request('POST', $commentId, [], ['hide' => false], ['access_token' => $this->accessToken]);

            if ($response) {
                InstagramModelResolver::instagram_comment()
                    ->where('comment_id', $commentId)
                    ->update(['hidden_at' => null]);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function deleteComment(string $commentId): bool
    {
        $this->validateToken();
        try {
            $this->apiClient->request('DELETE', $commentId, [], null, ['access_token' => $this->accessToken]);

            InstagramModelResolver::instagram_comment()
                ->where('comment_id', $commentId)
                ->update(['deleted_at' => now()]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function disableComments(string $mediaId): bool
    {
        $this->validateToken();
        try {
            $this->apiClient->request('POST', $mediaId, [], ['comment_enabled' => false], ['access_token' => $this->accessToken]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function enableComments(string $mediaId): bool
    {
        $this->validateToken();
        try {
            $this->apiClient->request('POST', $mediaId, [], ['comment_enabled' => true], ['access_token' => $this->accessToken]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene el detalle de un comentario donde el usuario fue mencionado.
     *
     * El webhook de menciones solo envia los IDs (comment_id + media_id).
     * El detalle se obtiene con el endpoint normal de comentario, que
     * funciona tanto con Instagram Login (graph.instagram.com) como con
     * Facebook Login (graph.facebook.com).
     *
     * @param string $igUserId ID de usuario de Instagram Business (entry.id del webhook)
     * @param string $commentId ID del comentario donde fue mencionado
     * @param string|null $mediaId ID del media comentado (para el host correcto)
     * @return array|null Detalle del comentario mencionado
     */
    public function getMentionedComment(string $igUserId, string $commentId, ?string $mediaId = null): ?array
    {
        $this->validateToken();

        try {
            $response = $this->apiClient->request('GET', $commentId, [], null, [
                'fields' => 'id,text,timestamp,username,like_count,media_id',
                'access_token' => $this->accessToken,
            ]);

            return $response;
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error getting mentioned comment:', [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtiene el detalle de un media donde el usuario fue mencionado.
     *
     * Se usa el endpoint normal del media, que funciona con ambos flujos
     * de login (el campo mentioned_media solo existe con Facebook Login).
     *
     * @param string $igUserId ID de usuario de Instagram Business (entry.id del webhook)
     * @param string $mediaId ID del media donde fue mencionado
     * @return array|null Detalle del media mencionado
     */
    public function getMentionedMedia(string $igUserId, string $mediaId): ?array
    {
        $this->validateToken();

        try {
            $response = $this->apiClient->request('GET', $mediaId, [], null, [
                'fields' => 'id,caption,media_url,media_type,username,timestamp,permalink,like_count,comments_count',
                'access_token' => $this->accessToken,
            ]);

            return $response;
        } catch (Exception $e) {
            Log::channel('instagram')->error('Error getting mentioned media:', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function replyToMention(string $igUserId, string $commentId, string $message, ?string $mediaId = null): ?array
    {
        $this->validateToken();
        $data = [
            'comment_id' => $commentId,
            'message' => $message,
        ];
        if ($mediaId) {
            $data['media_id'] = $mediaId;
        }
        try {
            return $this->apiClient->request('POST', "{$igUserId}/mentions", [], $data, ['access_token' => $this->accessToken]);
        } catch (Exception $e) {
            return null;
        }
    }
}