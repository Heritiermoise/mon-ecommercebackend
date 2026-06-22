<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('avis')) {
            Schema::create('avis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
                $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
                $table->foreignId('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
                $table->tinyInteger('note')->unsigned();
                $table->string('titre', 255)->nullable();
                $table->text('commentaire');
                $table->boolean('est_verifie')->default(false);
                $table->boolean('est_approuve')->default(false);
                $table->integer('nb_utile')->default(0);
                $table->integer('nb_inutile')->default(0);
                $table->timestamp('date_publication')->nullable();
                $table->timestamps();
                
                $table->index(['produit_id', 'note']);
                $table->index(['utilisateur_id']);
                $table->unique(['produit_id', 'utilisateur_id']);
            });
        }

        if (!Schema::hasTable('avis_photos')) {
            Schema::create('avis_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('avis_id')->constrained('avis')->cascadeOnDelete();
                $table->string('url_image');
                $table->string('chemin_fichier');
                $table->integer('ordre')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('avis_reponses')) {
            Schema::create('avis_reponses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('avis_id')->constrained('avis')->cascadeOnDelete();
                $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
                $table->text('contenu');
                $table->boolean('est_admin')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('avis_signalements')) {
            Schema::create('avis_signalements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('avis_id')->constrained('avis')->cascadeOnDelete();
                $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
                $table->string('motif');
                $table->text('details')->nullable();
                $table->boolean('est_traite')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_signalements');
        Schema::dropIfExists('avis_reponses');
        Schema::dropIfExists('avis_photos');
        Schema::dropIfExists('avis');
    }
};