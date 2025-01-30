<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks');

            // 注文者
            $table->unsignedBigInteger('user_id')->nullable();
            // 注文数
            $table->integer('quantity')->nullable();

            // ステータスが1のもののみインポート
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_requests');
    }
};
