<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adresses_livraison', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->string('nom_complet', 120);
            $table->string('telephone', 30);
            $table->text('adresse');
            $table->string('ville', 100);
            $table->string('code_postal', 20)->nullable();
            $table->string('pays', 100)->default('France');
            $table->boolean('est_principale')->default(false);
            $table->timestamps();
        });

        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs');
            $table->string('numero_commande', 50)->unique();
            $table->decimal('montant_total', 10, 2);
            $table->decimal('frais_livraison', 10, 2)->default(0);
            $table->decimal('reduction', 10, 2)->default(0);
            $table->enum('statut', ['en_attente', 'payee', 'confirmee', 'en_cours_de_traitement', 'expediee', 'livree', 'annulee'])->default('en_attente');
            $table->enum('statut_paiement', ['non_paye', 'paye', 'echoue', 'rembourse'])->default('non_paye');
            $table->foreignId('adresse_livraison_id')->nullable()->constrained('adresses_livraison')->nullOnDelete();
            $table->text('note_client')->nullable();
            $table->text('note_admin')->nullable();
            $table->timestamp('date_livraison_prevue')->nullable();
            $table->timestamp('date_livraison_effective')->nullable();
            $table->timestamps();
        });

        Schema::create('articles_commande', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained()->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits');
            $table->string('produit_nom', 255);
            $table->integer('quantite');
            $table->decimal('prix', 10, 2);
            $table->decimal('prix_total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles_commande');
        Schema::dropIfExists('commandes');
        Schema::dropIfExists('adresses_livraison');
    }
};