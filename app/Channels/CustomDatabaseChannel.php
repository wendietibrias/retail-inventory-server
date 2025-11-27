<?php

namespace App\Channels;

use Notification;

class CustomDatabaseChannel {
    public function send($notifiable, Notification $notification)
    {
        // Ambil data standard dari toDatabase / toArray
        $data = $notification->toDatabase($notifiable);

        // Simpan manual ke database menggunakan Eloquent atau Query Builder
        return $notifiable->routeNotificationFor('database')->create([
            'id' => $notification->id,
            'type' => get_class($notification),
            'data' => $data, // Data sisa tetap masuk JSON
            'read_at' => null,
            
            // ISI KOLOM CUSTOM DISINI
            // Kita ambil dari property public di class Notification
            'sender_id' => $notification->sender_id ?? null,
            'priority' => $notification->priority ?? 'normal',
        ]);
    } 
}