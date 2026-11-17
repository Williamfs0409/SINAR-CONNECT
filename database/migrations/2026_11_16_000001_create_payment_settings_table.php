<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('bank_name', 100);
            $table->string('account_holder', 150);
            $table->string('account_number', 40);
            $table->timestamps();
        });

        DB::table('payment_settings')->insert([
            'id' => 1,
            'bank_name' => 'Bank Syariah Indonesia (BSI)',
            'account_holder' => 'Sri Hariati',
            'account_number' => '7276048637',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
