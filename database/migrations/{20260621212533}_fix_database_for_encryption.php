<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function columnExists($table, $column)
    {
        return Schema::hasColumn($table, $column);
    }

    private function safeModify($table, $column, $type)
    {
        if ($this->columnExists($table, $column)) {
            try {
                DB::statement("ALTER TABLE `$table` MODIFY `$column` $type");
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    private function safeAddColumn($table, $column, $definition)
    {
        if (!$this->columnExists($table, $column)) {
            try {
                DB::statement("ALTER TABLE `$table` ADD `$column` $definition");
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    public function up(): void
    {
        // 1. UTILISATEURS
        if (Schema::hasTable('utilisateurs')) {
            $this->safeModify('utilisateurs', 'nom', 'TEXT');
            $this->safeModify('utilisateurs', 'telephone', 'TEXT NULL');
            $this->safeModify('utilisateurs', 'two_factor_secret', 'TEXT NULL');
            $this->safeModify('utilisateurs', 'current_session_token', 'TEXT NULL');
            $this->safeModify('utilisateurs', 'last_login_ip', 'VARCHAR(100) NULL');
        }

        // 2. ADRESSES LIVRAISON
        if (Schema::hasTable('adresses_livraison')) {
            $this->safeModify('adresses_livraison', 'nom_complet', 'TEXT');
            $this->safeModify('adresses_livraison', 'telephone', 'TEXT');
            $this->safeModify('adresses_livraison', 'adresse', 'TEXT');
            $this->safeModify('adresses_livraison', 'ville', 'TEXT');
            $this->safeModify('adresses_livraison', 'code_postal', 'TEXT NULL');
            $this->safeModify('adresses_livraison', 'instructions', 'TEXT NULL');
        }

        // 3. PAIEMENTS - Ajouter la colonne manquante
        if (Schema::hasTable('paiements')) {
            $this->safeAddColumn('paiements', 'reference_transaction', 'TEXT NULL');
            $this->safeModify('paiements', 'reference_transaction', 'TEXT NULL');
        }

        // 4. COMMANDES
        if (Schema::hasTable('commandes')) {
            $this->safeModify('commandes', 'note_client', 'TEXT NULL');
            $this->safeModify('commandes', 'note_admin', 'TEXT NULL');
        }

        // 5. PRODUITS
        if (Schema::hasTable('produits')) {
            $this->safeModify('produits', 'description', 'TEXT NULL');
        }

        // 6. AVIS
        if (Schema::hasTable('avis')) {
            $this->safeModify('avis', 'commentaire', 'TEXT');
            $this->safeModify('avis', 'titre', 'TEXT NULL');
        }

        // 7. AVIS RÉPONSES
        if (Schema::hasTable('avis_reponses')) {
            $this->safeModify('avis_reponses', 'contenu', 'TEXT');
        }

        // 8. AVIS SIGNALEMENTS
        if (Schema::hasTable('avis_signalements')) {
            $this->safeModify('avis_signalements', 'motif', 'TEXT');
            $this->safeModify('avis_signalements', 'details', 'TEXT NULL');
        }

        // 9. WISHLISTS
        if (Schema::hasTable('wishlists')) {
            $this->safeModify('wishlists', 'note_personnelle', 'TEXT NULL');
        }

        // 10. WISHLIST PARTAGÉES
        if (Schema::hasTable('wishlist_partagees')) {
            $this->safeModify('wishlist_partagees', 'nom', 'TEXT');
        }

        // 11. NOTIFICATIONS
        if (Schema::hasTable('notifications')) {
            $this->safeModify('notifications', 'titre', 'TEXT');
            $this->safeModify('notifications', 'message', 'TEXT');
            $this->safeModify('notifications', 'lien', 'TEXT NULL');
        }

        // 12. TAGS
        if (Schema::hasTable('tags')) {
            $this->safeModify('tags', 'nom', 'TEXT');
            $this->safeModify('tags', 'slug', 'TEXT');
        }

        // 13. COULEURS
        if (Schema::hasTable('couleurs')) {
            $this->safeModify('couleurs', 'nom', 'TEXT');
        }

        // 14. RECHERCHES RÉCENTES
        if (Schema::hasTable('recherches_recentes')) {
            $this->safeModify('recherches_recentes', 'terme', 'TEXT');
            $this->safeModify('recherches_recentes', 'ip_address', 'VARCHAR(100) NULL');
        }

        // 15. SECURITY BLOCKED IPS
        if (Schema::hasTable('security_blocked_ips')) {
            $this->safeModify('security_blocked_ips', 'ip_address', 'VARCHAR(100)');
            $this->safeModify('security_blocked_ips', 'reason', 'TEXT');
        }

        // 16. SECURITY LOGS
        if (Schema::hasTable('security_logs')) {
            $this->safeModify('security_logs', 'ip_address', 'VARCHAR(100)');
            $this->safeModify('security_logs', 'path', 'TEXT NULL');
            $this->safeModify('security_logs', 'details', 'TEXT NULL');
        }

        // 17. PARAMÈTRES SITE
        if (Schema::hasTable('parametres_site')) {
            $this->safeModify('parametres_site', 'valeur', 'TEXT NULL');
        }

        // 18. TAUX CHANGE
        if (Schema::hasTable('taux_change')) {
            $this->safeModify('taux_change', 'note', 'TEXT NULL');
        }

        // 19. USER SESSIONS
        if (Schema::hasTable('user_sessions')) {
            $this->safeModify('user_sessions', 'session_token', 'TEXT');
            $this->safeModify('user_sessions', 'ip_address', 'VARCHAR(100)');
            $this->safeModify('user_sessions', 'user_agent', 'TEXT NULL');
        }

        // 20. PRODUITS VUES
        if (Schema::hasTable('produits_vues')) {
            $this->safeModify('produits_vues', 'ip_address', 'VARCHAR(100) NULL');
        }

        // 21. CATÉGORIES
        if (Schema::hasTable('categories')) {
            $this->safeModify('categories', 'description', 'TEXT NULL');
        }

        // 22. MARQUES
        if (Schema::hasTable('marques')) {
            $this->safeModify('marques', 'description', 'TEXT NULL');
        }

        // 23. IMAGES PRODUITS
        if (Schema::hasTable('images_produits')) {
            $this->safeModify('images_produits', 'url_image', 'TEXT');
            $this->safeModify('images_produits', 'chemin_fichier', 'TEXT');
        }

        // 24. AVIS PHOTOS
        if (Schema::hasTable('avis_photos')) {
            $this->safeModify('avis_photos', 'url_image', 'TEXT');
            $this->safeModify('avis_photos', 'chemin_fichier', 'TEXT');
        }

        // 25. CODES PROMO
        if (Schema::hasTable('codes_promo')) {
            $this->safeModify('codes_promo', 'description', 'TEXT NULL');
        }

        // 26. POINTS FIDÉLITÉ
        if (Schema::hasTable('points_fidelite')) {
            $this->safeModify('points_fidelite', 'description', 'TEXT NULL');
        }
    }

    public function down(): void
    {
        // Rollback non nécessaire
    }
};