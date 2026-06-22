<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class TestJWT extends Command
{
    protected $signature = 'jwt:test {email?}';
    protected $description = 'Tester la configuration JWT';

    public function handle()
    {
        $this->info('=== TEST JWT ===');
        $this->info('');

        // 1. Vérifier JWT_SECRET
        $secret = config('jwt.secret');
        if ($secret) {
            $this->info('✓ JWT_SECRET configuré (' . strlen($secret) . ' caractères)');
        } else {
            $this->error('✗ JWT_SECRET non configuré');
            $this->info('Exécutez: php artisan jwt:secret');
            return 1;
        }

        // 2. Vérifier TTL
        $ttl = config('jwt.ttl');
        $this->info('✓ TTL: ' . $ttl . ' minutes');

        // 3. Vérifier l'algorithme
        $algo = config('jwt.algo');
        $this->info('✓ Algorithme: ' . $algo);

        // 4. Tester avec un utilisateur
        $email = $this->argument('email');
        
        if (!$email) {
            $user = User::first();
            if (!$user) {
                $this->warn('Aucun utilisateur trouvé, création d un utilisateur test...');
                $user = User::create([
                    'nom' => 'Test User',
                    'email' => 'test@example.com',
                    'mot_de_passe_hash' => bcrypt('password'),
                    'role' => 'client',
                    'statut' => 'actif',
                ]);
            }
            $email = $user->email;
        } else {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error('Utilisateur non trouvé: ' . $email);
                return 1;
            }
        }

        $this->info('');
        $this->info('Utilisateur test: ' . $user->email);
        $this->info('ID: ' . $user->id);
        $this->info('Type de clé: ' . gettype($user->getKey()));
        $this->info('Valeur de clé: ' . $user->getKey());

        // 5. Générer un token
        try {
            $token = JWTAuth::fromUser($user);
            $this->info('');
            $this->info('✓ Token généré avec succès');
            $this->info('Token: ' . substr($token, 0, 50) . '...');

            // 6. Vérifier le token
            $payload = JWTAuth::getPayload($token);
            $this->info('');
            $this->info('Payload JWT:');
            $this->info('  sub (subject): ' . $payload->get('sub'));
            $this->info('  iat (issued at): ' . $payload->get('iat'));
            $this->info('  exp (expires): ' . $payload->get('exp'));
            $this->info('  role: ' . $payload->get('role'));

            $this->info('');
            $this->info('✅ JWT CONFIGURÉ CORRECTEMENT !');
            return 0;
        } catch (\Exception $e) {
            $this->error('');
            $this->error('✗ Erreur lors de la génération du token:');
            $this->error($e->getMessage());
            $this->error('');
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}