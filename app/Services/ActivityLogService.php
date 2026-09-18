<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogService
{
    private const SENSITIVE = ['password', 'password_confirmation', 'token', 'access_token', 'refresh_token', 'secret'];

    public function write(string $action, string $module, ?Model $subject = null, array $old = [], array $new = [], ?Request $request = null): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old_values' => $this->sanitize($old) ?: null,
            'new_values' => $this->sanitize($new) ?: null,
            'ip_address' => $request?->ip() ?? request()?->ip(),
            'user_agent' => mb_substr((string) ($request?->userAgent() ?? request()?->userAgent()), 0, 1000),
            'created_at' => now(),
        ]);
    }

    private function sanitize(array $values): array
    {
        return collect($values)->except(self::SENSITIVE)->all();
    }
}
