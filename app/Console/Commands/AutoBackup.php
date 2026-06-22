<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AutoBackup extends Command
{
    protected $signature = 'backup:auto {--encrypt : Chiffrer la sauvegarde}';
    protected $description = 'Sauvegarde automatique de la base de donnees';

    public function handle()
    {
        $this->info('Demarrage de la sauvegarde...');

        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupDir = storage_path('app/backups');
            
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $backupFile = $backupDir . '/backup_' . $timestamp . '.sql';

            // Configuration MySQL
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            // Commande mysqldump
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s',
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbName),
                escapeshellarg($backupFile)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Erreur mysqldump: ' . implode("\n", $output));
            }

            // Chiffrer si demandé
            if ($this->option('encrypt')) {
                $encryptedFile = $backupFile . '.enc';
                $key = env('BACKUP_ENCRYPTION_KEY', 'default-key-change-me');
                
                $encryptCommand = sprintf(
                    'openssl enc -aes-256-cbc -salt -in %s -out %s -k %s',
                    escapeshellarg($backupFile),
                    escapeshellarg($encryptedFile),
                    escapeshellarg($key)
                );
                
                exec($encryptCommand, $output, $returnCode);
                
                if ($returnCode === 0) {
                    unlink($backupFile);
                    $backupFile = $encryptedFile;
                    $this->info('Sauvegarde chiffree');
                }
            }

            // Nettoyer anciennes sauvegardes (garder 30 jours)
            $this->cleanupOldBackups($backupDir, 30);

            $fileSize = round(filesize($backupFile) / 1024 / 1024, 2);
            
            Log::info("Sauvegarde reussie: $backupFile ($fileSize MB)");
            
            $this->info("Sauvegarde reussie: $backupFile ($fileSize MB)");
            
            return 0;

        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde: ' . $e->getMessage());
            $this->error('Erreur: ' . $e->getMessage());
            return 1;
        }
    }

    private function cleanupOldBackups(string $dir, int $days): void
    {
        $files = glob($dir . '/backup_*');
        $cutoff = now()->subDays($days)->timestamp;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $this->info("Ancienne sauvegarde supprimee: " . basename($file));
            }
        }
    }
}