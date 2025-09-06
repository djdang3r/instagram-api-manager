<?php

namespace ScriptDevelop\InstagramApiManager\InstagramApi;

use Illuminate\Support\Facades\Log;

class Endpoints
{
    // -----------------------------
    // Páginas de Facebook y cuentas
    // -----------------------------

    // Obtener las páginas (Facebook Pages) que administra el usuario y sus tokens de acceso, con info de Instagram Business Account asociada
    const GET_USER_MANAGED_PAGES = 'me/accounts?fields=name,access_token,tasks,instagram_business_account';

    // Obtener detalle y token de una página específica
    const GET_SPECIFIC_PAGE = '{page_id}?fields=name,access_token,instagram_business_account';


    // -----------------------------
    // Perfil y medios de Instagram
    // -----------------------------

    // Obtener información del perfil de usuario (GET)
    const GET_USER_PROFILE_INFO = '{ig_scoped_id}?fields=id,username,account_type,media_count,followers_count,follows_count,name,profile_picture_url';

    // Obtener medias del usuario (GET)
    const GET_USER_MEDIA = '{user_id}/media?fields=id,caption,media_type,media_url,thumbnail_url,timestamp,username,permalink,children{media_url,media_type}';

    // Obtener detalles de un media específico (GET)
    const GET_MEDIA_DETAILS = '{media_id}?fields=id,media_type,media_url,thumbnail_url,timestamp,username,caption,permalink,children{media_url,media_type}';


    // -----------------------------
    // Mensajería Instagram (Messenger API)
    // -----------------------------

    // Enviar mensaje de texto (POST)
    const SEND_TEXT_MESSAGE = '{ig_user_id}/messages';

    // Subir un Reel a un contenedor IG con video_url, caption y opción share_to_feed (POST)
    const UPLOAD_REEL_CONTAINER = '{ig_user_id}/media?media_type=REELS&video_url={video_url}&caption={caption}&share_to_feed={share_to_feed}';

    // Obtener estado de un contenedor IG (GET)
    const GET_IG_CONTAINER_STATUS = '{ig_container_id}?fields=status_code,status';

    // Publicar Reel desde contenedor IG (POST)
    const PUBLISH_REEL = '{ig_user_id}/media_publish?creation_id={ig_container_id}';

    // Subida de attachments para mensajes (foto) (POST)
    const UPLOAD_MESSAGE_ATTACHMENT = '{ig_user_id}/message_attachments';

    // Obtener lista de conversaciones para usuario IG (GET)
    const GET_CONVERSATIONS = '{ig_user_id}/conversations?platform=instagram';

    // Buscar conversación con usuario específico (GET)
    const FIND_CONVERSATION_WITH_USER = '{ig_user_id}/conversations?user_id={igsid}';

    // Obtener lista de mensajes en una conversación (GET)
    const GET_MESSAGES_IN_CONVERSATION = '{conversation_id}?fields=messages{from,to}';

    // Información detallada sobre un mensaje (GET)
    const GET_MESSAGE_INFO = '{message_id}?fields=id,created_time,from,to,message';


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
