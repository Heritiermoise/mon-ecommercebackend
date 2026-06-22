<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 120);
            $table->string('email', 150)->unique();
            $table->string('telephone', 30)->unique()->nullable();
            $table->string('mot_de_passe_hash', 255);
            $table->enum('role', ['client', 'administrateur', 'super_administrateur'])->default('client');
            $table->enum('statut', ['actif', 'banni'])->default('actif');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
    }
};