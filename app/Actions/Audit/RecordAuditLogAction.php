<?php

namespace App\Actions\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RecordAuditLogAction
{
    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public function execute(
        string $action,
        ?User $actor = null,
        Model|array|null $target = null,
        ?array $before = null,
        ?array $after = null,
        ?Request $request = null,
    ): AuditLog {
        [$targetType, $targetId] = $this->resolveTarget($target);

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request?->ip(),
            'request_id' => $request?->attributes->get('request_id') ?: $request?->header('X-Request-Id'),
        ]);
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    protected function resolveTarget(Model|array|null $target): array
    {
        if ($target instanceof Model) {
            return [$target->getMorphClass(), $target->getKey() ? (int) $target->getKey() : null];
        }

        if (is_array($target)) {
            return [
                isset($target['type']) ? (string) $target['type'] : null,
                isset($target['id']) ? (int) $target['id'] : null,
            ];
        }

        return [null, null];
    }
}
