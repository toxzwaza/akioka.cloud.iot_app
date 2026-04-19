<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            // 一覧取得の WHERE + ORDER BY 高速化
            ['order_requests', 'idx_order_requests_del_created', ['del_flg', 'created_at']],
            // 依頼者・工程フィルタ高速化
            ['order_requests', 'idx_order_requests_req_user_del', ['request_user_id', 'del_flg']],
            // initial_orders JOIN 高速化
            ['initial_orders', 'idx_initial_orders_req_id', ['order_request_id']],
            // reorder_point サブクエリ高速化
            ['stock_storages', 'idx_stock_storages_stock', ['stock_id']],
            // users 絞り込み
            ['users', 'idx_users_process_del', ['process_id', 'del_flg']],
        ];

        foreach ($indexes as [$table, $name, $columns]) {
            $this->createIndexIfMissing($table, $name, $columns);
        }
    }

    public function down(): void
    {
        $indexes = [
            ['order_requests', 'idx_order_requests_del_created'],
            ['order_requests', 'idx_order_requests_req_user_del'],
            ['initial_orders', 'idx_initial_orders_req_id'],
            ['stock_storages', 'idx_stock_storages_stock'],
            ['users', 'idx_users_process_del'],
        ];

        foreach ($indexes as [$table, $name]) {
            $this->dropIndexIfExists($table, $name);
        }
    }

    private function createIndexIfMissing(string $table, string $name, array $columns): void
    {
        $database = DB::getDatabaseName();
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $name)
            ->exists();

        if ($exists) {
            return;
        }

        $tableExists = DB::table('information_schema.tables')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->exists();

        if (!$tableExists) {
            return;
        }

        $cols = implode(', ', array_map(fn($c) => "`{$c}`", $columns));
        DB::statement("CREATE INDEX `{$name}` ON `{$table}` ({$cols})");
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        $database = DB::getDatabaseName();
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $name)
            ->exists();

        if (!$exists) {
            return;
        }

        DB::statement("DROP INDEX `{$name}` ON `{$table}`");
    }
};
