<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;

class NotificationService
{
    /**
     * Send a notification to specific users.
     */
    public static function notify(array $userIds, string $title, string $message, ?string $entityType = null, ?int $entityId = null): void
    {
        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);
        }
    }

    /**
     * Notify all users of a given role.
     */
    public static function notifyRole(string $roleName, string $title, string $message, ?string $entityType = null, ?int $entityId = null): void
    {
        $userIds = User::whereHas('role', fn ($q) => $q->where('name', $roleName))
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        static::notify($userIds, $title, $message, $entityType, $entityId);
    }

    /**
     * Notify multiple roles.
     */
    public static function notifyRoles(array $roleNames, string $title, string $message, ?string $entityType = null, ?int $entityId = null): void
    {
        $userIds = User::whereHas('role', fn ($q) => $q->whereIn('name', $roleNames))
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        static::notify($userIds, $title, $message, $entityType, $entityId);
    }

    // ─── Pre-built event helpers ───────────────────────────────────────────────

    public static function surveyRequested(int $surveyId, string $requestNo, string $customerName): void
    {
        static::notifyRole(Role::TRANSMISSION,
            "New Survey Request: {$requestNo}",
            "A new feasibility survey has been lodged for customer {$customerName}. Please review and begin field processing.",
            'survey_request', $surveyId
        );
    }

    public static function surveyReadyForApproval(int $surveyId, string $requestNo): void
    {
        static::notifyRole(Role::MD,
            "Feasibility Report Ready — {$requestNo}",
            "Transmission team has completed feasibility analysis for {$requestNo}. Awaiting your approval decision.",
            'survey_request', $surveyId
        );
    }

    public static function surveyApproved(int $surveyId, string $requestNo, int $resellerId): void
    {
        $resellerUser = User::whereHas('reseller', fn ($q) => $q->where('id', $resellerId))->first();
        if ($resellerUser) {
            static::notify([$resellerUser->id],
                "Survey Approved ✓ — {$requestNo}",
                "Your survey request {$requestNo} has been approved by MD. A work order is being issued to the transmission team.",
                'survey_request', $surveyId
            );
        }
        static::notifyRole(Role::TRANSMISSION,
            "Work Order Issued — {$requestNo}",
            "MD has approved survey {$requestNo}. Please proceed with field installation.",
            'survey_request', $surveyId
        );
    }

    public static function surveyRejected(int $surveyId, string $requestNo, int $resellerId): void
    {
        $resellerUser = User::whereHas('reseller', fn ($q) => $q->where('id', $resellerId))->first();
        if ($resellerUser) {
            static::notify([$resellerUser->id],
                "Survey Rejected — {$requestNo}",
                "Unfortunately, your survey request {$requestNo} was rejected by MD. Please contact your account manager.",
                'survey_request', $surveyId
            );
        }
    }

    public static function surveyNotFeasible(int $surveyId, string $requestNo, int $resellerId): void
    {
        $resellerUser = User::whereHas('reseller', fn ($q) => $q->where('id', $resellerId))->first();
        if ($resellerUser) {
            static::notify([$resellerUser->id],
                "Survey Route Infeasible ⚠️ — {$requestNo}",
                "Transmission team has assessed survey request {$requestNo} as Not Feasible. You can edit and resubmit your parameters or cancel the request.",
                'survey_request', $surveyId
            );
        }
    }

    public static function nocHandoverReceived(int $handoverId, string $requestNo): void
    {
        static::notifyRole(Role::NOC,
            "NOC Handover: {$requestNo}",
            "Work order for {$requestNo} has been handed over to NOC for final circuit testing and acceptance.",
            'noc_handover', $handoverId
        );
    }

    public static function linkActivated(int $linkId, string $linkCode, int $resellerId): void
    {
        $resellerUser = User::whereHas('reseller', fn ($q) => $q->where('id', $resellerId))->first();
        if ($resellerUser) {
            static::notify([$resellerUser->id],
                "Circuit Activated 🟢 — {$linkCode}",
                "Your circuit {$linkCode} is now LIVE. NOC testing passed and the link has been commissioned.",
                'link', $linkId
            );
        }
        static::notifyRole(Role::BILLING,
            "Link Ready for Billing — {$linkCode}",
            "Circuit {$linkCode} has been activated. Please process commercial invoicing.",
            'link', $linkId
        );
    }

    public static function ticketCreated(int $ticketId, string $ticketNo, string $linkCode): void
    {
        static::notifyRoles([Role::NOC, Role::TRANSMISSION],
            "Trouble Ticket: {$ticketNo}",
            "A new trouble ticket has been raised for circuit {$linkCode}. Please investigate.",
            'ticket', $ticketId
        );
    }

    public static function ticketResolved(int $ticketId, string $ticketNo, int $createdBy): void
    {
        static::notify([$createdBy],
            "Ticket Resolved ✓ — {$ticketNo}",
            "Your trouble ticket {$ticketNo} has been resolved by the operations team.",
            'ticket', $ticketId
        );
    }
}
