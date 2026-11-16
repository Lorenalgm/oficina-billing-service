<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->uuid('saga_id')->unique();
            $table->unsignedBigInteger('os_id');
            $table->foreignId('orcamento_id')->constrained('orcamentos');
            $table->unsignedBigInteger('valor');
            $table->string('status', 20);
            $table->string('gateway_id')->nullable()->unique();
            $table->string('link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
