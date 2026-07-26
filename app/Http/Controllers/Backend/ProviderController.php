<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ProviderPlatform;
use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Traits\ImageUpload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ProviderController extends Controller
{
    use ImageUpload;

    public function __construct()
    {
        $this->middleware('permission:provider-list', ['only' => ['index']]);
        $this->middleware('permission:provider-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:provider-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:provider-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $providers = Provider::when($request->search, function ($query) use ($request) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        })->when($request->sort_field && $request->sort_dir, function ($query) use ($request) {
            $query->orderBy($request->sort_field, $request->sort_dir);
        }, function ($query) {
            $query->orderBy('id', 'desc');
        })->paginate($request->perPage ?? 15);

        return view('backend.providers.index', compact('providers'));
    }

    public function create()
    {
        return view('backend.providers.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'unique:providers'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'platform' => ['nullable', Rule::in(array_column(ProviderPlatform::cases(), 'value'))],
            'platform_host' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'description' => ['nullable'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $this->imageUploadTrait(query: $request->file('image'), folder: 'providers/');
            }

            $coverImagePath = null;
            if ($request->hasFile('cover_image')) {
                $coverImagePath = $this->imageUploadTrait(query: $request->file('cover_image'), folder: 'providers/covers/');
            }

            Provider::create([
                'name' => $request->get('name'),
                'slug' => $request->get('slug') ?: null,
                'image' => $imagePath,
                'cover_image' => $coverImagePath,
                'website_url' => $request->get('website_url'),
                'platform' => $request->get('platform') ?: ProviderPlatform::WORDPRESS_WOOCOMMERCE->value,
                'platform_host' => $request->get('platform_host'),
                'api_key' => $request->get('api_key'),
                'api_secret' => $request->get('api_secret'),
                'status' => $request->get('status'),
                'description' => $request->get('description'),
            ]);

            notify()->success(__('Provider created successfully!'));
        } catch (Exception $e) {
            dd($e);
            notify()->error(__('Provider creation failed!'));
        }

        return to_route('admin.provider.index');
    }

    public function edit($id)
    {
        $provider = Provider::findOrFail($id);

        return view('backend.providers.edit', compact('provider'));
    }

    public function update(Request $request, $id)
    {
        $provider = Provider::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:providers,name,' . $provider->id,
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'platform' => ['nullable', Rule::in(array_column(ProviderPlatform::cases(), 'value'))],
            'platform_host' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'description' => ['nullable'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        try {
            if ($request->hasFile('image')) {
                $imagePath = $this->imageUploadTrait($request->file('image'), $provider->image, 'providers/');
            } else {
                $imagePath = $provider->image;
            }

            if ($request->hasFile('cover_image')) {
                $coverImagePath = $this->imageUploadTrait($request->file('cover_image'), $provider->cover_image, 'providers/covers/');
            } else {
                $coverImagePath = $provider->cover_image;
            }

            $provider->update([
                'name' => $request->get('name'),
                'slug' => $request->get('slug') ?: $provider->slug,
                'image' => $imagePath,
                'cover_image' => $coverImagePath,
                'website_url' => $request->get('website_url'),
                'platform' => $request->get('platform') ?: ProviderPlatform::WORDPRESS_WOOCOMMERCE->value,
                'platform_host' => $request->get('platform_host'),
                'api_key' => $request->get('api_key'),
                'api_secret' => $request->get('api_secret'),
                'status' => $request->get('status'),
                'description' => $request->get('description'),
            ]);

            notify()->success(__('Provider updated successfully!'));
        } catch (Exception $e) {
            dd($e);
            notify()->error(__('Provider update failed!'));
        }

        return to_route('admin.provider.index');
    }

    public function destroy($id)
    {
        $provider = Provider::findOrFail($id);
        if ($provider->image) {
            $this->delete($provider->image);
        }
        if ($provider->cover_image) {
            $this->delete($provider->cover_image);
        }
        $provider->delete();
        notify()->success(__('Provider deleted successfully!'));

        return to_route('admin.provider.index');
    }
}
