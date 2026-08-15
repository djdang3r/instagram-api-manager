<?php

namespace ScriptDevelop\InstagramApiManager\Support;

/**
 * Resuelve el host de la Graph API segun el tipo de token:
 * - Tokens de Instagram (IG...) -> graph.instagram.com (Instagram Login)
 * - Tokens de Facebook/Page (EAA...) -> graph.facebook.com (Facebook Login)
 *
 * Esto permite que una cuenta conectada por Facebook Login (token de pagina)
 * tambien pueda operar contenido, insights y comentarios de Instagram, que en
 * ese flujo van por graph.facebook.com (ver documentacion oficial de Meta).
 */
class GraphBaseUrlResolver
{
    public static function forToken(?string $accessToken, ?string $fallback = null): string
    {
        $fallback = $fallback ?? config('instagram.api.graph_base_url', 'https://graph.instagram.com');

        if (! $accessToken) {
            return $fallback;
        }

        // Los tokens de la Graph API de Facebook (user o page) empiezan con EAA,
        // los de Instagram Graph API con IG.
        if (str_starts_with($accessToken, 'EAA') || str_starts_with($accessToken, 'EAAG')) {
            return config('instagram.api.facebook_graph_base_url', 'https://graph.facebook.com');
        }

        if (str_starts_with($accessToken, 'IG')) {
            return config('instagram.api.graph_base_url', 'https://graph.instagram.com');
        }

        return $fallback;
    }
}
