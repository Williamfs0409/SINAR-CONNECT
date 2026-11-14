<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spp_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('period_month');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->date('due_date')->index();
            $table->timestamps();
            $table->unique(['student_id', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spp_invoices');
    }
};
