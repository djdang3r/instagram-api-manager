<?php

namespace ScriptDevelop\InstagramApiManager\FacebookApi\Endpoints;

use Illuminate\Support\Facades\Log;

class FacebookEndpoints
{
    // -----------------------------
    // Páginas de Facebook
    // -----------------------------

    // Obtener las páginas (Facebook Pages) que administra el usuario y sus tokens de acceso
    public const GET_USER_MANAGED_PAGES = 'me/accounts?fields=name,access_token,tasks,instagram_business_account';

    // Obtener detalle y token de una página específica
    public const GET_SPECIFIC_PAGE = '{page_id}?fields=name,access_token,instagram_business_account';

    // -----------------------------
    // Mensajería Messenger
    // -----------------------------

    // Enviar mensaje simple (POST)
    public const SEND_MESSAGE = '{page_id}/messages';

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

        Log::channel('instagram')->info('URL generada Facebook:', ['url' => $url]);

        return $url;
    }
}
