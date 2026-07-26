<?php

namespace App\Http\Controllers\Backend;

use App\Models\Country;
use App\Traits\ImageUpload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;

class CountryController implements HasMiddleware
{
    use ImageUpload;

    public static function middleware()
    {
        return [
            new Middleware('permission:country-list', ['only' => ['index']]),
            new Middleware('permission:country-create', ['only' => ['create', 'store']]),
            new Middleware('permission:country-edit', ['only' => ['edit', 'update']]),
            new Middleware('permission:country-delete', ['only' => ['destroy']]),
        ];
    }

    /**
     * Display a listing of the Country.
     */
    public function index(Request $request)
    {
        $countries = Country::latest()->when($request->get('search'), function ($query, $search) {
            $query->where('name', 'like', "%$search%")
                ->orWhere('currency_code', 'like', "%$search%")
                ->orWhere('dial_code', 'like', "%$search%");
        })->paginate();

        return view('backend.country.index', compact('countries'));
    }

    /**
     * Show the form for creating a new Country.
     */
    public function create()
    {
        return view('backend.country.create');
    }

    /**
     * Store a newly created Country in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:countries,name',
            'currency_code' => 'required|string|max:10',
            'dial_code' => 'required|string|max:10',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp',
            'own_rate' => 'required|numeric',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return back();
        }

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $this->imageUploadTrait($request->file('image'), null, 'country/');
            }

            Country::create([
                'name' => $request->get('name'),
                'currency_code' => $request->get('currency_code'),
                'image' => $imagePath,
                'own_rate' => $request->get('own_rate'),
                'status' => $request->boolean('status'),
                'dial_code' => $request->get('dial_code'),
            ]);
        } catch (Exception $e) {
            notify()->error(__('Country creation failed!'));

            return back()->withInput();
        }

        notify()->success(__('Country added successfully'));

        return to_route('admin.country.index');
    }

    /**
     * Show the form for editing the specified Country.
     */
    public function edit(string $id)
    {
        $country = Country::findOrFail($id);

        return view('backend.country.edit', compact('country'));
    }

    /**
     * Update the specified Country in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:countries,name,'.$id,
            'currency_code' => 'required|string|max:10',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp',
            'own_rate' => 'required|numeric',
            'status' => 'required|boolean',
            'dial_code' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return back();
        }

        $country = Country::findOrFail($id);

        try {
            $imagePath = $country->image;
            if ($request->hasFile('image')) {
                $imagePath = $this->imageUploadTrait($request->file('image'), $country->image, 'country/');
            }

            $country->update([
                'name' => $request->get('name'),
                'image' => $imagePath,
                'currency_code' => $request->get('currency_code'),
                'own_rate' => $request->get('own_rate'),
                'status' => $request->boolean('status'),
                'dial_code' => $request->get('dial_code'),
            ]);
        } catch (Exception $e) {
            notify()->error(__('Country update failed!'));

            return back()->withInput();
        }

        notify()->success(__('Country updated successfully'));

        return to_route('admin.country.index');
    }

    /**
     * Remove the specified Country from storage.
     */
    public function destroy(string $id)
    {
        $country = Country::findOrFail($id);
        if ($country->image !== null) {
            self::fileDelete($country->image);
        }

        $country->delete();

        notify()->success(__('Country deleted successfully'));

        return to_route('admin.country.index');
    }
}
