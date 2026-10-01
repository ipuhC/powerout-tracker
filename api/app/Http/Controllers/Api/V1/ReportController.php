<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OutageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use App\Http\Resources\ReportResource;
use App\Http\Responses\ApiResponse;
use App\Models\OutageReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $reports = $request->user()->outageReports()
            ->withCoordinates()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return $this->successResponse([
            'reports' => ReportResource::collection($reports),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
            ]
        ]);
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $user = $request->user();

        // Enforce quota (configurable via OUTAGE_MAX_ACTIVE_REPORTS env var)
        $maxActive = (int) env('OUTAGE_MAX_ACTIVE_REPORTS', 3);
        if ($user->activeReportsCount() >= $maxActive) {
            return $this->errorResponse(
                'QUOTA_EXCEEDED',
                "You can only have up to {$maxActive} active reports at a time.",
                null,
                422
            );
        }

        $report = new OutageReport();
        $report->user_id = $user->id;
        $report->setCoordinates($request->latitude, $request->longitude);
        $report->radius_km = $request->radius_km;
        $report->duration_type = $request->duration_type;
        $report->duration_hours = $request->duration_hours;
        $report->description = $request->description;
        $report->status = OutageStatus::ACTIVE;
        
        $report->save();
        $report->refresh(); // Refresh to ensure spatial properties and calculated attributes are loaded correctly

        return $this->successResponse([
            'report' => new ReportResource($report)
        ], 201);
    }

    public function update(UpdateReportRequest $request, OutageReport $report): JsonResponse
    {
        $this->authorize('update', $report);

        if ($report->status !== OutageStatus::ACTIVE) {
            return $this->errorResponse('INVALID_STATE', 'Only active reports can be updated.', null, 422);
        }

        if ($request->has('radius_km')) {
            $report->radius_km = $request->radius_km;
        }

        if ($request->has('duration_type')) {
            $report->duration_type = $request->duration_type;
            $report->duration_hours = $request->duration_hours;

            // Use $request->duration_type (raw string) — $report->duration_type is already
            // cast to a DurationType enum by Eloquent and cannot be passed to ::from() again.
            $report->expires_at = OutageReport::calculateExpiresAt(
                \App\Enums\DurationType::from($request->duration_type),
                $request->duration_hours,
                $report->created_at
            );
        }

        if ($request->has('description')) {
            $report->description = $request->description;
        }

        $report->save();
        $report->refresh();

        return $this->successResponse([
            'report' => new ReportResource($report)
        ]);
    }

    public function close(Request $request, OutageReport $report): JsonResponse
    {
        $this->authorize('close', $report);

        if ($report->status !== OutageStatus::ACTIVE) {
            return $this->errorResponse('INVALID_STATE', 'Report is already closed or expired.', null, 422);
        }

        $report->status = OutageStatus::CLOSED;
        $report->closed_at = now();
        $report->save();
        $report->refresh();

        return $this->successResponse([
            'report' => new ReportResource($report)
        ]);
    }
}
