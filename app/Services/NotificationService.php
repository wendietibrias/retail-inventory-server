<?php 

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class NotificationService {
    public function notifyRole(Role $role, $notificationInstance): void
    {
        // Query User berdasarkan Role (menggunakan relasi di model User)
        $users = User::whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role->value);
        })->get();

        $this->send($users, $notificationInstance);
    }

    private function send(Collection $users, $notificationInstance): void
    {
        if ($users->isEmpty()) {
            return;
        }

        // Facade Notification otomatis menangani chunking jika user sangat banyak
        Notification::send($users, $notificationInstance);
    }
}