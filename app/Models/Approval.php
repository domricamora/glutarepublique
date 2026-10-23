<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Something only the owner may grant. Staff ask; the owner approves or
 * rejects; approving carries the action out. Nothing is edited after the
 * decision, so the row stays the record of what was asked and why.
 *
 * @property string $type
 * @property string $status
 * @property array<string, mixed>|null $payload
 */
class Approval extends Model
{
    use BelongsToOrganization;

    public const TYPES = [
        'refund' => 'Refund',
        'discount' => 'Discount over the limit',
        'payroll' => 'Payroll run',
        'leave' => 'Leave',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'amount' => 'float', 'decided_at' => 'datetime'];
    }

    /** @param  Builder<Approval>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Asks for something, and writes the asking to the audit log.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function ask(string $type, string $summary, User $by, ?Model $subject = null, ?float $amount = null, array $payload = []): self
    {
        $approval = static::create([
            'type' => $type,
            'summary' => $summary,
            'requested_by' => $by->id,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'amount' => $amount,
            'payload' => $payload ?: null,
        ]);

        AuditLog::record('approval.request', "{$by->name} asked for approval: {$summary}", $approval, ['type' => $type, 'amount' => $amount]);

        return $approval;
    }
}
