<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's planned shift on one day, or their rest day. An end time
 * earlier than the start is a night shift that ends the next morning.
 *
 * @property int $employee_id
 * @property int|null $branch_id
 * @property \Carbon\CarbonImmutable $work_date
 * @property string|null $starts_at "HH:MM:SS"
 * @property string|null $ends_at
 * @property bool $is_rest_day
 */
class WorkSchedule extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['work_date' => 'date:Y-m-d', 'is_rest_day' => 'boolean'];
    }

    /** Planned minutes, counting a shift that runs past midnight. */
    public function minutes(): int
    {
        if ($this->is_rest_day || ! $this->starts_at || ! $this->ends_at) {
            return 0;
        }
        $start = (int) strtotime($this->starts_at);
        $end = (int) strtotime($this->ends_at);

        return intdiv(($end <= $start ? $end + 86400 : $end) - $start, 60);
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
