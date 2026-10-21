<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SurveyRequest extends Model
{
    public const STATUS_SURVEY_REQUESTED = 'SURVEY_REQUESTED';

    public const STATUS_SURVEY_PROCESSING = 'SURVEY_PROCESSING';

    public const STATUS_FEEDBACK_READY = 'FEEDBACK_READY';

    public const STATUS_MD_APPROVAL_PENDING = 'MD_APPROVAL_PENDING';

    public const STATUS_MD_APPROVED = 'MD_APPROVED';

    public const STATUS_MD_REJECTED = 'MD_REJECTED';

    public const STATUS_SENT_BACK = 'SENT_BACK';

    public const STATUS_NOT_FEASIBLE = 'NOT_FEASIBLE';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_WORK_ORDER_ISSUED = 'WORK_ORDER_ISSUED';

    public const STATUS_WORK_IN_PROGRESS = 'WORK_IN_PROGRESS';

    public const STATUS_NOC_HANDOVER = 'NOC_HANDOVER';

    public const STATUS_NOC_TESTING = 'NOC_TESTING';

    public const STATUS_NOC_VERIFIED = 'NOC_VERIFIED';

    public const STATUS_BILLING_PENDING = 'BILLING_PENDING';

    public const STATUS_BILLING_COMPLETED = 'BILLING_COMPLETED';

    public const STATUS_FINAL_MD_APPROVAL = 'FINAL_MD_APPROVAL';

    public const STATUS_FINAL_APPROVED = 'FINAL_APPROVED';

    public const STATUS_ACTIVE = 'ACTIVE';

    protected $fillable = [
        'request_no',
        'reseller_id',
        'customer_id',
        'bandwidth_mbps',
        'service_type',
        'preferred_nttn',
        'description',
        'status',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'bandwidth_mbps' => 'integer',
            'requested_at' => 'datetime',
        ];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(SurveyFeedback::class)->orderByRaw('is_selected DESC, feasible DESC, id ASC');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(SurveyFeedback::class)->orderBy('id', 'asc');
    }

    public function feasibleFeedbacks(): HasMany
    {
        return $this->hasMany(SurveyFeedback::class)->where('feasible', true)->orderBy('id', 'asc');
    }

    public function selectedFeedback(): HasOne
    {
        return $this->hasOne(SurveyFeedback::class)->where('is_selected', true);
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'entity_id')
            ->where('entity_type', 'survey_request');
    }

    public function isNotFeasible(): bool
    {
        return $this->status === self::STATUS_NOT_FEASIBLE;
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return in_array($this->status, [self::STATUS_NOT_FEASIBLE, self::STATUS_SURVEY_REQUESTED]);
        }

        if ($user->isReseller() && $user->reseller?->id === $this->reseller_id) {
            return in_array($this->status, [self::STATUS_NOT_FEASIBLE, self::STATUS_SURVEY_REQUESTED]);
        }

        return false;
    }

    /**
     * Delete expired non-feasible, rejected, or cancelled survey requests,
     * terminated/rejected links, and rejected service requests updated more than $days days ago.
     *
     * @return array{deleted_surveys: int, deleted_links: int, deleted_service_requests: int}
     */
    public static function pruneExpiredRecords(int $days = 30): array
    {
        $cutoff = Carbon::now()->subDays($days);

        $deletedSurveysCount = 0;
        $deletedLinksCount = 0;
        $deletedServiceRequestsCount = 0;

        DB::transaction(function () use ($cutoff, &$deletedSurveysCount, &$deletedLinksCount, &$deletedServiceRequestsCount) {
            // 1. Identify Surveys to delete: NOT_FEASIBLE, MD_REJECTED, CANCELLED that have been updated_at <= $cutoff
            $expiredSurveyIds = self::whereIn('status', [
                self::STATUS_NOT_FEASIBLE,
                self::STATUS_MD_REJECTED,
                self::STATUS_CANCELLED,
            ])
                ->where('updated_at', '<=', $cutoff)
                ->pluck('id')
                ->all();

            if (! empty($expiredSurveyIds)) {
                Approval::where('entity_type', 'survey_request')
                    ->whereIn('entity_id', $expiredSurveyIds)
                    ->delete();

                ActivityLog::where('entity_type', 'survey_request')
                    ->whereIn('entity_id', $expiredSurveyIds)
                    ->delete();

                $deletedSurveysCount = self::whereIn('id', $expiredSurveyIds)->delete();
            }

            // 2. Identify Terminated/Rejected links that have been updated_at <= $cutoff
            $expiredLinkIds = Link::whereIn('status', [
                Link::STATUS_TERMINATED,
                'REJECTED',
            ])
                ->where('updated_at', '<=', $cutoff)
                ->pluck('id')
                ->all();

            if (! empty($expiredLinkIds)) {
                Approval::where('entity_type', 'link')
                    ->whereIn('entity_id', $expiredLinkIds)
                    ->delete();

                ActivityLog::where('entity_type', 'link')
                    ->whereIn('entity_id', $expiredLinkIds)
                    ->delete();

                $deletedLinksCount = Link::whereIn('id', $expiredLinkIds)->delete();
            }

            // 3. Identify Rejected service requests updated_at <= $cutoff
            $expiredServiceReqIds = ServiceRequest::whereIn('status', [
                ServiceRequest::STATUS_REJECTED,
            ])
                ->where('updated_at', '<=', $cutoff)
                ->pluck('id')
                ->all();

            if (! empty($expiredServiceReqIds)) {
                ActivityLog::where('entity_type', 'service_request')
                    ->whereIn('entity_id', $expiredServiceReqIds)
                    ->delete();

                $deletedServiceRequestsCount = ServiceRequest::whereIn('id', $expiredServiceReqIds)->delete();
            }
        });

        return [
            'deleted_surveys' => $deletedSurveysCount,
            'deleted_links' => $deletedLinksCount,
            'deleted_service_requests' => $deletedServiceRequestsCount,
        ];
    }
}
