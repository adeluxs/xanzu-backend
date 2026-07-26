<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Traits\ImageUpload;
use Exception;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    use ImageUpload;

    public function index(Request $request)
    {
        $banners = Banner::query()
            ->with('category:id,name,slug,image,description')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->latest()
            ->paginate($request->perPage ?? 15);

        return view('backend.banners.index', compact('banners'));
    }

    public function create()
    {
        $categories = Category::isCategory()->get(['id', 'name']);

        return view('backend.banners.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp'],
        ]);

        try {
            $data = $request->only(['name', 'description', 'category_id']);

            if ($request->hasFile('image')) {
                $data['image'] = $this->imageUploadTrait($request->file('image'), null, 'banner/');
            }

            Banner::create($data);
            notify()->success(__('Banner created successfully!'));
        } catch (Exception $e) {
            notify()->error(__('Banner creation failed!'));
        }

        return to_route('admin.banner.index');
    }

    public function edit($id)
    {
        $banner = Banner::findOrFail($id);
        $categories = Category::isCategory()->get(['id', 'name']);

        return view('backend.banners.edit', compact('banner', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp'],
        ]);

        try {
            $data = $request->only(['name', 'description', 'category_id']);

            if ($request->hasFile('image')) {
                $data['image'] = $this->imageUploadTrait($request->file('image'), $banner->image, 'banner/');
            }

            $banner->update($data);
            notify()->success(__('Banner updated successfully!'));
        } catch (Exception $e) {
            notify()->error(__('Banner update failed!'));
        }

        return to_route('admin.banner.index');
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        if (! empty($banner->image)) {
            $this->delete($banner->image);
        }

        $banner->delete();
        notify()->success(__('Banner deleted successfully!'));

        return to_route('admin.banner.index');
    }
}

