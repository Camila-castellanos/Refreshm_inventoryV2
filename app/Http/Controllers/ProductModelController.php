<?php

namespace App\Http\Controllers;

use App\Models\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductModelController extends Controller
{
    private function ensureAdmin()
    {
        if (! Auth::user() || ! in_array(Auth::user()->role, ['ADMIN', 'OWNER'])) {
            abort(403, 'Only admins or owners can manage models.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();

        $search = $request->input('search');
        $type = $request->input('type');

        $query = ProductModel::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        $models = $query->with('items')
            ->withCount('items')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('ProductModels/Index', [
            'models' => $models,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
        ]);
    }

    public function create()
    {
        $this->ensureAdmin();

        return Inertia::render('ProductModels/Create');
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'colours' => 'nullable|array',
            'colours.*' => 'string|max:255',
            'capacities' => 'nullable|array',
            'capacities.*' => 'string|max:255',
            'description' => 'nullable|string',
        ]);

        $model = ProductModel::create([
            'name' => $request->name,
            'manufacturer' => $request->manufacturer,
            'type' => $request->type,
            'colours' => $request->colours,
            'capacities' => $request->capacities,
            'description' => $request->description,
        ]);

        return redirect()->route('product-models.edit', $model)
            ->with('success', 'Model created successfully.');
    }

    public function show(ProductModel $productModel)
    {
        return redirect()->route('product-models.edit', $productModel->id);
    }

    public function edit(ProductModel $productModel)
    {
        $this->ensureAdmin();

        return Inertia::render('ProductModels/Edit', [
            'model' => $productModel,
        ]);
    }

    public function update(Request $request, ProductModel $productModel)
    {
        $this->ensureAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'colours' => 'nullable|array',
            'colours.*' => 'string|max:255',
            'capacities' => 'nullable|array',
            'capacities.*' => 'string|max:255',
            'description' => 'nullable|string',
        ]);

        $productModel->update([
            'name' => $request->name,
            'manufacturer' => $request->manufacturer,
            'type' => $request->type,
            'colours' => $request->colours,
            'capacities' => $request->capacities,
            'description' => $request->description,
        ]);

        return redirect()->route('product-models.edit', $productModel)
            ->with('success', 'Model updated successfully.');
    }

    public function destroy(ProductModel $productModel)
    {
        $this->ensureAdmin();

        if ($productModel->items()->exists()) {
            return back()->with('error', 'Cannot delete model because it has linked items.');
        }

        $productModel->clearMediaCollection('product-photos');
        $productModel->delete();

        return redirect()->route('product-models.index')
            ->with('success', 'Model deleted successfully.');
    }

    public function uploadPhoto(Request $request, ProductModel $productModel)
    {
        $this->ensureAdmin();

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'colour' => 'nullable|string|max:255',
            'capacity' => 'nullable|string|max:255',
        ]);

        $productModel->addMedia($request->file('photo'))
            ->withCustomProperties([
                'colour' => $request->colour,
                'capacity' => $request->capacity,
            ])
            ->toMediaCollection('product-photos');

        return back()->with('success', 'Photo uploaded successfully.');
    }

    public function deletePhoto(ProductModel $productModel, Media $media)
    {
        $this->ensureAdmin();

        if ($media->model_id !== $productModel->id || $media->model_type !== ProductModel::class) {
            abort(404, 'Foto no encontrada.');
        }

        $media->delete();

        return back()->with('success', 'Photo deleted successfully.');
    }

    public function reorderPhotos(Request $request, ProductModel $productModel)
    {
        $this->ensureAdmin();

        $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:media,id',
        ]);

        $order = 1;
        foreach ($request->photo_ids as $photoId) {
            Media::where('id', $photoId)
                ->where('model_id', $productModel->id)
                ->where('model_type', ProductModel::class)
                ->update(['order_column' => $order]);
            $order++;
        }

        return back()->with('success', 'Photos reordered successfully.');
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $type = $request->input('type');

        $models = ProductModel::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->when($type, function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'manufacturer', 'type']);

        return response()->json(['models' => $models]);
    }

    public function sync(Request $request)
    {
        $this->ensureAdmin();

        // Capture Artisan output
        $output = [];
        $exitCode = Artisan::call('market:extract-product-models', [], $output);

        // Parse output to get counts
        $created = 0;
        $updated = 0;
        $modelsFound = 0;

        foreach ($output as $line) {
            if (preg_match('/Models found:\s*(\d+)/', $line, $matches)) {
                $modelsFound = (int) $matches[1];
            } elseif (preg_match('/ProductModels created:\s*(\d+)/', $line, $matches)) {
                $created = (int) $matches[1];
            } elseif (preg_match('/ProductModels updated:\s*(\d+)/', $line, $matches)) {
                $updated = (int) $matches[1];
            }
        }

        return back()->with('success', "Sync complete: {$created} created, {$updated} updated, {$modelsFound} models found.");
    }
}
