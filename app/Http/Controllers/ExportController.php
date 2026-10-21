<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Billing;
use App\Models\Link;
use App\Models\ServiceRequest;
use App\Models\SurveyRequest;
use App\Models\Ticket;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    // ─── CSV Exports ───────────────────────────────────────────────────────────

    public function surveysCsv(Request $request)
    {
        $user = Auth::user();

        $query = SurveyRequest::with(['customer', 'reseller', 'feedback']);

        if ($user->isReseller()) {
            $query->where('reseller_id', $user->reseller?->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $surveys = $query->latest()->get();

        $filename = 'surveys_export_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = [
            'Request No', 'Status', 'Reseller', 'Customer', 'District', 'Thana',
            'Service Type', 'Bandwidth (Mbps)', 'Provider', 'Feasible',
            'Installation Cost', 'Monthly Cost', 'Requested At',
        ];

        $callback = function () use ($surveys, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($surveys as $s) {
                fputcsv($handle, [
                    $s->request_no,
                    $s->status,
                    $s->reseller->company_name ?? 'N/A',
                    $s->customer->name ?? 'N/A',
                    $s->customer->district ?? '',
                    $s->customer->thana ?? '',
                    $s->service_type,
                    $s->bandwidth_mbps,
                    $s->feedback->provider ?? '',
                    isset($s->feedback) ? ($s->feedback->feasible ? 'Yes' : 'No') : '',
                    $s->feedback->installation_cost ?? '',
                    $s->feedback->monthly_cost ?? '',
                    $s->requested_at?->format('Y-m-d H:i') ?? $s->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function linksCsv(Request $request)
    {
        $user = Auth::user();

        $query = Link::with(['customer', 'reseller', 'billing']);

        if ($user->isReseller()) {
            $query->where('reseller_id', $user->reseller?->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $links = $query->latest()->get();

        $filename = 'circuits_export_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($links) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Link ID', 'Circuit ID', 'Status', 'Reseller', 'Customer',
                'Bandwidth (Mbps)', 'NTTN Provider', 'POP Location',
                'Invoice No', 'Monthly Charge', 'Activated At',
            ]);

            foreach ($links as $l) {
                fputcsv($handle, [
                    $l->link_id,
                    $l->circuit_id ?? '',
                    $l->status,
                    $l->reseller->company_name ?? 'N/A',
                    $l->customer->name ?? 'N/A',
                    $l->bandwidth_mbps,
                    $l->nttn_provider ?? '',
                    $l->pop_location ?? '',
                    $l->billing->invoice_no ?? '',
                    $l->billing->monthly_charge ?? '',
                    $l->activated_at?->format('Y-m-d') ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function ticketsCsv(Request $request)
    {
        $user = Auth::user();

        $query = Ticket::with(['link.customer', 'link.reseller', 'creator', 'assignedEngineer']);

        if ($user->isReseller()) {
            $query->where('created_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->latest()->get();

        $filename = 'tickets_export_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tickets) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Ticket No', 'Status', 'Priority', 'Type',
                'Link ID', 'Customer', 'Reseller',
                'Created By', 'Assigned To',
                'Created At', 'Resolved At', 'Closed At',
            ]);

            foreach ($tickets as $t) {
                fputcsv($handle, [
                    $t->ticket_no,
                    $t->status,
                    $t->priority,
                    $t->type,
                    $t->link->link_id ?? '',
                    $t->link->customer->name ?? '',
                    $t->link->reseller->company_name ?? '',
                    $t->creator->name ?? '',
                    $t->assignedEngineer->name ?? '',
                    $t->created_at->format('Y-m-d H:i'),
                    $t->resolved_at?->format('Y-m-d H:i') ?? '',
                    $t->closed_at?->format('Y-m-d H:i') ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function activityLogsCsv(Request $request)
    {
        $user = Auth::user();
        $query = ActivityLog::with('user');

        if ($user->isReseller()) {
            $resellerId = $user->reseller?->id ?? 0;
            $query->where(function ($q) use ($resellerId, $user) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($sub) use ($resellerId) {
                        $sub->where('entity_type', 'survey_request')
                            ->whereIn('entity_id', SurveyRequest::where('reseller_id', $resellerId)->select('id'));
                    })
                    ->orWhere(function ($sub) use ($resellerId) {
                        $sub->where('entity_type', 'work_order')
                            ->whereIn('entity_id', WorkOrder::whereHas('surveyRequest', fn ($sq) => $sq->where('reseller_id', $resellerId))->select('id'));
                    })
                    ->orWhere(function ($sub) use ($resellerId) {
                        $sub->where('entity_type', 'link')
                            ->whereIn('entity_id', Link::where('reseller_id', $resellerId)->select('id'));
                    })
                    ->orWhere(function ($sub) use ($resellerId) {
                        $sub->where('entity_type', 'ticket')
                            ->whereIn('entity_id', Ticket::whereHas('link', fn ($lq) => $lq->where('reseller_id', $resellerId))->select('id'));
                    })
                    ->orWhere(function ($sub) use ($resellerId) {
                        $sub->where('entity_type', 'service_request')
                            ->whereIn('entity_id', ServiceRequest::whereHas('link', fn ($lq) => $lq->where('reseller_id', $resellerId))->select('id'));
                    });
            });
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        $logs = $query->latest('created_at')->get();

        $filename = 'audit_log_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Timestamp', 'Action', 'Entity Type', 'Entity ID',
                'From Status', 'To Status', 'Actor', 'Remarks',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->action,
                    $log->entity_type,
                    $log->entity_id,
                    $log->from_status ?? '',
                    $log->to_status ?? '',
                    $log->user->name ?? 'System',
                    $log->remarks ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─── PDF Exports ───────────────────────────────────────────────────────────

    public function surveyPdf(SurveyRequest $survey)
    {
        $user = Auth::user();

        if ($user->isReseller() && $survey->reseller_id !== $user->reseller?->id) {
            abort(403);
        }

        $survey->load([
            'customer', 'reseller.user', 'feedback',
            'workOrder.link.billing', 'workOrder.nocHandover.test',
        ]);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('exports.survey_pdf', compact('survey'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("survey_{$survey->request_no}.pdf");
    }

    public function billingInvoicePdf(Billing $billing)
    {
        $billing->load(['link.customer', 'link.reseller', 'completedByUser']);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('exports.invoice_pdf', compact('billing'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("invoice_{$billing->invoice_no}.pdf");
    }
}
