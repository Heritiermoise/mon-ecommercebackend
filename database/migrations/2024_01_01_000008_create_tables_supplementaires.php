<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_statuts_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained()->cascadeOnDelete();
            $table->string('ancien_statut', 50)->nullable();
            $table->string('nouveau_statut', 50);
            $table->foreignId('modifie_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->text('commentaire')->nullable();
            $table->timestamp('cree_le')->useCurrent();
        });

        Schema::create('parametres_site', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 100)->unique();
            $table->text('valeur')->nullable();
            $table->enum('type', ['string', 'number', 'boolean', 'json'])->default('string');
            $table->string('categorie', 50)->default('general');
            $table->text('description')->nullable();
            $table->timestamp('modifie_le')->useCurrent();
        });

        Schema::create('methodes_paiement', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->decimal('frais_supplementaires', 10, 2)->default(0);
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->json('configuration')->nullable();
            $table->integer('position')->default(0);
        });

        Schema::create('profils_utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->string('avatar', 255)->nullable();
            $table->text('adresse')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('pays', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profils_utilisateurs');
        Schema::dropIfExists('methodes_paiement');
        Schema::dropIfExists('parametres_site');
        Schema::dropIfExists('historique_statuts_commandes');
    }
};