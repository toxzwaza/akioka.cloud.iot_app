<?php

namespace App\Http\Services;

use App\Models\InventoryOperation;
use App\Models\InventoryOperationRecord;
use App\Models\NotifyQueue;
use App\Models\NotifyQueueUser;
use App\Models\StockStorage;
use Exception;
use Illuminate\Support\Facades\DB;
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

    public static function createNotifyQueue($title, $msg, $url, $users)
    {
        $status = true;

        try {
            DB::beginTransaction();
            try {
                $notifyQueue = new NotifyQueue();
                $notifyQueue->title = $title;
                $notifyQueue->msg = $msg;
                $notifyQueue->url = $url;
                $notifyQueue->save();

                foreach ($users as $user) {
                    $notifyQueueUser = new NotifyQueueUser();
                    $notifyQueueUser->notify_queue_id = $notifyQueue->id;
                    $notifyQueueUser->user_id = $user;
                    $notifyQueueUser->save();
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            $status = false;
        }

        return $status;
    }

    public static function updateReOrderPoint($stock_storage_id, $stock_id, $quantity)
    {
        // 発注依頼を記録
        $inventoryOperationRecord = new InventoryOperationRecord();
        $inventoryOperationRecord->inventory_operation_id = 7;
        $inventoryOperationRecord->stock_id = $stock_id;
        $inventoryOperationRecord->stock_storage_id = $stock_storage_id;
        $inventoryOperationRecord->bef_quantity = $quantity;
        $inventoryOperationRecord->save();

        // 発注点再計算
        $reorder_point_avg = InventoryOperationRecord::where('stock_storage_id', $stock_storage_id)
            ->where('inventory_operation_id', 7)
            ->avg('bef_quantity');

        // 発注点を更新
        $stock_storage = StockStorage::find($stock_storage_id);
        $stock_storage->reorder_point = $reorder_point_avg;
        $stock_storage->save();
    }
}
