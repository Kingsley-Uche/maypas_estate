<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ApartmentCategory;
use App\Models\ApartmentLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class ApartmentController extends Controller
{
    /**
     * Display a listing of apartments for the authenticated user's estate manager.
     */
public function index(Request $request): JsonResponse
    {
        // Get the authenticated user's estate manager ID
        $estateManagerId = $request->user()->estate_manager_id;
        
        $categories = ApartmentCategory::whereHas('apartments', function ($query) use ($estateManagerId) {
            $query->where('estate_manager_id', $estateManagerId);
        })->with([
            'apartments' => function ($query) use ($estateManagerId) {
                $query->where('estate_manager_id', $estateManagerId)->with('apartmentAtLocation');
            }
        ])->get(['id', 'name', 'description']);

        return response()->json($categories);
    }

    /**
     * Store a new apartment.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:apartment_categories,id'],
            'number_item' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'string'],
            'address' => ['required', 'string'],
        ]);

        $estateManagerId = auth()->user()->estate_manager_id;

        return DB::transaction(function () use ($validated, $estateManagerId) {
            $apartment = Apartment::create([
                ...$validated,
                'estate_manager_id' => $estateManagerId
            ]);

            // Create apartment locations based on number_item
            $locations = [];
            for ($i = 0; $i < (int)$validated['number_item']; $i++) {
                $locations[] = [
                    'apartment_id' => $apartment->id,
                    'apartment_identifier' => 'None',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ApartmentLocation::insert($locations);

            return response()->json($apartment->load('category'), Response::HTTP_CREATED);
        });
    }

    /**
     * Display a specific apartment.
     */
public function show(Request $request, string $slug, int $id): JsonResponse
{
    $estateManagerId = auth()->user()->estate_manager_id;
    
   $apartmentLocation = ApartmentLocation::select(
    'apartment_locations.*', 
    'apartments.location',
    'apartments.address',
    'apartments.id as apartment_id',
    'apartments.category_id',
    'apartment_categories.id as category_id',
    'apartment_categories.name as category_name'
)
->join('apartments', 'apartments.id', '=', 'apartment_locations.apartment_id')
->join('apartment_categories', 'apartment_categories.id', '=', 'apartments.category_id') // Fixed typo: 'aparments' to 'apartments'
->where('apartment_locations.id', $id)
->where('apartments.estate_manager_id', $estateManagerId)
->first();

if (!$apartmentLocation) {
    return response()->json(
        ['message' => 'Apartment location not found'], 
        Response::HTTP_NOT_FOUND
    );
}

return response()->json($apartmentLocation);
}

    /**
     * Update an apartment.
     */
public function update(Request $request, string $slug, int $id): JsonResponse
{
    $estateManagerId = auth()->user()->estate_manager_id;
    
    $apartmentLocation = ApartmentLocation::where('id', $id)
        ->whereHas('apartment', function ($query) use ($estateManagerId) {
            $query->where('estate_manager_id', $estateManagerId);
        })
        ->first();

    if (!$apartmentLocation) {
        return response()->json(
            ['message' => 'Apartment location not found'], 
            Response::HTTP_NOT_FOUND
        );
    }

    $validated = $request->validate([
        'apartment_identifier' => ['sometimes', 'string', 'max:255'],

    ]);

    // Update only the provided fields for ApartmentLocation
    $apartmentLocation->update($validated);

    if ($request->hasAny(['category_id', 'location', 'address'])) {
        $apartmentValidated = $request->validate([
            'category_id' => ['sometimes', 'exists:apartment_categories,id'],
            'location' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:500'],
        ]);
        
        $apartmentLocation->apartment->update($apartmentValidated);
    }

    

    return response()->json(['status'=>true,'message'=>'updated successfully'],200);
}

    /**
     * Delete an apartment.
     */
    public function destroy(int $id): JsonResponse
    {
        $apartment = ApartmentLocation::find($id);

        if (!$apartment) {
            return response()->json(
                ['message' => 'Apartment not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        $apartment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Display a listing of apartment categories.
     */
    public function categoryIndex(): JsonResponse
    {
        $categories = ApartmentCategory::all();
        
        return response()->json($categories);
    }

    /**
     * Store a new apartment category.
     */
    public function categoryStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'unique:apartment_categories,name'],
            'description' => ['nullable', 'string']
        ]);

        $category = ApartmentCategory::create($validated);

        return response()->json($category, Response::HTTP_CREATED);
    }

    /**
     * Display a specific apartment category.
     */
    public function categoryShow(int $id): JsonResponse
    {
        $category = ApartmentCategory::find($id);

        if (!$category) {
            return response()->json(
                ['message' => 'Apartment category not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json($category);
    }

    /**
     * Update an apartment category.
     */
    public function categoryUpdate(Request $request, int $id): JsonResponse
    {
        $category = ApartmentCategory::find($id);

        if (!$category) {
            return response()->json(
                ['message' => 'Apartment category not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                Rule::unique('apartment_categories', 'name')->ignore($id)
            ],
            'description' => ['nullable', 'string']
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Delete an apartment category.
     */
    public function categoryDestroy(int $id): JsonResponse
    {
        $category =  ApartmentCategory::find($id);

        if (!$category) {
            return response()->json(
                ['message' => 'Apartment category not found'], 
                Response::HTTP_NOT_FOUND
            );
        }

        $category->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}