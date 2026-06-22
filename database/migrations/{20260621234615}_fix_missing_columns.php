<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================
        // TABLE AVIS - Ajouter colonnes manquantes
        // ============================================
        if (Schema::hasTable('avis')) {
            if (!Schema::hasColumn('avis', 'est_approuve')) {
                Schema::table('avis', function (Blueprint $table) {
                    $table->boolean('est_approuve')->default(false)->after('est_verifie');
                });
                DB::statement('UPDATE avis SET est_approuve = 1');
                echo "✓ Colonne est_approuve ajoutée à la table avis\n";
            }

            if (!Schema::hasColumn('avis', 'est_verifie')) {
                Schema::table('avis', function (Blueprint $table) {
                    $table->boolean('est_verifie')->default(false)->after('commande_id');
                });
                echo "✓ Colonne est_verifie ajoutée à la table avis\n";
            }

            if (!Schema::hasColumn('avis', 'nb_utile')) {
                Schema::table('avis', function (Blueprint $table) {
                    $table->integer('nb_utile')->default(0);
                });
                echo "✓ Colonne nb_utile ajoutée à la table avis\n";
            }

            if (!Schema::hasColumn('avis', 'nb_inutile')) {
                Schema::table('avis', function (Blueprint $table) {
                    $table->integer('nb_inutile')->default(0);
                });
                echo "✓ Colonne nb_inutile ajoutée à la table avis\n";
            }

            if (!Schema::hasColumn('avis', 'titre')) {
                Schema::table('avis', function (Blueprint $table) {
                    $table->string('titre', 255)->nullable()->after('note');
                });
                echo "✓ Colonne titre ajoutée à la table avis\n";
            }
        }

        // ============================================
        // TABLE PRODUITS - Ajouter colonnes manquantes
        // ============================================
        if (Schema::hasTable('produits')) {
            if (!Schema::hasColumn('produits', 'note_moyenne')) {
                Schema::table('produits', function (Blueprint $table) {
                    $table->decimal('note_moyenne', 3, 2)->default(0)->after('statut');
                });
                echo "✓ Colonne note_moyenne ajoutée à la table produits\n";
            }

            if (!Schema::hasColumn('produits', 'nombre_avis')) {
                Schema::table('produits', function (Blueprint $table) {
                    $table->integer('nombre_avis')->default(0)->after('note_moyenne');
                });
                echo "✓ Colonne nombre_avis ajoutée à la table produits\n";
            }
        }

        // ============================================
        // TABLE COMMANDES - Ajouter colonnes manquantes
        // ============================================
        if (Schema::hasTable('commandes')) {
            if (!Schema::hasColumn('commandes', 'statut_paiement')) {
                Schema::table('commandes', function (Blueprint $table) {
                    $table->string('statut_paiement', 50)->default('en_attente')->after('statut');
                });
                DB::statement("UPDATE commandes SET statut_paiement = 'en_attente'");
                echo "✓ Colonne statut_paiement ajoutée à la table commandes\n";
            }

            if (!Schema::hasColumn('commandes', 'frais_livraison')) {
                Schema::table('commandes', function (Blueprint $table) {
                    $table->decimal('frais_livraison', 10, 2)->default(0)->after('montant_total');
                });
                echo "✓ Colonne frais_livraison ajoutée à la table commandes\n";
            }

            if (!Schema::hasColumn('commandes', 'reduction')) {
                Schema::table('commandes', function (Blueprint $table) {
                    $table->decimal('reduction', 10, 2)->default(0)->after('frais_livraison');
                });
                echo "✓ Colonne reduction ajoutée à la table commandes\n";
            }

            if (!Schema::hasColumn('commandes', 'adresse_livraison_id')) {
                Schema::table('commandes', function (Blueprint $table) {
                    $table->foreignId('adresse_livraison_id')->nullable()->after('reduction')->constrained('adresses_livraison')->nullOnDelete();
                });
                echo "✓ Colonne adresse_livraison_id ajoutée à la table commandes\n";
            }

            if (!Schema::hasColumn('commandes', 'note_client')) {
                Schema::table('commandes', function (Blueprint $table) {
                    $table->text('note_client')->nullable()->after('adresse_livraison_id');
                });
                echo "✓ Colonne note_client ajoutée à la table commandes\n";
            }

            if (!Schema::hasColumn('commandes', 'note_admin')) {
                Schema::table('commandes', function (Blueprint $table) {
                    $table->text('note_admin')->nullable()->after('note_client');
                });
                echo "✓ Colonne note_admin ajoutée à la table commandes\n";
            }
        }

        // ============================================
        // TABLE PAIEMENTS - Ajouter colonnes manquantes
        // ============================================
        if (Schema::hasTable('paiements')) {
            if (!Schema::hasColumn('paiements', 'methode')) {
                Schema::table('paiements', function (Blueprint $table) {
                    $table->string('methode', 50)->default('maishapay')->after('commande_id');
                });
                echo "✓ Colonne methode ajoutée à la table paiements\n";
            }

            if (!Schema::hasColumn('paiements', 'montant')) {
                Schema::table('paiements', function (Blueprint $table) {
                    $table->decimal('montant', 10, 2)->after('methode');
                });
                echo "✓ Colonne montant ajoutée à la table paiements\n";
            }

            if (!Schema::hasColumn('paiements', 'statut')) {
                Schema::table('paiements', function (Blueprint $table) {
                    $table->string('statut', 50)->default('en_attente')->after('montant');
                });
                echo "✓ Colonne statut ajoutée à la table paiements\n";
            }

            if (!Schema::hasColumn('paiements', 'paye_le')) {
                Schema::table('paiements', function (Blueprint $table) {
                    $table->timestamp('paye_le')->nullable()->after('statut');
                });
                echo "✓ Colonne paye_le ajoutée à la table paiements\n";
            }

            if (!Schema::hasColumn('paiements', 'reference_transaction')) {
                Schema::table('paiements', function (Blueprint $table) {
                    $table->string('reference_transaction', 255)->nullable()->after('paye_le');
                });
                echo "✓ Colonne reference_transaction ajoutée à la table paiements\n";
            }
        }

        // ============================================
        // TABLE UTILISATEURS - Ajouter colonnes manquantes
        // ============================================
        if (Schema::hasTable('utilisateurs')) {
            if (!Schema::hasColumn('utilisateurs', 'telephone')) {
                Schema::table('utilisateurs', function (Blueprint $table) {
                    $table->string('telephone', 20)->nullable()->after('email');
                });
                echo "✓ Colonne telephone ajoutée à la table utilisateurs\n";
            }

            if (!Schema::hasColumn('utilisateurs', 'two_factor_enabled')) {
                Schema::table('utilisateurs', function (Blueprint $table) {
                    $table->boolean('two_factor_enabled')->default(false);
                });
                echo "✓ Colonne two_factor_enabled ajoutée à la table utilisateurs\n";
            }

            if (!Schema::hasColumn('utilisateurs', 'two_factor_secret')) {
                Schema::table('utilisateurs', function (Blueprint $table) {
                    $table->text('two_factor_secret')->nullable();
                });
                echo "✓ Colonne two_factor_secret ajoutée à la table utilisateurs\n";
            }

            if (!Schema::hasColumn('utilisateurs', 'two_factor_last_verified')) {
                Schema::table('utilisateurs', function (Blueprint $table) {
                    $table->timestamp('two_factor_last_verified')->nullable();
                });
                echo "✓ Colonne two_factor_last_verified ajoutée à la table utilisateurs\n";
            }

            if (!Schema::hasColumn('utilisateurs', 'current_session_token')) {
                Schema::table('utilisateurs', function (Blueprint $table) {
                    $table->string('current_session_token', 255)->nullable();
                });
                echo "✓ Colonne current_session_token ajoutée à la table utilisateurs\n";
            }

            if (!Schema::hasColumn('utilisateurs', 'last_login_at')) {
                Schema::table('utilisateurs', function (Blueprint $table) {
                    $table->timestamp('last_login_at')->nullable();
                });
                echo "✓ Colonne last_login_at ajoutée à la table utilisateurs\n";
            }

            if (!Schema::hasColumn('utilisateurs', 'last_login_ip')) {
                Schema::table('utilisateurs', function (Blueprint $table) {
                    $table->string('last_login_ip', 45)->nullable();
                });
                echo "✓ Colonne last_login_ip ajoutée à la table utilisateurs\n";
            }
        }

        // ============================================
        // TABLE AVIS_REPONSES - Vérifier existence
        // ============================================
        if (!Schema::hasTable('avis_reponses')) {
            Schema::create('avis_reponses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('avis_id')->constrained('avis')->cascadeOnDelete();
                $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
                $table->text('contenu');
                $table->boolean('est_admin')->default(false);
                $table->timestamps();
            });
            echo "✓ Table avis_reponses créée\n";
        }

        // ============================================
        // TABLE AVIS_SIGNALEMENTS - Vérifier existence
        // ============================================
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
            echo "✓ Table avis_signalements créée\n";
        }

        // ============================================
        // TABLE AVIS_PHOTOS - Vérifier existence
        // ============================================
        if (!Schema::hasTable('avis_photos')) {
            Schema::create('avis_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('avis_id')->constrained('avis')->cascadeOnDelete();
                $table->text('url_image');
                $table->text('chemin_fichier');
                $table->integer('ordre')->default(0);
                $table->timestamps();
            });
            echo "✓ Table avis_photos créée\n";
        }

        // ============================================
        // TABLE MOUVEMENTS_STOCK - Vérifier existence
        // ============================================
        if (!Schema::hasTable('mouvements_stock')) {
            Schema::create('mouvements_stock', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
                $table->foreignId('utilisateur_id')->nullable()->constrained('utilisateurs')->nullOnDelete();
                $table->enum('type', ['entree', 'sortie', 'ajustement', 'vente', 'retour']);
                $table->integer('quantite');
                $table->integer('stock_avant');
                $table->integer('stock_apres');
                $table->string('reference', 100)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                
                $table->index(['produit_id', 'created_at']);
                $table->index(['type', 'created_at']);
            });
            echo "✓ Table mouvements_stock créée\n";
        }

        // ============================================
        // TABLE SECURITY_BLOCKED_IPS - Vérifier existence
        // ============================================
        if (!Schema::hasTable('security_blocked_ips')) {
            Schema::create('security_blocked_ips', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 100)->unique();
                $table->text('reason')->nullable();
                $table->timestamp('blocked_at');
                $table->timestamp('blocked_until')->nullable();
                $table->integer('attempts')->default(0);
                $table->timestamps();
            });
            echo "✓ Table security_blocked_ips créée\n";
        }

        // ============================================
        // TABLE SECURITY_LOGS - Vérifier existence
        // ============================================
        if (!Schema::hasTable('security_logs')) {
            Schema::create('security_logs', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 100);
                $table->string('event_type', 100);
                $table->text('details')->nullable();
                $table->timestamps();
                
                $table->index(['ip_address', 'created_at']);
                $table->index(['event_type', 'created_at']);
            });
            echo "✓ Table security_logs créée\n";
        }

        // ============================================
        // TABLE PARAMETRES_SITE - Vérifier existence
        // ============================================
        if (!Schema::hasTable('parametres_site')) {
            Schema::create('parametres_site', function (Blueprint $table) {
                $table->id();
                $table->string('cle', 100)->unique();
                $table->text('valeur')->nullable();
                $table->timestamps();
            });
            echo "✓ Table parametres_site créée\n";
        }

        // ============================================
        // TABLE TAUX_CHANGE - Vérifier existence
        // ============================================
        if (!Schema::hasTable('taux_change')) {
            Schema::create('taux_change', function (Blueprint $table) {
                $table->id();
                $table->string('devise_source', 10)->default('USD');
                $table->string('devise_cible', 10)->default('CDF');
                $table->decimal('taux', 10, 4);
                $table->boolean('est_actif')->default(true);
                $table->timestamp('date_application');
                $table->text('note')->nullable();
                $table->foreignId('modifie_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
                $table->timestamps();
            });
            echo "✓ Table taux_change créée\n";
        }
    }

    public function down(): void
    {
        // Rollback non nécessaire
    }
};