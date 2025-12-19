<?php

use App\Concerns\DeviceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_name');
            $table->string('manufacturer');
            $table->string('modelName');
            $table->string('osName');
            $table->string('status')->default(DeviceStatus::Online->value);
            $table->timestamp('last_seen')->nullable();
            $table->json('extras');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
