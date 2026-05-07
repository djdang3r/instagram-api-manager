<?php

namespace ScriptDevelop\InstagramApiManager\Contracts;

use Illuminate\Http\Request;

interface WebhookProcessorInterface
{
    public function handle(Request $request): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse;

    public function verifyWebhook(Request $request): \Illuminate\Http\Response;

    public function processWebhookPayload(Request $request): \Illuminate\Http\JsonResponse;
}
