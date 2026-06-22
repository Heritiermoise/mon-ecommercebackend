-- ============================================
-- SCRIPT SQL COMPLET POUR SUPPORTER LE CHIFFREMENT
-- Base de données: ecommerce_db
-- Exécuter dans phpMyAdmin ou MySQL CLI
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. MODIFICATION DE TOUTES LES TABLES
-- ============================================

-- Table: utilisateurs
ALTER TABLE \utilisateurs\
  MODIFY COLUMN \
om\ TEXT,
  MODIFY COLUMN \email\ TEXT,
  MODIFY COLUMN \	elephone\ TEXT NULL,
  MODIFY COLUMN \	wo_factor_secret\ TEXT NULL,
  MODIFY COLUMN \current_session_token\ TEXT NULL,
  MODIFY COLUMN \last_login_ip\ TEXT NULL,
  MODIFY COLUMN \last_login_at\ DATETIME NULL;

-- Table: adresses_livraison
ALTER TABLE \dresses_livraison\
  MODIFY COLUMN \
om_complet\ TEXT,
  MODIFY COLUMN \	elephone\ TEXT,
  MODIFY COLUMN \dresse\ TEXT,
  MODIFY COLUMN \ille\ TEXT,
  MODIFY COLUMN \code_postal\ TEXT NULL,
  MODIFY COLUMN \instructions\ TEXT NULL;

-- Table: commandes
ALTER TABLE \commandes\
  MODIFY COLUMN \
umero_commande\ TEXT,
  MODIFY COLUMN \
ote_client\ TEXT NULL,
  MODIFY COLUMN \
ote_admin\ TEXT NULL;

-- Table: paiements
ALTER TABLE \paiements\
  MODIFY COLUMN \methode\ TEXT,
  MODIFY COLUMN \eference_transaction\ TEXT NULL;

-- Table: produits
ALTER TABLE \produits\
  MODIFY COLUMN \
om\ TEXT,
  MODIFY COLUMN \slug\ TEXT,
  MODIFY COLUMN \description\ TEXT NULL;

-- Table: categories
ALTER TABLE \categories\
  MODIFY COLUMN \
om\ TEXT,
  MODIFY COLUMN \slug\ TEXT,
  MODIFY COLUMN \description\ TEXT NULL;

-- Table: marques
ALTER TABLE \marques\
  MODIFY COLUMN \
om\ TEXT,
  MODIFY COLUMN \description\ TEXT NULL;

-- Table: images_produits
ALTER TABLE \images_produits\
  MODIFY COLUMN \url_image\ TEXT,
  MODIFY COLUMN \chemin_fichier\ TEXT;

-- Table: avis
ALTER TABLE \vis\
  MODIFY COLUMN \	itre\ TEXT NULL,
  MODIFY COLUMN \commentaire\ TEXT;

-- Table: avis_photos
ALTER TABLE \vis_photos\
  MODIFY COLUMN \url_image\ TEXT,
  MODIFY COLUMN \chemin_fichier\ TEXT;

-- Table: avis_reponses
ALTER TABLE \vis_reponses\
  MODIFY COLUMN \contenu\ TEXT;

-- Table: avis_signalements
ALTER TABLE \vis_signalements\
  MODIFY COLUMN \motif\ TEXT,
  MODIFY COLUMN \details\ TEXT NULL;

-- Table: wishlists
ALTER TABLE \wishlists\
  MODIFY COLUMN \
om_collection\ TEXT,
  MODIFY COLUMN \
ote_personnelle\ TEXT NULL;

-- Table: wishlist_partagees
ALTER TABLE \wishlist_partagees\
  MODIFY COLUMN \	oken\ TEXT,
  MODIFY COLUMN \
om\ TEXT;

-- Table: notifications
ALTER TABLE \
otifications\
  MODIFY COLUMN \	ype\ TEXT,
  MODIFY COLUMN \	itre\ TEXT,
  MODIFY COLUMN \message\ TEXT,
  MODIFY COLUMN \lien\ TEXT NULL;

-- Table: tags
ALTER TABLE \	ags\
  MODIFY COLUMN \
om\ TEXT,
  MODIFY COLUMN \slug\ TEXT;

-- Table: couleurs
ALTER TABLE \couleurs\
  MODIFY COLUMN \
om\ TEXT,
  MODIFY COLUMN \code_hex\ TEXT;

-- Table: tailles
ALTER TABLE \	ailles\
  MODIFY COLUMN \
om\ TEXT;

-- Table: produit_tags
ALTER TABLE \produit_tags\
  MODIFY COLUMN \produit_id\ BIGINT,
  MODIFY COLUMN \	ag_id\ BIGINT;

-- Table: produit_couleurs
ALTER TABLE \produit_couleurs\
  MODIFY COLUMN \produit_id\ BIGINT,
  MODIFY COLUMN \couleur_id\ BIGINT;

-- Table: produit_tailles
ALTER TABLE \produit_tailles\
  MODIFY COLUMN \produit_id\ BIGINT,
  MODIFY COLUMN \	aille_id\ BIGINT;

-- Table: recherches_recentes
ALTER TABLE \echerches_recentes\
  MODIFY COLUMN \session_id\ TEXT NULL,
  MODIFY COLUMN \	erme\ TEXT,
  MODIFY COLUMN \ip_address\ TEXT NULL;

-- Table: produits_vues
ALTER TABLE \produits_vues\
  MODIFY COLUMN \session_id\ TEXT NULL,
  MODIFY COLUMN \ip_address\ TEXT NULL;

-- Table: produits_achetes
ALTER TABLE \produits_achetes\
  MODIFY COLUMN \produit_id\ BIGINT,
  MODIFY COLUMN \commande_id\ BIGINT;

-- Table: security_blocked_ips
ALTER TABLE \security_blocked_ips\
  MODIFY COLUMN \ip_address\ TEXT,
  MODIFY COLUMN \eason\ TEXT;

-- Table: security_logs
ALTER TABLE \security_logs\
  MODIFY COLUMN \ip_address\ TEXT,
  MODIFY COLUMN \event_type\ TEXT,
  MODIFY COLUMN \details\ TEXT NULL;

-- Table: parametres_site
ALTER TABLE \parametres_site\
  MODIFY COLUMN \cle\ TEXT,
  MODIFY COLUMN \aleur\ TEXT NULL;

-- Table: taux_change
ALTER TABLE \	aux_change\
  MODIFY COLUMN \devise_source\ TEXT,
  MODIFY COLUMN \devise_cible\ TEXT,
  MODIFY COLUMN \
ote\ TEXT NULL;

-- Table: user_sessions
ALTER TABLE \user_sessions\
  MODIFY COLUMN \session_token\ TEXT,
  MODIFY COLUMN \ip_address\ TEXT,
  MODIFY COLUMN \user_agent\ TEXT NULL;

-- Table: codes_promo
ALTER TABLE \codes_promo\
  MODIFY COLUMN \code\ TEXT,
  MODIFY COLUMN \description\ TEXT NULL;

-- Table: points_fidelite
ALTER TABLE \points_fidelite\
  MODIFY COLUMN \description\ TEXT NULL;

-- Table: panier
ALTER TABLE \paniers\
  MODIFY COLUMN \statut\ TEXT;

-- Table: articles_panier
ALTER TABLE \rticles_panier\
  MODIFY COLUMN \prix_unitaire\ DECIMAL(15,4);

-- Table: articles_commande
ALTER TABLE \rticles_commande\
  MODIFY COLUMN \produit_nom\ TEXT,
  MODIFY COLUMN \prix\ DECIMAL(15,4),
  MODIFY COLUMN \prix_total\ DECIMAL(15,4);

-- ============================================
-- 2. CRÉATION DES VIEWS
-- ============================================

-- Vue: Vue d'ensemble des commandes
CREATE OR REPLACE VIEW \_commandes_complete\ AS
SELECT 
  c.id,
  c.numero_commande,
  c.utilisateur_id,
  u.nom AS nom_client,
  u.email AS email_client,
  c.montant_total,
  c.frais_livraison,
  c.reduction,
  (c.montant_total + c.frais_livraison - c.reduction) AS total_final,
  c.statut,
  c.statut_paiement,
  c.created_at,
  COUNT(ac.id) AS nombre_articles
FROM commandes c
LEFT JOIN utilisateurs u ON c.utilisateur_id = u.id
LEFT JOIN articles_commande ac ON c.id = ac.commande_id
GROUP BY c.id;

-- Vue: Top produits vendus
CREATE OR REPLACE VIEW \_top_produits\ AS
SELECT 
  p.id,
  p.nom,
  p.slug,
  p.prix,
  p.prix_remise,
  COUNT(ac.id) AS nombre_ventes,
  SUM(ac.quantite) AS quantite_totale_vendue,
  SUM(ac.prix_total) AS revenu_total
FROM produits p
LEFT JOIN articles_commande ac ON p.id = ac.produit_id
LEFT JOIN commandes c ON ac.commande_id = c.id
WHERE c.statut_paiement = 'paye'
GROUP BY p.id
ORDER BY quantite_totale_vendue DESC;

-- Vue: Statistiques clients
CREATE OR REPLACE VIEW \_statistiques_clients\ AS
SELECT 
  u.id,
  u.nom,
  u.email,
  u.role,
  u.statut,
  COUNT(DISTINCT c.id) AS nombre_commandes,
  SUM(CASE WHEN c.statut_paiement = 'paye' THEN c.montant_total ELSE 0 END) AS total_depense,
  AVG(CASE WHEN c.statut_paiement = 'paye' THEN c.montant_total END) AS panier_moyen,
  MAX(c.created_at) AS derniere_commande
FROM utilisateurs u
LEFT JOIN commandes c ON u.id = c.utilisateur_id
WHERE u.role = 'client'
GROUP BY u.id;

-- Vue: Produits avec avis
CREATE OR REPLACE VIEW \_produits_avis\ AS
SELECT 
  p.id,
  p.nom,
  p.slug,
  p.prix,
  p.note_moyenne,
  p.nombre_avis,
  COUNT(a.id) AS total_avis,
  AVG(a.note) AS note_calculee,
  SUM(CASE WHEN a.est_verifie = 1 THEN 1 ELSE 0 END) AS avis_verifies
FROM produits p
LEFT JOIN avis a ON p.id = a.produit_id AND a.est_approuve = 1
GROUP BY p.id;

-- Vue: Revenus par mois
CREATE OR REPLACE VIEW \_revenus_mensuels\ AS
SELECT 
  DATE_FORMAT(created_at, '%Y-%m') AS mois,
  COUNT(*) AS nombre_commandes,
  SUM(montant_total) AS revenu_total,
  AVG(montant_total) AS panier_moyen
FROM commandes
WHERE statut_paiement = 'paye'
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY mois DESC;

-- Vue: Stock des produits
CREATE OR REPLACE VIEW \_stock_produits\ AS
SELECT 
  p.id,
  p.nom,
  p.slug,
  p.quantite_stock,
  c.nom AS categorie,
  m.nom AS marque,
  CASE 
    WHEN p.quantite_stock = 0 THEN 'Rupture'
    WHEN p.quantite_stock < 10 THEN 'Stock faible'
    ELSE 'En stock'
  END AS statut_stock
FROM produits p
LEFT JOIN categories c ON p.categorie_id = c.id
LEFT JOIN marques m ON p.marque_id = m.id;

-- ============================================
-- 3. CRÉATION DES PROCÉDURES STOCKÉES
-- ============================================

-- Procédure: Calculer les statistiques globales
DELIMITER //
CREATE PROCEDURE \sp_calculer_statistiques\(
  IN date_debut DATE,
  IN date_fin DATE
)
BEGIN
  SELECT 
    COUNT(*) AS total_commandes,
    SUM(CASE WHEN statut_paiement = 'paye' THEN montant_total ELSE 0 END) AS revenu_total,
    AVG(CASE WHEN statut_paiement = 'paye' THEN montant_total END) AS panier_moyen,
    COUNT(DISTINCT utilisateur_id) AS clients_uniques,
    SUM(frais_livraison) AS total_frais_livraison,
    SUM(reduction) AS total_reductions
  FROM commandes
  WHERE created_at BETWEEN date_debut AND date_fin;
END //
DELIMITER ;

-- Procédure: Mettre à jour la note moyenne d'un produit
DELIMITER //
CREATE PROCEDURE \sp_mettre_a_jour_note_produit\(
  IN produit_id INT
)
BEGIN
  UPDATE produits
  SET 
    note_moyenne = (
      SELECT AVG(note) 
      FROM avis 
      WHERE produit_id = produits.id AND est_approuve = 1
    ),
    nombre_avis = (
      SELECT COUNT(*) 
      FROM avis 
      WHERE produit_id = produits.id AND est_approuve = 1
    )
  WHERE id = produit_id;
END //
DELIMITER ;

-- Procédure: Transférer wishlist vers panier
DELIMITER //
CREATE PROCEDURE \sp_transfert_wishlist_vers_panier\(
  IN user_id INT,
  OUT nb_transfere INT,
  OUT nb_echec INT
)
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE w_id INT;
  DECLARE w_produit_id INT;
  DECLARE w_prix DECIMAL(10,2);
  DECLARE panier_id INT;
  DECLARE stock_dispo INT;
  
  DECLARE cur CURSOR FOR 
    SELECT id, produit_id FROM wishlists WHERE utilisateur_id = user_id;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  
  SET nb_transfere = 0;
  SET nb_echec = 0;
  
  -- Créer ou récupérer le panier
  SELECT id INTO panier_id FROM paniers 
  WHERE utilisateur_id = user_id AND statut = 'actif' 
  LIMIT 1;
  
  IF panier_id IS NULL THEN
    INSERT INTO paniers (utilisateur_id, statut, date_creation)
    VALUES (user_id, 'actif', NOW());
    SET panier_id = LAST_INSERT_ID();
  END IF;
  
  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO w_id, w_produit_id;
    IF done THEN
      LEAVE read_loop;
    END IF;
    
    -- Vérifier le stock
    SELECT quantite_stock, COALESCE(prix_remise, prix) 
    INTO stock_dispo, w_prix
    FROM produits WHERE id = w_produit_id;
    
    IF stock_dispo > 0 THEN
      -- Ajouter au panier
      INSERT INTO articles_panier (panier_id, produit_id, quantite, prix_unitaire)
      VALUES (panier_id, w_produit_id, 1, w_prix)
      ON DUPLICATE KEY UPDATE 
        quantite = quantite + 1,
        prix_unitaire = w_prix;
      
      SET nb_transfere = nb_transfere + 1;
    ELSE
      SET nb_echec = nb_echec + 1;
    END IF;
  END LOOP;
  CLOSE cur;
  
  -- Supprimer les éléments transférés de la wishlist
  DELETE FROM wishlists WHERE utilisateur_id = user_id;
END //
DELIMITER ;

-- Procédure: Générer rapport de ventes
DELIMITER //
CREATE PROCEDURE \sp_rapport_ventes\(
  IN periode VARCHAR(20)
)
BEGIN
  CASE periode
    WHEN 'jour' THEN
      SELECT DATE(created_at) AS periode, COUNT(*) AS nb_commandes, 
             SUM(montant_total) AS revenu
      FROM commandes WHERE statut_paiement = 'paye'
      GROUP BY DATE(created_at) ORDER BY periode DESC LIMIT 30;
    WHEN 'semaine' THEN
      SELECT YEARWEEK(created_at) AS periode, COUNT(*) AS nb_commandes,
             SUM(montant_total) AS revenu
      FROM commandes WHERE statut_paiement = 'paye'
      GROUP BY YEARWEEK(created_at) ORDER BY periode DESC LIMIT 12;
    WHEN 'mois' THEN
      SELECT DATE_FORMAT(created_at, '%Y-%m') AS periode, COUNT(*) AS nb_commandes,
             SUM(montant_total) AS revenu
      FROM commandes WHERE statut_paiement = 'paye'
      GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY periode DESC LIMIT 12;
    ELSE
      SELECT DATE_FORMAT(created_at, '%Y') AS periode, COUNT(*) AS nb_commandes,
             SUM(montant_total) AS revenu
      FROM commandes WHERE statut_paiement = 'paye'
      GROUP BY DATE_FORMAT(created_at, '%Y') ORDER BY periode DESC;
  END CASE;
END //
DELIMITER ;

-- Procédure: Détecter les produits en rupture imminente
DELIMITER //
CREATE PROCEDURE \sp_alerte_stock\(
  IN seuil INT
)
BEGIN
  SELECT p.id, p.nom, p.quantite_stock, c.nom AS categorie,
         (SELECT SUM(quantite) FROM articles_commande ac 
          JOIN commandes c2 ON ac.commande_id = c2.id 
          WHERE ac.produit_id = p.id AND c2.statut_paiement = 'paye'
          AND c2.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         ) AS ventes_30j
  FROM produits p
  LEFT JOIN categories c ON p.categorie_id = c.id
  WHERE p.quantite_stock <= seuil
  ORDER BY p.quantite_stock ASC;
END //
DELIMITER ;

-- ============================================
-- 4. CRÉATION DES EVENTS (TÂCHES PLANIFIÉES)
-- ============================================

-- Activer l'event scheduler
SET GLOBAL event_scheduler = ON;

-- Event: Nettoyage des recherches récentes (30 jours)
DELIMITER //
CREATE EVENT IF NOT EXISTS \evt_nettoyer_recherches\
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
  DELETE FROM recherches_recentes 
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //
DELIMITER ;

-- Event: Nettoyage des logs de sécurité (60 jours)
DELIMITER //
CREATE EVENT IF NOT EXISTS \evt_nettoyer_logs_securite\
ON SCHEDULE EVERY 7 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
  DELETE FROM security_logs 
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY);
END //
DELIMITER ;

-- Event: Mise à jour automatique des notes moyennes
DELIMITER //
CREATE EVENT IF NOT EXISTS \evt_maj_notes_moyennes\
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
  UPDATE produits p
  SET 
    note_moyenne = COALESCE((
      SELECT AVG(note) FROM avis 
      WHERE produit_id = p.id AND est_approuve = 1
    ), 0),
    nombre_avis = COALESCE((
      SELECT COUNT(*) FROM avis 
      WHERE produit_id = p.id AND est_approuve = 1
    ), 0);
END //
DELIMITER ;

-- Event: Archivage des anciennes commandes (2 ans)
DELIMITER //
CREATE EVENT IF NOT EXISTS \evt_archiver_commandes\
ON SCHEDULE EVERY 30 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
  -- Marquer les vieilles commandes comme archivées
  UPDATE commandes 
  SET note_admin = CONCAT(COALESCE(note_admin, ''), '[ARCHIVE] ')
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)
    AND statut IN ('livree', 'annulee');
END //
DELIMITER ;

-- Event: Suppression des tokens expirés
DELIMITER //
CREATE EVENT IF NOT EXISTS \evt_nettoyer_tokens\
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
  DELETE FROM user_sessions 
  WHERE last_activity < DATE_SUB(NOW(), INTERVAL 7 DAY);
END //
DELIMITER ;

-- ============================================
-- 5. CRÉATION DES TRIGGERS
-- ============================================

-- Trigger: Mettre à jour la note moyenne après un avis
DELIMITER //
CREATE TRIGGER \	rg_after_avis_insert\
AFTER INSERT ON avis
FOR EACH ROW
BEGIN
  UPDATE produits
  SET 
    note_moyenne = (SELECT AVG(note) FROM avis WHERE produit_id = NEW.produit_id AND est_approuve = 1),
    nombre_avis = (SELECT COUNT(*) FROM avis WHERE produit_id = NEW.produit_id AND est_approuve = 1)
  WHERE id = NEW.produit_id;
END //
DELIMITER ;

-- Trigger: Mettre à jour après suppression d'avis
DELIMITER //
CREATE TRIGGER \	rg_after_avis_delete\
AFTER DELETE ON avis
FOR EACH ROW
BEGIN
  UPDATE produits
  SET 
    note_moyenne = COALESCE((SELECT AVG(note) FROM avis WHERE produit_id = OLD.produit_id AND est_approuve = 1), 0),
    nombre_avis = COALESCE((SELECT COUNT(*) FROM avis WHERE produit_id = OLD.produit_id AND est_approuve = 1), 0)
  WHERE id = OLD.produit_id;
END //
DELIMITER ;

-- Trigger: Enregistrer la vente après confirmation de paiement
DELIMITER //
CREATE TRIGGER \	rg_after_paiement_confirm\
AFTER UPDATE ON commandes
FOR EACH ROW
BEGIN
  IF NEW.statut_paiement = 'paye' AND OLD.statut_paiement != 'paye' THEN
    -- Insérer dans produits_achetes
    INSERT INTO produits_achetes (produit_id, commande_id, quantite, prix_unitaire)
    SELECT ac.produit_id, NEW.id, ac.quantite, ac.prix
    FROM articles_commande ac
    WHERE ac.commande_id = NEW.id;
  END IF;
END //
DELIMITER ;

-- ============================================
-- 6. INDEX POUR OPTIMISATION
-- ============================================

-- Index sur utilisateurs
CREATE INDEX IF NOT EXISTS idx_users_email ON utilisateurs(email(100));
CREATE INDEX IF NOT EXISTS idx_users_role ON utilisateurs(role(50));
CREATE INDEX IF NOT EXISTS idx_users_statut ON utilisateurs(statut(20));

-- Index sur commandes
CREATE INDEX IF NOT EXISTS idx_commandes_numero ON commandes(numero_commande(100));
CREATE INDEX IF NOT EXISTS idx_commandes_utilisateur ON commandes(utilisateur_id);
CREATE INDEX IF NOT EXISTS idx_commandes_statut ON commandes(statut(50));
CREATE INDEX IF NOT EXISTS idx_commandes_paiement ON commandes(statut_paiement(50));
CREATE INDEX IF NOT EXISTS idx_commandes_date ON commandes(created_at);

-- Index sur produits
CREATE INDEX IF NOT EXISTS idx_produits_slug ON produits(slug(100));
CREATE INDEX IF NOT EXISTS idx_produits_categorie ON produits(categorie_id);
CREATE INDEX IF NOT EXISTS idx_produits_marque ON produits(marque_id);
CREATE INDEX IF NOT EXISTS idx_produits_statut ON produits(statut(20));
CREATE INDEX IF NOT EXISTS idx_produits_prix ON produits(prix);

-- Index sur avis
CREATE INDEX IF NOT EXISTS idx_avis_produit ON avis(produit_id);
CREATE INDEX IF NOT EXISTS idx_avis_utilisateur ON avis(utilisateur_id);
CREATE INDEX IF NOT EXISTS idx_avis_approuve ON avis(est_approuve);

-- Index sur paiements
CREATE INDEX IF NOT EXISTS idx_paiements_commande ON paiements(commande_id);
CREATE INDEX IF NOT EXISTS idx_paiements_statut ON paiements(statut(50));

-- Index sur wishlists
CREATE INDEX IF NOT EXISTS idx_wishlists_utilisateur ON wishlists(utilisateur_id);
CREATE INDEX IF NOT EXISTS idx_wishlists_unique ON wishlists(utilisateur_id, produit_id);

-- Index sur notifications
CREATE INDEX IF NOT EXISTS idx_notifications_utilisateur ON notifications(utilisateur_id);
CREATE INDEX IF NOT EXISTS idx_notifications_lu ON notifications(lu);

-- Index sur security_logs
CREATE INDEX IF NOT EXISTS idx_security_logs_ip ON security_logs(ip_address(100));
CREATE INDEX IF NOT EXISTS idx_security_logs_date ON security_logs(created_at);

-- ============================================
-- 7. VÉRIFICATION FINALE
-- ============================================

SELECT '✅ Base de données configurée avec succès!' AS message;
SELECT COUNT(*) AS total_tables FROM information_schema.tables WHERE table_schema = 'ecommerce_db';
SELECT COUNT(*) AS total_views FROM information_schema.views WHERE table_schema = 'ecommerce_db';
SELECT COUNT(*) AS total_procedures FROM information_schema.routines WHERE routine_schema = 'ecommerce_db' AND routine_type = 'PROCEDURE';
SELECT COUNT(*) AS total_events FROM information_schema.events WHERE event_schema = 'ecommerce_db';
SELECT COUNT(*) AS total_triggers FROM information_schema.triggers WHERE trigger_schema = 'ecommerce_db';

SET FOREIGN_KEY_CHECKS = 1;
