<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\PublicOutageIndexRequest;
use App\Http\Resources\PublicOutageResource;
use App\Http\Responses\ApiResponse;
use App\Models\OutageReport;
use Illuminate\Http\JsonResponse;

class PublicOutageController extends Controller
{
    use ApiResponse;

    public function index(PublicOutageIndexRequest $request): JsonResponse
    {
        $outages = OutageReport::active()
            ->withCoordinates()
            ->withinBoundingBox(
                $request->min_lat,
                $request->min_lng,
                $request->max_lat,
                $request->max_lng
            )
            ->get();

        return $this->successResponse([
            'outages' => PublicOutageResource::collection($outages)
        ]);
    }
}
