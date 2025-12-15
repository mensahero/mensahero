<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile')->index();
            $table->string('country_code')->default('PH');
            $table->string('source')
                ->index()
                ->comment('source of the contact, if imported from devices or other.')
                ->nullable();
            $table->userstamps();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
