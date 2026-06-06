<?php

namespace ScriptDevelop\InstagramApiManager\Services;

use ScriptDevelop\InstagramApiManager\Support\InstagramModelResolver;
use Illuminate\Support\Facades\Log;
use Exception;

class MessengerLinkService
{
    public function generateMmeLink(string $pageId, ?string $ref = null): string
    {
        $page = InstagramModelResolver::facebook_page()->where('page_id', $pageId)->first();
        if (!$page) {
            throw new Exception("Facebook Page not found: {$pageId}");
        }

        $username = $page->name;

        // Limpiar el username de caracteres no permitidos en URLs
        $cleanUsername = preg_replace('/[^a-zA-Z0-9._]/', '', $username);
        $url = config('facebook.links.base_url', 'https://m.me') . '/' . $cleanUsername;

        if ($ref) {
            $cleanRef = preg_replace('/[^a-zA-Z0-9_=\-]/', '', $ref);
            if (strlen($cleanRef) > 2083) {
                $cleanRef = substr($cleanRef, 0, 2083);
            }
            $url .= '?ref=' . urlencode($cleanRef);
        }

        return $url;
    }

    /**
     * @deprecated Google Charts API is deprecated since 2012. Returns null. Implement a custom QR provider.
     */
    public function generateMmeQrCode(string $pageId, ?string $ref = null, int $size = 300): ?string
    {
        $url = $this->generateMmeLink($pageId, $ref);
        Log::warning('generateMmeQrCode: Google Charts API is deprecated, returning null', ['url' => $url]);
        return null;
    }
}
