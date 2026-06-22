<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listes_souhaits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
            $table->timestamp('ajoute_le')->useCurrent();
            $table->unique(['utilisateur_id', 'produit_id']);
        });

        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
            $table->tinyInteger('note');
            $table->text('commentaire')->nullable();
            $table->boolean('est_verifie')->default(false);
            $table->enum('statut', ['en_attente', 'approuve', 'rejete'])->default('en_attente');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->string('titre', 255);
            $table->text('message');
            $table->enum('type', ['commande', 'paiement', 'promo', 'systeme', 'fidelite']);
            $table->boolean('est_lu')->default(false);
            $table->string('lien', 255)->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('avis');
        Schema::dropIfExists('listes_souhaits');
    }
};