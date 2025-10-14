<?php

namespace App;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait LogActivity
{
    /**
     * Log activity secara sinkron. Tidak akan melempar exception ke caller.
     *
     * @param string $action
     * @param string|null $description
     * @param array $meta // optional additional data
     * @return void
     */
    public function logActivity(string $action, ?string $description = null, array $meta = []): void
    {
        try {
            $user = Auth::user();

            // ambil data request jika tersedia (cek helper request())
            $metaData = array_merge([
                'ip' => function_exists('request') && request()?->ip() ? request()->ip() : null,
                'user_agent' => function_exists('request') && request()?->userAgent() ? request()->userAgent() : null,
                'route' => function_exists('request') && request()?->route()?->getName() ? request()->route()->getName() : null,
            ], $meta);

            ActivityLog::create([
                'user_id'     => $user?->id,
                'role'        => $user?->role ?? 'system',
                'action'      => $action,
                'description' => $description,
                'meta'        => $metaData, // pastikan kolom meta json di migration/model
            ]);
        } catch (\Throwable $e) {
            // jangan melempar lagi — fallback: tulis ke log file supaya tidak kehilangan info saat debugging
            \Log::warning('Failed to write activity log: '.$e->getMessage(), [
                'action' => $action,
                'description' => $description,
                'exception' => $e,
            ]);
        }
    }
}
