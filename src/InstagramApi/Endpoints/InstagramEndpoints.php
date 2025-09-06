<?php

namespace ScriptDevelop\InstagramApiManager\InstagramApi\Endpoints;

use Illuminate\Support\Facades\Log;

class InstagramEndpoints
{
    // -----------------------------
    // Perfil y medios de Instagram
    // -----------------------------

    public const GET_USER_PROFILE_INFO = '{ig_scoped_id}?fields=id,username,account_type,media_count,followers_count,follows_count,name,profile_picture_url,biography';

    public const GET_USER_MEDIA = '{user_id}/media?fields=id,caption,media_type,media_url,thumbnail_url,timestamp,username,permalink,children{media_url,media_type}';

    public const GET_MEDIA_DETAILS = '{media_id}?fields=id,media_type,media_url,thumbnail_url,timestamp,username,caption,permalink,children{media_url,media_type}';

    // -----------------------------
    // Mensajería Instagram (Messenger API)
    // -----------------------------

    public const SEND_TEXT_MESSAGE = '{ig_user_id}/messages';

    public const UPLOAD_REEL_CONTAINER = '{ig_user_id}/media?media_type=REELS&video_url={video_url}&caption={caption}&share_to_feed={share_to_feed}';

    public const GET_IG_CONTAINER_STATUS = '{ig_container_id}?fields=status_code,status';

    public const PUBLISH_REEL = '{ig_user_id}/media_publish?creation_id={ig_container_id}';

    public const UPLOAD_MESSAGE_ATTACHMENT = '{ig_user_id}/message_attachments';

    public const GET_CONVERSATIONS = '{ig_user_id}/conversations?platform=instagram';

    public const FIND_CONVERSATION_WITH_USER = '{ig_user_id}/conversations?user_id={igsid}';

    public const GET_MESSAGES_IN_CONVERSATION = '{conversation_id}?fields=messages{from,to}';

    public const GET_MESSAGE_INFO = '{message_id}?fields=id,created_time,from,to,message';

    // Nuevos endpoints para OAuth token management
    public const OAUTH_ACCESS_TOKEN = 'oauth/access_token';

    public const REFRESH_ACCESS_TOKEN = 'refresh_access_token';

    /**
     * Construye la URL final con reemplazo de parámetros.
     *
     * @param string $endpoint Endpoint con placeholders.
     * @param array $params Valores para placeholders.
     * @return string URL construida.
     */
    public static function build(string $endpoint, array $params = []): string
    {
        $placeholders = array_map(fn($key) => '{' . $key . '}', array_keys($params));
        $url = str_replace($placeholders, array_values($params), $endpoint);

        Log::channel('instagram')->info('URL generada:', ['url' => $url]);

        return $url;
    }
}
