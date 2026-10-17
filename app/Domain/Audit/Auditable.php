<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Records who created, changed or deleted a model, with the before/after values of changed attributes.
 *
 * Models may define `protected array $auditExclude` to skip noisy or sensitive attributes.
 *
 * @mixin Model
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => AuditLogger::record($model, 'created', [], $model->getAttributes()));

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();
            $original = array_intersect_key($model->getPrevious(), $changes);

            AuditLogger::record($model, 'updated', $original, $changes);
        });

        static::deleted(fn (Model $model) => AuditLogger::record($model, 'deleted', $model->getAttributes(), []));
    }

    /**
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest('id');
    }

    /**
     * @return list<string>
     */
    public function auditExclusions(): array
    {
        return [
            'created_at',
            'updated_at',
            'password',
            'remember_token',
            ...(property_exists($this, 'auditExclude') ? $this->auditExclude : []),
        ];
    }
}
