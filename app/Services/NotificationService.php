<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Envoyer une notification à un utilisateur
     */
    public function envoyer($userId, $titre, $message, $type = 'systeme', $lien = null, $data = null)
    {
        return Notification::create([
            'utilisateur_id' => $userId,
            'titre' => $titre,
            'message' => $message,
            'type' => $type,
            'lien' => $lien,
            'data' => $data,
        ]);
    }

    /**
     * Envoyer à tous les admins
     */
    public function envoyerAuxAdmins($titre, $message, $type = 'systeme', $lien = null)
    {
        $admins = User::admins()->get();

        foreach ($admins as $admin) {
            $this->envoyer($admin->id, $titre, $message, $type, $lien);
        }

        return count($admins);
    }

    /**
     * Envoyer à tous les clients
     */
    public function envoyerAuxClients($titre, $message, $type = 'promo', $lien = null)
    {
        $clients = User::clients()->actifs()->get();

        foreach ($clients as $client) {
            $this->envoyer($client->id, $titre, $message, $type, $lien);
        }

        return count($clients);
    }

    /**
     * Marquer comme lu
     */
    public function marquerCommeLu($notificationId, $userId)
    {
        return Notification::where('id', $notificationId)
            ->where('utilisateur_id', $userId)
            ->update(['est_lu' => true]);
    }

    /**
     * Tout marquer comme lu
     */
    public function toutMarquerCommeLu($userId)
    {
        return Notification::where('utilisateur_id', $userId)
            ->where('est_lu', false)
            ->update(['est_lu' => true]);
    }
}
