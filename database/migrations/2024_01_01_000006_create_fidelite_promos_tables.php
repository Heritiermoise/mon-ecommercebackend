<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_fidelite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->integer('points')->default(0);
            $table->enum('type', ['gain', 'utilisation']);
            $table->string('description', 255)->nullable();
            $table->integer('points_montant');
            $table->foreignId('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('recompenses_fidelite', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->text('description')->nullable();
            $table->integer('points_necessaires');
            $table->enum('type_reduction', ['pourcentage', 'montant_fixe', 'livraison_gratuite']);
            $table->decimal('valeur_reduction', 10, 2);
            $table->integer('stock_disponible')->default(0);
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->timestamps();
        });

        Schema::create('codes_promo', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->enum('type_reduction', ['pourcentage', 'montant_fixe', 'livraison_gratuite']);
            $table->decimal('valeur_reduction', 10, 2);
            $table->decimal('montant_minimum', 10, 2)->default(0);
            $table->decimal('montant_maximum', 10, 2)->nullable();
            $table->integer('utilisation_max')->default(0);
            $table->integer('utilisation_par_user')->default(1);
            $table->integer('nombre_utilisations')->default(0);
            $table->enum('statut', ['actif', 'inactif', 'expire'])->default('actif');
            $table->dateTime('date_debut')->nullable();
            $table->dateTime('date_fin')->nullable();
            $table->foreignId('categorie_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('utilisations_code_promo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('code_promo_id')->constrained('codes_promo')->cascadeOnDelete();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->decimal('montant_reduction', 10, 2);
            $table->timestamp('utilise_le')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisations_code_promo');
        Schema::dropIfExists('codes_promo');
        Schema::dropIfExists('recompenses_fidelite');
        Schema::dropIfExists('points_fidelite');
    }
};