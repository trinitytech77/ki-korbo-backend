<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateServiceAction;
use App\Actions\UpdateServiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display a listing of services with categories and fee breakdown.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->with(['category', 'fees']);

        // Filter by category (id or slug)
        if ($request->filled('category')) {
            $cat = $request->query('category');
            $query->whereHas('category', function ($q) use ($cat) {
                $q->where('id', $cat)->orWhere('slug', $cat);
            });
        }

        // Search by keyword
        if ($request->filled('search') || $request->filled('q')) {
            $term = $request->query('search') ?? $request->query('q');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Active status filtering (public defaults to active only)
        $isStaff = $request->user() && $request->user()->isStaff();
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        } elseif (!$isStaff) {
            $query->where('is_active', true);
        }

        // Optionally eager load requirements and steps
        if ($request->boolean('with_details')) {
            $query->with(['requirements', 'steps']);
        }

        $perPage = (int) $request->query('per_page', 25);
        $services = $perPage > 0 ? $query->paginate($perPage) : $query->get();

        return response()->json([
            'success' => true,
            'data' => ServiceResource::collection($services),
            'meta' => $perPage > 0 ? [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'total' => $services->total(),
            ] : null,
        ]);
    }

    /**
     * Display the specified service with complete requirements, steps and fees.
     *
     * @param Request $request
     * @param Service $service
     * @return JsonResponse
     */
    public function show(Request $request, Service $service): JsonResponse
    {
        $service->load(['category', 'requirements', 'steps', 'fees']);

        $this->authorize('view', $service);

        return response()->json([
            'success' => true,
            'data' => new ServiceResource($service),
        ]);
    }

    /**
     * Store a newly created database-driven service.
     *
     * @param StoreServiceRequest $request
     * @param CreateServiceAction $action
     * @return JsonResponse
     */
    public function store(StoreServiceRequest $request, CreateServiceAction $action): JsonResponse
    {
        $this->authorize('create', Service::class);

        $service = $action->execute($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully.',
            'data' => new ServiceResource($service),
        ], 201);
    }

    /**
     * Update the specified service and sync its requirements, steps and fees.
     *
     * @param UpdateServiceRequest $request
     * @param Service $service
     * @param UpdateServiceAction $action
     * @return JsonResponse
     */
    public function update(UpdateServiceRequest $request, Service $service, UpdateServiceAction $action): JsonResponse
    {
        $this->authorize('update', $service);

        $service = $action->execute($service, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully.',
            'data' => new ServiceResource($service),
        ]);
    }

    /**
     * Remove the specified service.
     *
     * @param Request $request
     * @param Service $service
     * @return JsonResponse
     */
    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->authorize('delete', $service);

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully.',
        ]);
    }
}
