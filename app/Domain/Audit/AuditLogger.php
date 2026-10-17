<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public static function record(Model $model, string $event, array $old, array $new): void
    {
        $excluded = method_exists($model, 'auditExclusions') ? $model->auditExclusions() : [];

        $old = Arr::except($old, $excluded);
        $new = Arr::except($new, $excluded);

        if ($event === 'updated' && $new === []) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => app()->runningInConsole() ? null : Request::ip(),
        ]);
    }
}
