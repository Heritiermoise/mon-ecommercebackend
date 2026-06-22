<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
                $table->tinyInteger('note');
                $table->string('titre', 255)->nullable();
                $table->text('commentaire');
                $table->boolean('est_verifie')->default(false);
                $table->boolean('est_approuve')->default(true);
                $table->integer('nb_utile')->default(0);
                $table->integer('nb_inutile')->default(0);
                $table->timestamp('date_publication')->nullable();
                $table->timestamps();
                
                $table->index(['produit_id', 'est_approuve']);
                $table->index(['utilisateur_id']);
            });
            DB::statement('SELECT 1');
            echo "Table avis creee" . PHP_EOL;
            return;
        }
        
        // Ajouter les colonnes manquantes une par une
        if (!Schema::hasColumn('avis', 'commande_id')) {
            Schema::table('avis', function (Blueprint $table) {
                $table->foreignId('commande_id')->nullable()->after('utilisateur_id')->constrained('commandes')->nullOnDelete();
            });
            echo "Colonne commande_id ajoutee" . PHP_EOL;
        }
        
        if (!Schema::hasColumn('avis', 'titre')) {
            Schema::table('avis', function (Blueprint $table) {
                $table->string('titre', 255)->nullable()->after('note');
            });
            echo "Colonne titre ajoutee" . PHP_EOL;
        }
        
        if (!Schema::hasColumn('avis', 'est_verifie')) {
            Schema::table('avis', function (Blueprint $table) {
                $table->boolean('est_verifie')->default(false)->after('commentaire');
            });
            echo "Colonne est_verifie ajoutee" . PHP_EOL;
        }
        
        if (!Schema::hasColumn('avis', 'est_approuve')) {
            Schema::table('avis', function (Blueprint $table) {
                $table->boolean('est_approuve')->default(true)->after('est_verifie');
            });
            echo "Colonne est_approuve ajoutee" . PHP_EOL;
        }
        
        if (!Schema::hasColumn('avis', 'nb_utile')) {
            Schema::table('avis', function (Blueprint $table) {
                $table->integer('nb_utile')->default(0)->after('est_approuve');
            });
            echo "Colonne nb_utile ajoutee" . PHP_EOL;
        }
        
        if (!Schema::hasColumn('avis', 'nb_inutile')) {
            Schema::table('avis', function (Blueprint $table) {
                $table->integer('nb_inutile')->default(0)->after('nb_utile');
            });
            echo "Colonne nb_inutile ajoutee" . PHP_EOL;
        }
        
        if (!Schema::hasColumn('avis', 'date_publication')) {
            Schema::table('avis', function (Blueprint $table) {
                $table->timestamp('date_publication')->nullable()->after('nb_inutile');
            });
            echo "Colonne date_publication ajoutee" . PHP_EOL;
        }
    }

    public function down(): void
    {
        // Pas de rollback
    }
};