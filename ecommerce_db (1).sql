 uj-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2026 at 02:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_appliquer_code_promo` (IN `p_code` VARCHAR(50), IN `p_utilisateur_id` BIGINT, IN `p_montant_panier` DECIMAL(10,2), OUT `p_reduction` DECIMAL(10,2), OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_code_id BIGINT;
    DECLARE v_type VARCHAR(50);
    DECLARE v_valeur DECIMAL(10,2);
    DECLARE v_min DECIMAL(10,2);
    DECLARE v_max DECIMAL(10,2);
    
    -- Récupérer le code
    SELECT id, type_reduction, valeur_reduction, montant_minimum, montant_maximum
    INTO v_code_id, v_type, v_valeur, v_min, v_max
    FROM codes_promo
    WHERE code = p_code
    AND statut = 'actif'
    AND (date_debut IS NULL OR date_debut <= NOW())
    AND (date_fin IS NULL OR date_fin > NOW());
    
    IF v_code_id IS NULL THEN
        SET p_reduction = 0;
        SET p_message = 'Code promo invalide ou expiré';
    ELSEIF p_montant_panier < v_min THEN
        SET p_reduction = 0;
        SET p_message = CONCAT('Montant minimum requis: ', v_min, '€');
    ELSE
        -- Calculer la réduction
        IF v_type = 'pourcentage' THEN
            SET p_reduction = p_montant_panier * (v_valeur / 100);
        ELSEIF v_type = 'montant_fixe' THEN
            SET p_reduction = v_valeur;
        ELSE
            SET p_reduction = 0; -- livraison gratuite gérée séparément
        END IF;
        
        -- Appliquer le maximum
        IF v_max IS NOT NULL AND p_reduction > v_max THEN
            SET p_reduction = v_max;
        END IF;
        
        SET p_message = 'Code appliqué avec succès';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_calculer_points_fidelite` (IN `p_utilisateur_id` BIGINT, OUT `p_points_actuels` INT, OUT `p_points_gagnes` INT, OUT `p_points_utilises` INT)   BEGIN
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'gain' THEN points_montant ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN type = 'gain' THEN points_montant ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN type = 'utilisation' THEN points_montant ELSE 0 END), 0)
    INTO p_points_actuels, p_points_gagnes, p_points_utilises
    FROM points_fidelite
    WHERE utilisateur_id = p_utilisateur_id;
    
    SET p_points_actuels = p_points_gagnes - p_points_utilises;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_creer_commande` (IN `p_utilisateur_id` BIGINT, IN `p_adresse_livraison_id` BIGINT, IN `p_methode_paiement` VARCHAR(50), OUT `p_commande_id` BIGINT, OUT `p_numero_commande` VARCHAR(50))   BEGIN
    DECLARE v_panier_id BIGINT;
    DECLARE v_total DECIMAL(10,2);
    
    -- Récupérer le panier actif
    SELECT id INTO v_panier_id
    FROM paniers
    WHERE utilisateur_id = p_utilisateur_id AND statut = 'actif'
    LIMIT 1;
    
    IF v_panier_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Aucun panier actif trouvé';
    END IF;
    
    -- Calculer le total
    SELECT COALESCE(SUM(quantite * prix_unitaire), 0) INTO v_total
    FROM articles_panier
    WHERE panier_id = v_panier_id;
    
    IF v_total = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Le panier est vide';
    END IF;
    
    -- Créer la commande (le trigger va générer le numéro)
    INSERT INTO commandes (utilisateur_id, montant_total, adresse_livraison_id, statut)
    VALUES (p_utilisateur_id, v_total, p_adresse_livraison_id, 'en_attente');
    
    SET p_commande_id = LAST_INSERT_ID();
    
    -- Récupérer le numéro généré
    SELECT numero_commande INTO p_numero_commande
    FROM commandes
    WHERE id = p_commande_id;
    
    -- Transférer les articles du panier vers la commande
    INSERT INTO articles_commande (commande_id, produit_id, quantite, prix)
    SELECT p_commande_id, produit_id, quantite, prix_unitaire
    FROM articles_panier
    WHERE panier_id = v_panier_id;
    
    -- Créer l'entrée de paiement
    INSERT INTO paiements (commande_id, methode, montant, statut)
    VALUES (p_commande_id, p_methode_paiement, v_total, 'en_attente');
    
    -- Convertir le panier
    UPDATE paniers SET statut = 'converti' WHERE id = v_panier_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_stats_admin` (IN `p_jours` INT)   BEGIN
    DECLARE v_date_debut DATE;
    SET v_date_debut = DATE_SUB(CURDATE(), INTERVAL p_jours DAY);
    
    -- Stats générales
    SELECT 
        COUNT(*) as total_commandes,
        SUM(montant_total) as revenu_total,
        AVG(montant_total) as panier_moyen,
        COUNT(DISTINCT utilisateur_id) as clients_uniques
    FROM commandes
    WHERE cree_le >= v_date_debut
    AND statut != 'annulee';
    
    -- Top produits
    SELECT 
        p.nom,
        SUM(ac.quantite) as quantite_vendue,
        SUM(ac.quantite * ac.prix) as revenu
    FROM articles_commande ac
    INNER JOIN produits p ON ac.produit_id = p.id
    INNER JOIN commandes c ON ac.commande_id = c.id
    WHERE c.cree_le >= v_date_debut
    AND c.statut != 'annulee'
    GROUP BY p.id, p.nom
    ORDER BY quantite_vendue DESC
    LIMIT 10;
    
    -- Répartition des statuts
    SELECT statut, COUNT(*) as nombre
    FROM commandes
    WHERE cree_le >= v_date_debut
    GROUP BY statut;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `adresses_livraison`
--

CREATE TABLE `adresses_livraison` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `nom_complet` text DEFAULT NULL,
  `telephone` text DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` text DEFAULT NULL,
  `code_postal` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `est_defaut` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `adresses_livraison`
--

INSERT INTO `adresses_livraison` (`id`, `utilisateur_id`, `nom_complet`, `telephone`, `adresse`, `ville`, `code_postal`, `instructions`, `est_defaut`, `created_at`, `updated_at`) VALUES
(1, 3, 'butembo', '0977342386', 'lukanga', 'butmbo', NULL, NULL, 0, '2026-06-12 12:35:28', '2026-06-12 12:35:28'),
(2, 3, 'BUMA VITA Moise', '0977342386', 'W7RX+CW5', 'Lubero', NULL, NULL, 0, '2026-06-12 16:35:26', '2026-06-12 16:35:26');

-- --------------------------------------------------------

--
-- Table structure for table `articles_commande`
--

CREATE TABLE `articles_commande` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `commande_id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `produit_nom` varchar(255) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `articles_commande`
--

INSERT INTO `articles_commande` (`id`, `commande_id`, `produit_id`, `produit_nom`, `quantite`, `prix`, `prix_total`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Iphone 12 pro', 1, 250.00, 250.00, '2026-06-12 12:35:40', '2026-06-12 12:35:40'),
(28, 28, 1, 'Iphone 12 pro', 6, 245.00, 1470.00, '2026-06-12 16:46:59', '2026-06-12 16:46:59'),
(29, 29, 1, 'Iphone 12 pro', 1, 245.00, 245.00, '2026-06-12 17:33:29', '2026-06-12 17:33:29'),
(30, 30, 1, 'Iphone 12 pro', 1, 245.00, 245.00, '2026-06-12 17:38:54', '2026-06-12 17:38:54'),
(31, 31, 2, 'iphone 12', 1, 350.00, 350.00, '2026-06-22 06:19:19', '2026-06-22 06:19:19'),
(32, 32, 1, 'Iphone 12 pro', 1, 245.00, 245.00, '2026-06-22 06:47:23', '2026-06-22 06:47:23'),
(33, 33, 2, 'iphone 12', 2, 350.00, 700.00, '2026-06-22 07:25:08', '2026-06-22 07:25:08');

-- --------------------------------------------------------

--
-- Table structure for table `articles_panier`
--

CREATE TABLE `articles_panier` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `panier_id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avis`
--

CREATE TABLE `avis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `commande_id` bigint(20) UNSIGNED DEFAULT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `note` tinyint(4) NOT NULL,
  `titre` varchar(255) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `est_verifie` tinyint(1) NOT NULL DEFAULT 0,
  `est_approuve` tinyint(1) NOT NULL DEFAULT 0,
  `statut` enum('en_attente','approuve','rejete') NOT NULL DEFAULT 'en_attente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `nb_utile` int(11) NOT NULL DEFAULT 0,
  `nb_inutile` int(11) NOT NULL DEFAULT 0,
  `date_publication` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `avis`
--

INSERT INTO `avis` (`id`, `utilisateur_id`, `commande_id`, `produit_id`, `note`, `titre`, `commentaire`, `est_verifie`, `est_approuve`, `statut`, `created_at`, `updated_at`, `nb_utile`, `nb_inutile`, `date_publication`) VALUES
(1, 3, NULL, 2, 3, 'vous offrer un jolie service', 'je suis vraiment ravie de ce que voous nous offrer', 0, 1, 'en_attente', '2026-06-22 06:11:51', '2026-06-22 06:11:51', 0, 0, '2026-06-22 06:11:51');

-- --------------------------------------------------------

--
-- Table structure for table `avis_photos`
--

CREATE TABLE `avis_photos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `avis_id` bigint(20) UNSIGNED NOT NULL,
  `url_image` text DEFAULT NULL,
  `chemin_fichier` text DEFAULT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avis_reponses`
--

CREATE TABLE `avis_reponses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `avis_id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `contenu` text DEFAULT NULL,
  `est_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avis_signalements`
--

CREATE TABLE `avis_signalements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `avis_id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `motif` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `est_traite` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nom` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `nom`, `slug`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Smartphone', 'smartphone', '2026-06-12 12:00:30', '2026-06-12 12:00:30');

-- --------------------------------------------------------

--
-- Table structure for table `codes_promo`
--

CREATE TABLE `codes_promo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `type_reduction` enum('pourcentage','montant_fixe','livraison_gratuite') NOT NULL,
  `valeur_reduction` decimal(10,2) NOT NULL,
  `montant_minimum` decimal(10,2) NOT NULL DEFAULT 0.00,
  `montant_maximum` decimal(10,2) DEFAULT NULL,
  `utilisation_max` int(11) NOT NULL DEFAULT 0,
  `utilisation_par_user` int(11) NOT NULL DEFAULT 1,
  `nombre_utilisations` int(11) NOT NULL DEFAULT 0,
  `statut` enum('actif','inactif','expire') NOT NULL DEFAULT 'actif',
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `categorie_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commandes`
--

CREATE TABLE `commandes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `numero_commande` varchar(50) NOT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  `frais_livraison` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `statut` enum('en_attente','payee','confirmee','en_cours_de_traitement','expediee','livree','annulee') NOT NULL DEFAULT 'en_attente',
  `statut_paiement` enum('non_paye','paye','echoue','rembourse') NOT NULL DEFAULT 'non_paye',
  `adresse_livraison_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note_client` text DEFAULT NULL,
  `note_admin` text DEFAULT NULL,
  `date_livraison_prevue` timestamp NULL DEFAULT NULL,
  `date_livraison_effective` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commandes`
--

INSERT INTO `commandes` (`id`, `utilisateur_id`, `numero_commande`, `montant_total`, `frais_livraison`, `reduction`, `statut`, `statut_paiement`, `adresse_livraison_id`, `note_client`, `note_admin`, `date_livraison_prevue`, `date_livraison_effective`, `created_at`, `updated_at`) VALUES
(1, 3, 'CMD-20260612-4377', 250.00, 0.00, 0.00, 'en_attente', 'non_paye', 1, NULL, NULL, NULL, NULL, '2026-06-12 12:35:39', '2026-06-12 13:10:01'),
(28, 3, 'CMD-20260612-8004', 1470.00, 0.00, 0.00, 'en_attente', 'non_paye', 2, NULL, NULL, NULL, NULL, '2026-06-12 16:46:59', '2026-06-12 16:46:59'),
(29, 3, 'CMD-20260612-2390', 245.00, 0.00, 0.00, 'en_attente', 'non_paye', 2, NULL, NULL, NULL, NULL, '2026-06-12 17:33:29', '2026-06-12 17:33:29'),
(30, 3, 'CMD-20260612-8217', 245.00, 0.00, 0.00, 'en_attente', 'non_paye', 2, NULL, NULL, NULL, NULL, '2026-06-12 17:38:54', '2026-06-12 17:38:54'),
(31, 3, 'CMD-20260622-7016', 350.00, 0.00, 0.00, 'en_attente', 'non_paye', 2, NULL, NULL, NULL, NULL, '2026-06-22 06:19:19', '2026-06-22 06:19:19'),
(32, 3, 'CMD-20260622-8216', 245.00, 0.00, 0.00, 'en_attente', 'non_paye', 1, NULL, NULL, NULL, NULL, '2026-06-22 06:47:23', '2026-06-22 06:47:23'),
(33, 3, 'CMD-20260622-4120EA', 700.00, 5.00, 0.00, 'en_attente', 'non_paye', 1, NULL, NULL, NULL, NULL, '2026-06-22 07:25:08', '2026-06-22 07:25:08');

-- --------------------------------------------------------

--
-- Table structure for table `couleurs`
--

CREATE TABLE `couleurs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` text DEFAULT NULL,
  `code_hex` varchar(7) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historique_statuts_commandes`
--

CREATE TABLE `historique_statuts_commandes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `commande_id` bigint(20) UNSIGNED NOT NULL,
  `ancien_statut` varchar(50) DEFAULT NULL,
  `nouveau_statut` varchar(50) NOT NULL,
  `modifie_par` bigint(20) UNSIGNED DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `cree_le` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `images_produits`
--

CREATE TABLE `images_produits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `url_image` text DEFAULT NULL,
  `est_principale` tinyint(1) NOT NULL DEFAULT 0,
  `ordre` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `images_produits`
--

INSERT INTO `images_produits` (`id`, `produit_id`, `url_image`, `est_principale`, `ordre`, `created_at`, `updated_at`) VALUES
(1, 1, 'https://localhost:8000/storage/products/images-2-1781276511-7pdD61M1.jpg', 1, 0, '2026-06-12 12:03:10', '2026-06-12 12:03:10');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `listes_souhaits`
--

CREATE TABLE `listes_souhaits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `ajoute_le` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marques`
--

CREATE TABLE `marques` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(120) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marques`
--

INSERT INTO `marques` (`id`, `nom`, `created_at`, `updated_at`) VALUES
(1, 'Apple(iphone)', '2026-06-12 12:00:57', '2026-06-12 12:00:57'),
(2, 'Samsung', '2026-06-21 17:28:44', '2026-06-21 17:28:44');

-- --------------------------------------------------------

--
-- Table structure for table `methodes_paiement`
--

CREATE TABLE `methodes_paiement` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `frais_supplementaires` decimal(10,2) NOT NULL DEFAULT 0.00,
  `statut` enum('actif','inactif') NOT NULL DEFAULT 'actif',
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `position` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2024_01_01_000001_create_utilisateurs_table', 1),
(5, '2024_01_01_000002_create_produits_tables', 1),
(6, '2024_01_01_000003_create_commandes_tables', 1),
(7, '2024_01_01_000004_create_paiements_table', 1),
(8, '2024_01_01_000005_create_paniers_tables', 1),
(9, '2024_01_01_000006_create_fidelite_promos_tables', 1),
(10, '2024_01_01_000007_create_wishlist_avis_notifications_tables', 1),
(11, '2024_01_01_000008_create_tables_supplementaires', 1),
(12, '2024_01_01_000009_create_adresses_livraison_table', 1),
(13, '2024_01_01_000010_create_taux_change_table', 1),
(14, '2024_01_01_000011_create_security_tables', 1),
(15, '2026_06_12_122419_create_permission_tables', 1),
(16, '{}_add_security_fields_to_users', 2),
(17, '{20260612223822}_add_security_fields_to_users', 3),
(18, '2026_06_12_224200_fix_paiements_methode_column', 4),
(19, '{20260613135704}_create_reviews_tables', 5),
(20, '{20260613142751}_create_product_attributes_tables', 6),
(21, '20260613142751_create_product_attributes_tables', 7),
(22, '{20260613145246}_create_wishlist_tables', 7),
(23, '20260613145246_create_wishlist_tables', 8),
(24, '{20260621212533}_fix_database_for_encryption', 9),
(25, '{20260621222842}_fix_last_login_ip_column', 10),
(26, '{20260621232106}_create_stock_movements_table', 11),
(27, '{20260621234615}_fix_missing_columns', 12),
(28, '{20260622121056}_add_missing_columns_to_avis_table', 13);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mouvements_stock`
--

CREATE TABLE `mouvements_stock` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('entree','sortie','ajustement','vente','retour') NOT NULL,
  `quantite` int(11) NOT NULL,
  `stock_avant` int(11) NOT NULL,
  `stock_apres` int(11) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `titre` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` enum('commande','paiement','promo','systeme','fidelite') NOT NULL,
  `est_lu` tinyint(1) NOT NULL DEFAULT 0,
  `lien` text DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paiements`
--

CREATE TABLE `paiements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `commande_id` bigint(20) UNSIGNED NOT NULL,
  `methode` varchar(50) DEFAULT NULL,
  `id_transaction_fournisseur` varchar(255) DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `frais` decimal(10,2) NOT NULL DEFAULT 0.00,
  `statut` enum('en_attente','succes','echoue','rembourse') NOT NULL DEFAULT 'en_attente',
  `details_paiement` text DEFAULT NULL,
  `paye_le` timestamp NULL DEFAULT NULL,
  `rembourse_le` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reference_transaction` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `paiements`
--

INSERT INTO `paiements` (`id`, `commande_id`, `methode`, `id_transaction_fournisseur`, `montant`, `frais`, `statut`, `details_paiement`, `paye_le`, `rembourse_le`, `created_at`, `updated_at`, `reference_transaction`) VALUES
(1, 1, 'maishapay', NULL, 250.00, 0.00, 'en_attente', NULL, NULL, NULL, '2026-06-12 12:35:40', '2026-06-22 08:19:05', NULL),
(2, 28, 'en_attente', NULL, 1470.00, 0.00, 'en_attente', NULL, NULL, NULL, '2026-06-12 16:46:59', '2026-06-12 16:46:59', NULL),
(3, 29, 'en_attente', NULL, 245.00, 0.00, 'en_attente', NULL, NULL, NULL, '2026-06-12 17:33:30', '2026-06-12 17:33:30', NULL),
(4, 30, 'en_attente', NULL, 245.00, 0.00, 'en_attente', NULL, NULL, NULL, '2026-06-12 17:38:54', '2026-06-12 17:38:54', NULL),
(5, 31, 'maishapay', NULL, 350.00, 0.00, 'en_attente', NULL, NULL, NULL, '2026-06-22 06:19:19', '2026-06-22 06:30:54', NULL),
(6, 32, 'maishapay', NULL, 245.00, 0.00, 'en_attente', NULL, NULL, NULL, '2026-06-22 06:47:23', '2026-06-22 06:47:36', NULL),
(7, 33, 'maishapay', NULL, 705.00, 0.00, 'en_attente', NULL, NULL, NULL, '2026-06-22 07:25:17', '2026-06-22 07:25:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `paniers`
--

CREATE TABLE `paniers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `statut` enum('actif','converti','abandonne') NOT NULL DEFAULT 'actif',
  `session_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `paniers`
--

INSERT INTO `paniers` (`id`, `utilisateur_id`, `statut`, `session_id`, `created_at`, `updated_at`) VALUES
(3, 3, 'actif', NULL, '2026-06-12 16:32:19', '2026-06-12 16:32:19');

-- --------------------------------------------------------

--
-- Table structure for table `parametres_site`
--

CREATE TABLE `parametres_site` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cle` varchar(100) NOT NULL,
  `valeur` text DEFAULT NULL,
  `type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `categorie` varchar(50) NOT NULL DEFAULT 'general',
  `description` text DEFAULT NULL,
  `modifie_le` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parametres_site`
--

INSERT INTO `parametres_site` (`id`, `cle`, `valeur`, `type`, `categorie`, `description`, `modifie_le`) VALUES
(1, 'site_nom', 'vita', 'string', 'general', NULL, '2026-06-12 15:18:36'),
(2, 'site_description', 'nos parametre', 'string', 'general', NULL, '2026-06-12 15:18:36'),
(3, 'site_email_contact', 'vitalheritier@1gmail.com', 'string', 'general', NULL, '2026-06-12 15:18:36'),
(4, 'site_telephone', '+243988401637', 'string', 'general', NULL, '2026-06-12 15:18:36'),
(5, 'devise_principale', 'USD', 'string', 'general', NULL, '2026-06-12 15:18:36'),
(6, 'frais_livraison', '5', 'number', 'general', NULL, '2026-06-12 15:18:36'),
(7, 'livraison_gratuite_seuil', '100', 'number', 'general', NULL, '2026-06-12 15:18:36');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `points_fidelite`
--

CREATE TABLE `points_fidelite` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `type` enum('gain','utilisation') NOT NULL,
  `description` text DEFAULT NULL,
  `points_montant` int(11) NOT NULL,
  `commande_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produits`
--

CREATE TABLE `produits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `prix_remise` decimal(10,2) DEFAULT NULL,
  `quantite_stock` int(11) NOT NULL DEFAULT 0,
  `categorie_id` bigint(20) UNSIGNED DEFAULT NULL,
  `marque_id` bigint(20) UNSIGNED DEFAULT NULL,
  `statut` enum('actif','inactif') NOT NULL DEFAULT 'actif',
  `note_moyenne` decimal(3,2) NOT NULL DEFAULT 0.00,
  `nombre_avis` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `slug`, `description`, `prix`, `prix_remise`, `quantite_stock`, `categorie_id`, `marque_id`, `statut`, `note_moyenne`, `nombre_avis`, `created_at`, `updated_at`) VALUES
(1, 'Iphone 12 pro', 'iphone-12-pro-6a2c1fae59d3a', 'voici mes produit dispos', 250.00, 245.00, 10, 1, 1, 'actif', 0.00, 0, '2026-06-12 12:03:10', '2026-06-12 12:03:10'),
(2, 'iphone 12', 'iphone-12-6a384b79e4eab', 'voici nos telephone disponible', 350.00, NULL, 40, 1, 1, 'actif', 3.00, 1, '2026-06-21 17:37:13', '2026-06-22 06:11:52'),
(3, 'iphone 12 pro', 'iphone-12-pro-6a384bccf40dc', NULL, 250.00, 245.00, 10, 1, 1, 'actif', 0.00, 0, '2026-06-21 17:38:36', '2026-06-21 17:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `produits_achetes`
--

CREATE TABLE `produits_achetes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `commande_id` bigint(20) UNSIGNED NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `prix_unitaire` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produits_vues`
--

CREATE TABLE `produits_vues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produit_couleurs`
--

CREATE TABLE `produit_couleurs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `couleur_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produit_tags`
--

CREATE TABLE `produit_tags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `tag_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produit_tailles`
--

CREATE TABLE `produit_tailles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `taille_id` bigint(20) UNSIGNED NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profils_utilisateurs`
--

CREATE TABLE `profils_utilisateurs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recherches_recentes`
--

CREATE TABLE `recherches_recentes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `terme` text DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `nb_resultats` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recompenses_fidelite`
--

CREATE TABLE `recompenses_fidelite` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `points_necessaires` int(11) NOT NULL,
  `type_reduction` enum('pourcentage','montant_fixe','livraison_gratuite') NOT NULL,
  `valeur_reduction` decimal(10,2) NOT NULL,
  `stock_disponible` int(11) NOT NULL DEFAULT 0,
  `statut` enum('actif','inactif') NOT NULL DEFAULT 'actif',
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_blocked_ips`
--

CREATE TABLE `security_blocked_ips` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `blocked_until` timestamp NULL DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `path` text DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL DEFAULT 'access',
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` text DEFAULT NULL,
  `slug` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tailles`
--

CREATE TABLE `tailles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` varchar(20) NOT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taux_change`
--

CREATE TABLE `taux_change` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `devise_source` varchar(10) NOT NULL DEFAULT 'USD',
  `devise_cible` varchar(10) NOT NULL DEFAULT 'CDF',
  `taux` decimal(15,4) NOT NULL DEFAULT 2800.0000,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_application` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` text DEFAULT NULL,
  `modifie_par` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `taux_change`
--

INSERT INTO `taux_change` (`id`, `devise_source`, `devise_cible`, `taux`, `est_actif`, `date_application`, `note`, `modifie_par`, `created_at`, `updated_at`) VALUES
(1, 'USD', 'CDF', 2800.0000, 1, '2026-06-12 11:48:02', 'Taux initial', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `session_token` text DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nom` text DEFAULT NULL,
  `email` varchar(500) DEFAULT NULL,
  `telephone` text DEFAULT NULL,
  `mot_de_passe_hash` varchar(255) NOT NULL,
  `role` enum('client','administrateur','super_administrateur') NOT NULL DEFAULT 'client',
  `statut` enum('actif','banni') NOT NULL DEFAULT 'actif',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_last_verified` timestamp NULL DEFAULT NULL,
  `current_session_token` text DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `email`, `telephone`, `mot_de_passe_hash`, `role`, `statut`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`, `two_factor_enabled`, `two_factor_secret`, `two_factor_last_verified`, `current_session_token`, `last_login_at`, `last_login_ip`) VALUES
(3, 'vita', 'heritiervita143@gmail.com', NULL, '$2y$12$Sli8wKBFCtXPBUjI..y/uu6FrGIvWV3bOkiWauZghrn1P9gMntp6y', 'client', 'actif', NULL, NULL, '2026-06-12 12:32:18', '2026-06-22 08:17:52', 0, NULL, NULL, NULL, '2026-06-22 08:17:52', '127.0.0.1'),
(7, 'BUMA VITA Moise', 'vitalheritier1@gmail.com', '+243977342386', '$2y$12$hfsFaYxB6zBPFf3irs7XY.TcT6oX/w.8QjGGaJSwMeBRxUukZVmTm', 'super_administrateur', 'actif', NULL, NULL, '2026-06-21 16:07:26', '2026-06-22 07:22:00', 0, NULL, NULL, NULL, '2026-06-22 07:22:00', '127.0.0.1');

-- --------------------------------------------------------

--
-- Table structure for table `utilisations_code_promo`
--

CREATE TABLE `utilisations_code_promo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code_promo_id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `commande_id` bigint(20) UNSIGNED NOT NULL,
  `montant_reduction` decimal(10,2) NOT NULL,
  `utilise_le` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_commandes_completes`
-- (See below for the actual view)
--
CREATE TABLE `v_commandes_completes` (
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_produits_complets`
-- (See below for the actual view)
--
CREATE TABLE `v_produits_complets` (
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_utilisateurs_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_utilisateurs_stats` (
);

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED NOT NULL,
  `nom_collection` varchar(100) NOT NULL DEFAULT 'Mes favoris',
  `note_personnelle` text DEFAULT NULL,
  `alerte_prix` tinyint(1) NOT NULL DEFAULT 0,
  `prix_cible` decimal(12,2) DEFAULT NULL,
  `prix_ajout` decimal(12,2) DEFAULT NULL,
  `derniere_alerte` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist_alertes`
--

CREATE TABLE `wishlist_alertes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `wishlist_id` bigint(20) UNSIGNED NOT NULL,
  `ancien_prix` decimal(12,2) NOT NULL,
  `nouveau_prix` decimal(12,2) NOT NULL,
  `pourcentage_reduction` decimal(5,2) NOT NULL DEFAULT 0.00,
  `est_lue` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist_partagees`
--

CREATE TABLE `wishlist_partagees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `nom` text DEFAULT NULL,
  `est_publique` tinyint(1) NOT NULL DEFAULT 1,
  `expire_le` timestamp NULL DEFAULT NULL,
  `nb_vues` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `v_commandes_completes`
--
DROP TABLE IF EXISTS `v_commandes_completes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_commandes_completes`  AS SELECT `c`.`id` AS `id`, `c`.`utilisateur_id` AS `utilisateur_id`, `c`.`numero_commande` AS `numero_commande`, `c`.`montant_total` AS `montant_total`, `c`.`statut` AS `statut`, `c`.`statut_paiement` AS `statut_paiement`, `c`.`adresse_livraison_id` AS `adresse_livraison_id`, `c`.`cree_le` AS `cree_le`, `u`.`nom` AS `client_nom`, `u`.`email` AS `client_email`, `al`.`nom_complet` AS `destinataire`, `al`.`adresse` AS `adresse`, `al`.`ville` AS `ville`, count(`ac`.`id`) AS `nombre_articles`, max(`p`.`statut`) AS `dernier_statut_paiement` FROM ((((`commandes` `c` left join `utilisateurs` `u` on(`c`.`utilisateur_id` = `u`.`id`)) left join `adresses_livraison` `al` on(`c`.`adresse_livraison_id` = `al`.`id`)) left join `articles_commande` `ac` on(`c`.`id` = `ac`.`commande_id`)) left join `paiements` `p` on(`c`.`id` = `p`.`commande_id`)) GROUP BY `c`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_produits_complets`
--
DROP TABLE IF EXISTS `v_produits_complets`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_produits_complets`  AS SELECT `p`.`id` AS `id`, `p`.`nom` AS `nom`, `p`.`slug` AS `slug`, `p`.`description` AS `description`, `p`.`prix` AS `prix`, `p`.`prix_remise` AS `prix_remise`, `p`.`quantite_stock` AS `quantite_stock`, `p`.`categorie_id` AS `categorie_id`, `p`.`marque_id` AS `marque_id`, `p`.`statut` AS `statut`, `p`.`cree_le` AS `cree_le`, `c`.`nom` AS `categorie_nom`, `c`.`slug` AS `categorie_slug`, `m`.`nom` AS `marque_nom`, (select `images_produits`.`url_image` from `images_produits` where `images_produits`.`produit_id` = `p`.`id` and `images_produits`.`est_principale` = 1 limit 1) AS `image_principale`, (select avg(`avis`.`note`) from `avis` where `avis`.`produit_id` = `p`.`id`) AS `note_moyenne`, (select count(0) from `avis` where `avis`.`produit_id` = `p`.`id`) AS `nombre_avis` FROM ((`produits` `p` left join `categories` `c` on(`p`.`categorie_id` = `c`.`id`)) left join `marques` `m` on(`p`.`marque_id` = `m`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_utilisateurs_stats`
--
DROP TABLE IF EXISTS `v_utilisateurs_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_utilisateurs_stats`  AS SELECT `u`.`id` AS `id`, `u`.`nom` AS `nom`, `u`.`email` AS `email`, `u`.`telephone` AS `telephone`, `u`.`mot_de_passe_hash` AS `mot_de_passe_hash`, `u`.`role` AS `role`, `u`.`statut` AS `statut`, `u`.`cree_le` AS `cree_le`, count(distinct `c`.`id`) AS `nombre_commandes`, coalesce(sum(`c`.`montant_total`),0) AS `total_depense`, max(`c`.`cree_le`) AS `derniere_commande`, coalesce((select sum(`points_fidelite`.`points_montant`) from `points_fidelite` where `points_fidelite`.`utilisateur_id` = `u`.`id` and `points_fidelite`.`type` = 'gain'),0) - coalesce((select sum(`points_fidelite`.`points_montant`) from `points_fidelite` where `points_fidelite`.`utilisateur_id` = `u`.`id` and `points_fidelite`.`type` = 'utilisation'),0) AS `points_fidelite` FROM (`utilisateurs` `u` left join `commandes` `c` on(`u`.`id` = `c`.`utilisateur_id`)) GROUP BY `u`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adresses_livraison`
--
ALTER TABLE `adresses_livraison`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adresses_livraison_utilisateur_id_foreign` (`utilisateur_id`);

--
-- Indexes for table `articles_commande`
--
ALTER TABLE `articles_commande`
  ADD PRIMARY KEY (`id`),
  ADD KEY `articles_commande_commande_id_foreign` (`commande_id`),
  ADD KEY `articles_commande_produit_id_foreign` (`produit_id`);

--
-- Indexes for table `articles_panier`
--
ALTER TABLE `articles_panier`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `articles_panier_panier_id_produit_id_unique` (`panier_id`,`produit_id`),
  ADD KEY `articles_panier_produit_id_foreign` (`produit_id`);

--
-- Indexes for table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avis_utilisateur_id_foreign` (`utilisateur_id`),
  ADD KEY `avis_produit_id_foreign` (`produit_id`),
  ADD KEY `avis_commande_id_foreign` (`commande_id`);

--
-- Indexes for table `avis_photos`
--
ALTER TABLE `avis_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avis_photos_avis_id_foreign` (`avis_id`);

--
-- Indexes for table `avis_reponses`
--
ALTER TABLE `avis_reponses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avis_reponses_avis_id_foreign` (`avis_id`),
  ADD KEY `avis_reponses_utilisateur_id_foreign` (`utilisateur_id`);

--
-- Indexes for table `avis_signalements`
--
ALTER TABLE `avis_signalements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avis_signalements_avis_id_foreign` (`avis_id`),
  ADD KEY `avis_signalements_utilisateur_id_foreign` (`utilisateur_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `codes_promo`
--
ALTER TABLE `codes_promo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codes_promo_code_unique` (`code`),
  ADD KEY `codes_promo_categorie_id_foreign` (`categorie_id`);

--
-- Indexes for table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `commandes_numero_commande_unique` (`numero_commande`),
  ADD KEY `commandes_utilisateur_id_foreign` (`utilisateur_id`),
  ADD KEY `commandes_adresse_livraison_id_foreign` (`adresse_livraison_id`);

--
-- Indexes for table `couleurs`
--
ALTER TABLE `couleurs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `historique_statuts_commandes`
--
ALTER TABLE `historique_statuts_commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `historique_statuts_commandes_commande_id_foreign` (`commande_id`),
  ADD KEY `historique_statuts_commandes_modifie_par_foreign` (`modifie_par`);

--
-- Indexes for table `images_produits`
--
ALTER TABLE `images_produits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `images_produits_produit_id_foreign` (`produit_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `listes_souhaits`
--
ALTER TABLE `listes_souhaits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `listes_souhaits_utilisateur_id_produit_id_unique` (`utilisateur_id`,`produit_id`),
  ADD KEY `listes_souhaits_produit_id_foreign` (`produit_id`);

--
-- Indexes for table `marques`
--
ALTER TABLE `marques`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `marques_nom_unique` (`nom`);

--
-- Indexes for table `methodes_paiement`
--
ALTER TABLE `methodes_paiement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `methodes_paiement_code_unique` (`code`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mouvements_stock_utilisateur_id_foreign` (`utilisateur_id`),
  ADD KEY `mouvements_stock_produit_id_created_at_index` (`produit_id`,`created_at`),
  ADD KEY `mouvements_stock_type_created_at_index` (`type`,`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_utilisateur_id_foreign` (`utilisateur_id`);

--
-- Indexes for table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paiements_commande_id_foreign` (`commande_id`);

--
-- Indexes for table `paniers`
--
ALTER TABLE `paniers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paniers_utilisateur_id_foreign` (`utilisateur_id`);

--
-- Indexes for table `parametres_site`
--
ALTER TABLE `parametres_site`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parametres_site_cle_unique` (`cle`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `points_fidelite`
--
ALTER TABLE `points_fidelite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `points_fidelite_utilisateur_id_foreign` (`utilisateur_id`),
  ADD KEY `points_fidelite_commande_id_foreign` (`commande_id`);

--
-- Indexes for table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `produits_slug_unique` (`slug`),
  ADD KEY `produits_categorie_id_foreign` (`categorie_id`),
  ADD KEY `produits_marque_id_foreign` (`marque_id`);

--
-- Indexes for table `produits_achetes`
--
ALTER TABLE `produits_achetes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produits_achetes_commande_id_foreign` (`commande_id`),
  ADD KEY `produits_achetes_produit_id_index` (`produit_id`);

--
-- Indexes for table `produits_vues`
--
ALTER TABLE `produits_vues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produits_vues_utilisateur_id_foreign` (`utilisateur_id`),
  ADD KEY `produits_vues_produit_id_created_at_index` (`produit_id`,`created_at`);

--
-- Indexes for table `produit_couleurs`
--
ALTER TABLE `produit_couleurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `produit_couleurs_produit_id_couleur_id_unique` (`produit_id`,`couleur_id`),
  ADD KEY `produit_couleurs_couleur_id_foreign` (`couleur_id`);

--
-- Indexes for table `produit_tags`
--
ALTER TABLE `produit_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `produit_tags_produit_id_tag_id_unique` (`produit_id`,`tag_id`),
  ADD KEY `produit_tags_tag_id_foreign` (`tag_id`);

--
-- Indexes for table `produit_tailles`
--
ALTER TABLE `produit_tailles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `produit_tailles_produit_id_taille_id_unique` (`produit_id`,`taille_id`),
  ADD KEY `produit_tailles_taille_id_foreign` (`taille_id`);

--
-- Indexes for table `profils_utilisateurs`
--
ALTER TABLE `profils_utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profils_utilisateurs_utilisateur_id_foreign` (`utilisateur_id`);

--
-- Indexes for table `recherches_recentes`
--
ALTER TABLE `recherches_recentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recherches_recentes_terme_index` (`terme`(768)),
  ADD KEY `recherches_recentes_utilisateur_id_created_at_index` (`utilisateur_id`,`created_at`),
  ADD KEY `recherches_recentes_session_id_created_at_index` (`session_id`,`created_at`);

--
-- Indexes for table `recompenses_fidelite`
--
ALTER TABLE `recompenses_fidelite`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `security_blocked_ips`
--
ALTER TABLE `security_blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `security_blocked_ips_ip_address_unique` (`ip_address`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `security_logs_ip_address_created_at_index` (`ip_address`,`created_at`),
  ADD KEY `security_logs_event_type_index` (`event_type`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tags_nom_unique` (`nom`) USING HASH,
  ADD UNIQUE KEY `tags_slug_unique` (`slug`) USING HASH;

--
-- Indexes for table `tailles`
--
ALTER TABLE `tailles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `taux_change`
--
ALTER TABLE `taux_change`
  ADD PRIMARY KEY (`id`),
  ADD KEY `taux_change_modifie_par_foreign` (`modifie_par`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_sessions_session_token_unique` (`session_token`) USING HASH,
  ADD KEY `user_sessions_user_id_is_active_index` (`user_id`,`is_active`);

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utilisateurs_email_unique` (`email`),
  ADD UNIQUE KEY `utilisateurs_telephone_unique` (`telephone`) USING HASH;

--
-- Indexes for table `utilisations_code_promo`
--
ALTER TABLE `utilisations_code_promo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisations_code_promo_code_promo_id_foreign` (`code_promo_id`),
  ADD KEY `utilisations_code_promo_utilisateur_id_foreign` (`utilisateur_id`),
  ADD KEY `utilisations_code_promo_commande_id_foreign` (`commande_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wishlists_utilisateur_id_produit_id_unique` (`utilisateur_id`,`produit_id`),
  ADD KEY `wishlists_produit_id_foreign` (`produit_id`),
  ADD KEY `wishlists_utilisateur_id_created_at_index` (`utilisateur_id`,`created_at`),
  ADD KEY `wishlists_alerte_prix_prix_cible_index` (`alerte_prix`,`prix_cible`);

--
-- Indexes for table `wishlist_alertes`
--
ALTER TABLE `wishlist_alertes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wishlist_alertes_wishlist_id_est_lue_index` (`wishlist_id`,`est_lue`);

--
-- Indexes for table `wishlist_partagees`
--
ALTER TABLE `wishlist_partagees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wishlist_partagees_token_unique` (`token`),
  ADD KEY `wishlist_partagees_utilisateur_id_foreign` (`utilisateur_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adresses_livraison`
--
ALTER TABLE `adresses_livraison`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `articles_commande`
--
ALTER TABLE `articles_commande`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `articles_panier`
--
ALTER TABLE `articles_panier`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `avis_photos`
--
ALTER TABLE `avis_photos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `avis_reponses`
--
ALTER TABLE `avis_reponses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `avis_signalements`
--
ALTER TABLE `avis_signalements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `codes_promo`
--
ALTER TABLE `codes_promo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `couleurs`
--
ALTER TABLE `couleurs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historique_statuts_commandes`
--
ALTER TABLE `historique_statuts_commandes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `images_produits`
--
ALTER TABLE `images_produits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `listes_souhaits`
--
ALTER TABLE `listes_souhaits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marques`
--
ALTER TABLE `marques`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `methodes_paiement`
--
ALTER TABLE `methodes_paiement`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `paniers`
--
ALTER TABLE `paniers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `parametres_site`
--
ALTER TABLE `parametres_site`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `points_fidelite`
--
ALTER TABLE `points_fidelite`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `produits_achetes`
--
ALTER TABLE `produits_achetes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produits_vues`
--
ALTER TABLE `produits_vues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produit_couleurs`
--
ALTER TABLE `produit_couleurs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produit_tags`
--
ALTER TABLE `produit_tags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produit_tailles`
--
ALTER TABLE `produit_tailles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profils_utilisateurs`
--
ALTER TABLE `profils_utilisateurs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recherches_recentes`
--
ALTER TABLE `recherches_recentes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recompenses_fidelite`
--
ALTER TABLE `recompenses_fidelite`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_blocked_ips`
--
ALTER TABLE `security_blocked_ips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tailles`
--
ALTER TABLE `tailles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `taux_change`
--
ALTER TABLE `taux_change`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `utilisations_code_promo`
--
ALTER TABLE `utilisations_code_promo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist_alertes`
--
ALTER TABLE `wishlist_alertes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist_partagees`
--
ALTER TABLE `wishlist_partagees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adresses_livraison`
--
ALTER TABLE `adresses_livraison`
  ADD CONSTRAINT `adresses_livraison_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `articles_commande`
--
ALTER TABLE `articles_commande`
  ADD CONSTRAINT `articles_commande_commande_id_foreign` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `articles_commande_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`);

--
-- Constraints for table `articles_panier`
--
ALTER TABLE `articles_panier`
  ADD CONSTRAINT `articles_panier_panier_id_foreign` FOREIGN KEY (`panier_id`) REFERENCES `paniers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `articles_panier_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`);

--
-- Constraints for table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_commande_id_foreign` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `avis_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avis_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `avis_photos`
--
ALTER TABLE `avis_photos`
  ADD CONSTRAINT `avis_photos_avis_id_foreign` FOREIGN KEY (`avis_id`) REFERENCES `avis` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `avis_reponses`
--
ALTER TABLE `avis_reponses`
  ADD CONSTRAINT `avis_reponses_avis_id_foreign` FOREIGN KEY (`avis_id`) REFERENCES `avis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avis_reponses_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `avis_signalements`
--
ALTER TABLE `avis_signalements`
  ADD CONSTRAINT `avis_signalements_avis_id_foreign` FOREIGN KEY (`avis_id`) REFERENCES `avis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avis_signalements_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `codes_promo`
--
ALTER TABLE `codes_promo`
  ADD CONSTRAINT `codes_promo_categorie_id_foreign` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_adresse_livraison_id_foreign` FOREIGN KEY (`adresse_livraison_id`) REFERENCES `adresses_livraison` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `commandes_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`);

--
-- Constraints for table `historique_statuts_commandes`
--
ALTER TABLE `historique_statuts_commandes`
  ADD CONSTRAINT `historique_statuts_commandes_commande_id_foreign` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historique_statuts_commandes_modifie_par_foreign` FOREIGN KEY (`modifie_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `images_produits`
--
ALTER TABLE `images_produits`
  ADD CONSTRAINT `images_produits_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `listes_souhaits`
--
ALTER TABLE `listes_souhaits`
  ADD CONSTRAINT `listes_souhaits_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `listes_souhaits_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `mouvements_stock_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mouvements_stock_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_commande_id_foreign` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `paniers`
--
ALTER TABLE `paniers`
  ADD CONSTRAINT `paniers_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `points_fidelite`
--
ALTER TABLE `points_fidelite`
  ADD CONSTRAINT `points_fidelite_commande_id_foreign` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `points_fidelite_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_categorie_id_foreign` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `produits_marque_id_foreign` FOREIGN KEY (`marque_id`) REFERENCES `marques` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `produits_achetes`
--
ALTER TABLE `produits_achetes`
  ADD CONSTRAINT `produits_achetes_commande_id_foreign` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produits_achetes_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `produits_vues`
--
ALTER TABLE `produits_vues`
  ADD CONSTRAINT `produits_vues_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produits_vues_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `produit_couleurs`
--
ALTER TABLE `produit_couleurs`
  ADD CONSTRAINT `produit_couleurs_couleur_id_foreign` FOREIGN KEY (`couleur_id`) REFERENCES `couleurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produit_couleurs_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `produit_tags`
--
ALTER TABLE `produit_tags`
  ADD CONSTRAINT `produit_tags_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produit_tags_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `produit_tailles`
--
ALTER TABLE `produit_tailles`
  ADD CONSTRAINT `produit_tailles_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produit_tailles_taille_id_foreign` FOREIGN KEY (`taille_id`) REFERENCES `tailles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profils_utilisateurs`
--
ALTER TABLE `profils_utilisateurs`
  ADD CONSTRAINT `profils_utilisateurs_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recherches_recentes`
--
ALTER TABLE `recherches_recentes`
  ADD CONSTRAINT `recherches_recentes_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `taux_change`
--
ALTER TABLE `taux_change`
  ADD CONSTRAINT `taux_change_modifie_par_foreign` FOREIGN KEY (`modifie_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `utilisations_code_promo`
--
ALTER TABLE `utilisations_code_promo`
  ADD CONSTRAINT `utilisations_code_promo_code_promo_id_foreign` FOREIGN KEY (`code_promo_id`) REFERENCES `codes_promo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `utilisations_code_promo_commande_id_foreign` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `utilisations_code_promo_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist_alertes`
--
ALTER TABLE `wishlist_alertes`
  ADD CONSTRAINT `wishlist_alertes_wishlist_id_foreign` FOREIGN KEY (`wishlist_id`) REFERENCES `wishlists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist_partagees`
--
ALTER TABLE `wishlist_partagees`
  ADD CONSTRAINT `wishlist_partagees_utilisateur_id_foreign` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `expire_codes_promo` ON SCHEDULE EVERY 1 DAY STARTS '2026-06-11 12:15:50' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    UPDATE codes_promo
    SET statut = 'expire'
    WHERE statut = 'actif'
    AND date_fin IS NOT NULL
    AND date_fin < NOW();
END$$

CREATE DEFINER=`root`@`localhost` EVENT `clean_abandoned_carts` ON SCHEDULE EVERY 1 DAY STARTS '2026-06-11 03:00:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- Supprimer les paniers actifs de plus de 30 jours
    DELETE FROM paniers
    WHERE statut = 'actif'
    AND id IN (
        SELECT id FROM (
            SELECT p.id
            FROM paniers p
            LEFT JOIN articles_panier ap ON p.id = ap.panier_id
            WHERE p.statut = 'actif'
            AND (
                SELECT MAX(cree_le) 
                FROM articles_panier 
                WHERE panier_id = p.id
            ) < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) AS temp
    );
END$$

CREATE DEFINER=`root`@`localhost` EVENT `archive_old_orders` ON SCHEDULE EVERY 1 MONTH STARTS '2026-07-01 02:00:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- Marquer les très anciennes commandes comme archivées
    UPDATE commandes
    SET statut = 'livree'
    WHERE statut IN ('en_attente', 'payee')
    AND cree_le < DATE_SUB(NOW(), INTERVAL 2 YEAR);
END$$

CREATE DEFINER=`root`@`localhost` EVENT `expire_loyalty_points` ON SCHEDULE EVERY 1 DAY STARTS '2026-06-11 04:00:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- Ajouter une entrée négative pour les points expirés
    INSERT INTO points_fidelite (utilisateur_id, points, type, description, points_montant)
    SELECT 
        pf.utilisateur_id,
        -SUM(pf.points_montant),
        'utilisation',
        'Points expirés (plus d''un an)',
        SUM(pf.points_montant)
    FROM points_fidelite pf
    WHERE pf.type = 'gain'
    AND pf.cree_le < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    AND pf.utilisateur_id NOT IN (
        SELECT DISTINCT utilisateur_id 
        FROM points_fidelite 
        WHERE type = 'utilisation'
        AND description = 'Points expirés (plus d''un an)'
        AND cree_le >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    )
    GROUP BY pf.utilisateur_id;
END$$

CREATE DEFINER=`root`@`localhost` EVENT `remind_abandoned_carts` ON SCHEDULE EVERY 6 HOUR STARTS '2026-06-11 12:15:54' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- Créer une notification pour les paniers abandonnés depuis 24h
    INSERT INTO notifications (utilisateur_id, titre, message, type)
    SELECT DISTINCT
        p.utilisateur_id,
        'Votre panier vous attend ! 🛒',
        'Vous avez des articles dans votre panier. Finalisez votre commande avant qu''ils ne disparaissent !',
        'promo'
    FROM paniers p
    INNER JOIN articles_panier ap ON p.id = ap.panier_id
    WHERE p.statut = 'actif'
    AND ap.cree_le < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    AND p.utilisateur_id NOT IN (
        SELECT utilisateur_id 
        FROM notifications 
        WHERE titre = 'Votre panier vous attend ! 🛒'
        AND cree_le > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    );
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
