<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_FULL_DAY = 'full_day';
    public const TYPE_HALF_DAY = 'half_day';

    public const SESSION_FIRST_HALF = 'first_half';
    public const SESSION_SECOND_HALF = 'second_half';

    protected $fillable = [
        'user_id',
        'created_by',
        'leave_date',
        'leave_type',
        'half_day_session',
        'reason',
        'document_path',
        'document_name',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'leave_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return $this->leave_type === self::TYPE_HALF_DAY ? 'Half Day' : 'Full Day';
    }

    public function getHalfDaySessionLabelAttribute(): ?string
    {
        return match ($this->half_day_session) {
            self::SESSION_FIRST_HALF => 'First Half',
            self::SESSION_SECOND_HALF => 'Second Half',
            default => null,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            default => 'badge-warning',
        };
    }

    public function scopeForUserDate($query, int $userId, string $date, ?int $ignoreId = null)
    {
        return $query
            ->where('user_id', $userId)
            ->whereDate('leave_date', $date)
            ->where('status', '!=', self::STATUS_REJECTED)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId));
    }

    public function isRejectable(?CarbonInterface $today = null): bool
    {
        $today = $today ?: now('Asia/Kolkata');

        return $this->leave_date?->toDateString() > $today->toDateString();
    }
}
