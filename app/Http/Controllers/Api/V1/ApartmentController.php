<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Apartment;

class ApartmentController extends Controller
{
    public function index()
    {
        return response()->json(Apartment::with(['category', 'tenant'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:apartment_categories,id',
            'name' => 'required|string',
            'number_item' => 'required|integer|min:1',
            'location' => 'required|string',
            'address' => 'required|string',
            'tenant_id' => 'nullable|exists:users,id'
        ]);

        $apartment = Apartment::create($validated);
        return response()->json($apartment->load(['category', 'tenant']), 201);
    }

    public function show($id)
    {
        $apartment = Apartment::with(['category', 'tenant'])->findOrFail($id);
        return response()->json($apartment);
    }

    public function update(Request $request, $id)
    {
        $apartment = Apartment::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:apartment_categories,id',
            'name' => 'required|string',
            'number_item' => 'required|integer|min:1',
            'location' => 'required|string',
            'address' => 'required|string',
            'tenant_id' => 'nullable|exists:users,id'
        ]);

        $apartment->update($validated);
        return response()->json($apartment->load(['category', 'tenant']));
    }

    public function destroy($id)
    {
        Apartment::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
     public function CategoryIndex()
    {
        return response()->json(ApartmentCategory::all());
    }

    public function CategoryStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:apartment_categories,name',
            'description' => 'nullable|string'
        ]);

        $category = ApartmentCategory::create($validated);
        return response()->json($category, 201);
    }

    public function CategoryShow($id)
    {
        $category = ApartmentCategory::findOrFail($id);
        return response()->json($category);
    }

    public function CategoryUpdate(Request $request, $id)
    {
        $category = ApartmentCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:apartment_categories,name,' . $id,
            'description' => 'nullable|string'
        ]);

        $category->update($validated);
        return response()->json($category);
    }

    public function CategoryDestroy($id)
    {
        ApartmentCategory::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

}
