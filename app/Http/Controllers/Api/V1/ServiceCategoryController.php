<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceCategoryRequest;
use App\Http\Requests\UpdateServiceCategoryRequest;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    /**
     * Display a listing of service categories.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = ServiceCategory::query()->withCount('services');

        $isStaff = $request->user() && $request->user()->isStaff();
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        } elseif (!$isStaff) {
            $query->where('is_active', true);
        }

        if ($request->boolean('with_services')) {
            $query->with(['services' => function ($q) {
                $q->where('is_active', true)->with('fees');
            }]);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => ServiceCategoryResource::collection($categories),
        ]);
    }

    /**
     * Display the specified category with active services.
     *
     * @param Request $request
     * @param ServiceCategory $category
     * @return JsonResponse
     */
    public function show(Request $request, ServiceCategory $category): JsonResponse
    {
        $category->load(['services' => function ($q) {
            $q->where('is_active', true)->with('fees');
        }]);

        return response()->json([
            'success' => true,
            'data' => new ServiceCategoryResource($category),
        ]);
    }

    /**
     * Store a newly created service category.
     *
     * @param StoreServiceCategoryRequest $request
     * @return JsonResponse
     */
    public function store(StoreServiceCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', ServiceCategory::class);

        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category = ServiceCategory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Service category created successfully.',
            'data' => new ServiceCategoryResource($category),
        ], 201);
    }

    /**
     * Update the specified service category.
     *
     * @param UpdateServiceCategoryRequest $request
     * @param ServiceCategory $category
     * @return JsonResponse
     */
    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $category): JsonResponse
    {
        $this->authorize('update', $category);

        $data = $request->validated();
        if (!empty($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Service category updated successfully.',
            'data' => new ServiceCategoryResource($category),
        ]);
    }

    /**
     * Remove the specified category.
     *
     * @param Request $request
     * @param ServiceCategory $category
     * @return JsonResponse
     */
    public function destroy(Request $request, ServiceCategory $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service category deleted successfully.',
        ]);
    }
}
