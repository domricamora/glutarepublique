<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A CRM lead: someone who contacted or booked through the website. Not a
 * patient; clinical data never lives here (plan.md §15).
 *
 * @property CarbonImmutable|null $privacy_consent_at
 */
class Lead extends Model
{
    use BelongsToOrganization;

    public const STAGES = [
        'new' => 'New lead',
        'contacted' => 'Contacted',
        'consultation_booked' => 'Consultation booked',
        'consultation_completed' => 'Consultation completed',
        'treatment_recommended' => 'Treatment recommended',
        'treatment_booked' => 'Treatment booked',
        'customer' => 'Customer',
        'repeat_customer' => 'Repeat customer',
        'vip' => 'VIP',
    ];

    /** plan.md §13 lead sources. */
    public const SOURCES = [
        'website' => 'Website', 'chat' => 'Website chat',
        'facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok',
        'google' => 'Google', 'referral' => 'Referral', 'walk_in' => 'Walk-in', 'phone' => 'Phone',
        'email' => 'Email', 'campaign' => 'Campaign', 'partner' => 'Partner',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['privacy_consent_at' => 'datetime', 'marketing_consent' => 'boolean'];
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** @return BelongsTo<Treatment, $this> */
    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return HasMany<CrmActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class)->latest();
    }

    /**
     * What a webhook receives about a client. Contacts are included: only the
     * owner can point a webhook anywhere (clients.contacts).
     *
     * @return array<string, mixed>
     */
    public function webhookPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->fullName(),
            'phone' => $this->phone,
            'email' => $this->email,
            'source' => $this->source,
            'form' => $this->form,
            'stage' => $this->stage,
            'treatment' => $this->treatment?->name,
            'branch' => $this->branch?->name,
            'message' => $this->message,
        ];
    }
}
