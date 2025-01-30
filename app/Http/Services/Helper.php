<?php

namespace App\Http\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Helper
{
    // Teamsより通知
    public static function sendNotify($mentionIds, $message, $url = null)
    {
        $status = false;

        // Webhook URL（.envファイルから取得）
        $webhookUrl = env('TEAMS_WEBHOOK_URL');


        if (!$webhookUrl) {
            return $status;
        }

        // メンション部分を生成
        $mentions = array_map(function ($id) {
            return [
                "type" => "mention",
                "text" => "<at>{$id}</at>",
                "mentioned" => [
                    "id" => $id,
                    "name" => $id,
                ],
            ];
        }, $mentionIds);

        // メンション用テキストを生成
        $mentionText = implode(' ', array_map(fn($id) => "@<at>{$id}</at>", $mentionIds));

        // Adaptive Cardのペイロード
        $payload = [
            "type" => "message",
            "attachments" => [
                [
                    "contentType" => "application/vnd.microsoft.card.adaptive",
                    "content" => [
                        "type" => "AdaptiveCard",
                        "body" => [
                            [
                                "type" => "TextBlock",
                                "text" => $mentionText,
                                "color" => "attention",
                                "size" => "large",
                            ],
                            [
                                "type" => "TextBlock",
                                "text" => "在庫管理システムからの通知です。",
                                "color" => "default",
                                "size" => "default",
                            ],
                            [
                                "type" => "TextBlock",
                                "text" => $message,
                                "color" => "good",
                                "size" => "medium",
                                "wrap" => True  // テキストの折り返しを有効化
                            ],
                        ],
                        '"$schema"' => "http://adaptivecards.io/schemas/adaptive-card.json",
                        "version" => "1.0",
                        "msteams" => [
                            "entities" => $mentions,
                        ],
                    ],
                ],
            ],
        ];

        if ($url) {
            $payload['attachments'][0]['content']['body'][] = [
                "type" => "TextBlock",
                "text" => "[在庫管理システム]({$url})",
                "color" => "accent",
                "size" => "medium",
                "wrap" => True
            ];
        }
        try {
            // リクエストを送信
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($webhookUrl, $payload);
            // 結果を返却
            if ($response->successful()) {
                $status = true;
            }
        } catch (Exception $e) {
            $status = false;
        }

        return $status;
    }
}
