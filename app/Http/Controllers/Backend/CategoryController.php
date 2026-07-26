<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    use ImageUpload;

    public function __construct()
    {
        $this->middleware('permission:category-list', ['only' => ['index']]);
        $this->middleware('permission:category-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:category-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:category-delete', ['only' => ['delete']]);
    }

    public function index(Request $request)
    {
        $categories = Category::isCategory()
            ->withCount(['children'])
            ->when($request->search, function ($query) use ($request) {
                $query->where('name', 'LIKE', '%'.$request->search.'%');
            })
            ->when($request->category, function ($query) use ($request) {
                $query->where('parent_id', $request->category);
            })
            ->when($request->sort_field && $request->sort_dir, function ($query) use ($request) {
                $query->orderBy($request->sort_field, $request->sort_dir);
            }, function ($query) {
                $query->orderBy('order', 'asc');
            })
            ->paginate();

        $allCategories = Category::isCategory()->get();

        return view('backend.category.index', compact('categories', 'allCategories'));
    }

    public function create()
    {
        $categories = Category::isCategory()->get();

        return view('backend.category.create', compact('categories'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'unique:categories'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'order' => ['nullable', 'numeric', 'unique:categories,order'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'status' => ['required', 'boolean'],
            'description' => ['nullable'],
            'is_trending' => ['nullable', 'boolean'],
            'in_landing_hero' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        $imagePath = $this->imageUploadTrait($request->file('image'));

        $parent_id = empty($request->parent_id) ? null : $request->parent_id;

        Category::create([
            'name' => $request->get('name'),
            'image' => $imagePath,
            'order' => ! $request->order || empty($request->order) ? Category::max('order') + 1 : $request->get('order'),
            'status' => $request->get('status'),
            'description' => $request->get('description'),
            'is_trending' => $request->boolean('is_trending', 0),
            'parent_id' => $parent_id,
            'in_landing_hero' => $request->boolean('in_landing_hero', 0),
        ]);

        notify()->success(__('Category added successfully!'));

        return to_route('admin.category.index');
    }

    public function edit($id)
    {
        $category = Category::with('children')->findOrFail($id);
        $subCategories = $category->children()->latest()->get();

        return view('backend.category.edit', compact('category', 'subCategories'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:categories,name,'.$category->id,
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'order' => 'nullable|numeric|unique:categories,order,'.$category->id,
            'status' => ['required', 'boolean'],
            'description' => ['nullable'],
            'is_trending' => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'in_landing_hero' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        if ($request->hasFile('image')) {
            $imagePath = $this->imageUploadTrait($request->file('image'), $category->image);
        } else {
            $imagePath = $category->image;
        }
        $parent_id = empty($request->parent_id) ? null : $request->parent_id;
        $category->update([
            'name' => $request->get('name'),
            'image' => $imagePath,
            'order' => ! $request->order || empty($request->order) ? Category::max('order') + 1 : $request->get('order'),
            'status' => $request->get('status'),
            'description' => $request->get('description'),
            'is_trending' => $request->boolean('is_trending', 0),
            'in_landing_hero' => $request->boolean('in_landing_hero', 0),
            'parent_id' => $parent_id,
        ]);

        notify()->success(__('Category updated successfully!'));

        return to_route('admin.category.index');
    }

    public function destroy($id)
    {
        $category = Category::withCount(['listings', 'children'])->findOrFail($id);
        if ($category->listings_count > 0) {
            notify()->error(__('Category has listings, can not be deleted!'));

            return back();
        } elseif ($category->children_count > 0) {
            notify()->error(__('Category has sub categories, can not be deleted!'));

            return back();
        }

        $this->delete($category->image);
        $category->delete();

        notify()->success(__('Category deleted successfully!'));

        return to_route('admin.category.index');
    }

    public function trendingToggle(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->update(['is_trending' => ! $category->is_trending]);

        notify()->success(__('Category trending status updated successfully!'));

        return back();
    }
}
