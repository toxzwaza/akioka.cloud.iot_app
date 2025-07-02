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
        Schema::create('device_messages', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('priority')->nullable(false)->default(0); //0:info 1:warning 2:danger
            $table->foreignId('to_device_id')->constrained('devices');
            $table->foreignId('from_device_id')->constrained('devices');
            $table->foreignId('to_user_id')->constrained('users');
            $table->foreignId('from_user_id')->constrained('users');
            $table->text('message')->nullable(false);
            $table->tinyInteger('read_flg')->nullable(false)->default(0); //0:unread 1:read
            $table->tinyInteger('del_flg')->nullable(false)->default(0); //0:not deleted 1:deleted
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
        Schema::dropIfExists('device_messages');
    }
};
