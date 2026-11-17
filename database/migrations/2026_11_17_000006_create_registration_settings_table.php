<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registration_settings')) {
            Schema::create('registration_settings', function (Blueprint $table): void {
                $table->unsignedTinyInteger('id')->primary();
                $table->boolean('is_open')->default(true);
                $table->timestamps();
            });
        }

        if (! DB::table('registration_settings')->where('id', 1)->exists()) {
            DB::table('registration_settings')->insert([
                'id' => 1,
                'is_open' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_settings');
    }
};
