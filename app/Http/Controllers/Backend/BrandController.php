<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Traits\ImageUpload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    use ImageUpload;

    public function __construct()
    {
        $this->middleware('permission:brand-list', ['only' => ['index']]);
        $this->middleware('permission:brand-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:brand-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:brand-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $brands = Brand::when($request->search, function ($query) use ($request) {
            $query->where('name', 'LIKE', '%'.$request->search.'%');
        })->when($request->sort_field && $request->sort_dir, function ($query) use ($request) {
            $query->orderBy($request->sort_field, $request->sort_dir);
        }, function ($query) {
            $query->orderBy('id', 'desc');
        })->paginate($request->perPage ?? 15);

        return view('backend.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('backend.brands.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'unique:brands'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'status' => ['required', 'boolean'],
            'description' => ['nullable'],
            'is_popular' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $this->imageUploadTrait(query: $request->file('image'), folder: 'brands/');
            }

            Brand::create([
                'name' => $request->get('name'),
                'slug' => $request->get('slug') ?: null,
                'image' => $imagePath,
                'status' => $request->get('status'),
                'description' => $request->get('description'),
                'is_popular' => $request->get('is_popular') ?? false,
            ]);

            notify()->success(__('Brand created successfully!'));
        } catch (Exception $e) {
            notify()->error(__('Brand creation failed!'));
        }

        return to_route('admin.brand.index');
    }

    public function edit($id)
    {
        $brand = Brand::findOrFail($id);

        return view('backend.brands.edit', compact('brand'));
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:brands,name,'.$brand->id,
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'status' => ['required', 'boolean'],
            'description' => ['nullable'],
            'is_popular' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        try {
            if ($request->hasFile('image')) {
                $imagePath = $this->imageUploadTrait($request->file('image'), $brand->image, 'brands/');
            } else {
                $imagePath = $brand->image;
            }

            $brand->update([
                'name' => $request->get('name'),
                'slug' => $request->get('slug') ?: $brand->slug,
                'image' => $imagePath,
                'status' => $request->get('status'),
                'description' => $request->get('description'),
                'is_popular' => $request->get('is_popular') ?? false,
            ]);

            notify()->success(__('Brand updated successfully!'));
        } catch (Exception $e) {
            notify()->error(__('Brand update failed!'));
        }

        return to_route('admin.brand.index');
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        if ($brand->image) {
            $this->delete($brand->image);
        }
        $brand->delete();
        notify()->success(__('Brand deleted successfully!'));

        return to_route('admin.brand.index');
    }
}
