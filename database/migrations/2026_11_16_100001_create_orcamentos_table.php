<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->id();
            $table->uuid('saga_id')->unique();
            $table->unsignedBigInteger('os_id');
            $table->json('cliente');
            $table->json('itens');
            $table->unsignedBigInteger('valor_total');
            $table->string('status', 20);
            $table->string('token', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
