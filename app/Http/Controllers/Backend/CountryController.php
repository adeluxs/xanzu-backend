<?php

namespace App\Http\Controllers\Backend;

use App\Models\Country;
use App\Traits\ImageUpload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        $countryNames = collect(getCountries())->pluck('name')->filter()->values()->all();

        $validator = Validator::make($request->all(), [
            'name' => ['required', Rule::in($countryNames), Rule::unique('countries', 'name')],
            'currency_code' => 'required|string|max:10',
            'image' => 'required|image|mimes:png,jpg,jpeg,gif,webp|max:5120',
            'own_rate' => 'required|numeric|min:0',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return back()->withErrors($validator)->withInput();
        }

        $countryData = collect(getCountries())->firstWhere('name', $request->string('name')->toString());
        $countryCode = strtoupper((string) data_get($countryData, 'code', ''));
        $dialCode = (string) data_get($countryData, 'dial_code', '');

        if ($countryCode === '' || $dialCode === '') {
            notify()->error(__('Unable to resolve the selected country details. Please select the country again.'));

            return back()->withInput();
        }

        $imagePath = null;

        try {
            $imagePath = $this->imageUploadTrait($request->file('image'), null, 'country/');

            Country::create([
                'name' => $request->string('name')->toString(),
                'code' => $countryCode,
                'currency_code' => strtoupper($request->string('currency_code')->trim()->toString()),
                'image' => $imagePath,
                'own_rate' => $request->get('own_rate'),
                'status' => $request->boolean('status'),
                'dial_code' => $dialCode,
            ]);
        } catch (Exception $e) {
            if ($imagePath) {
                $this->delete($imagePath);
            }

            Log::error('COUNTRY_CREATE_FAILED', [
                'admin_user_id' => auth()->id(),
                'country_name' => $request->get('name'),
                'country_code' => $countryCode,
                'currency_code' => $request->get('currency_code'),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            report($e);

            notify()->error(__('Country creation failed! Please check the entered information and try again.'));

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
        $country = Country::findOrFail($id);
        $countryNames = collect(getCountries())->pluck('name')->filter()->values()->all();

        $validator = Validator::make($request->all(), [
            'name' => ['required', Rule::in($countryNames), Rule::unique('countries', 'name')->ignore($country->id)],
            'currency_code' => 'required|string|max:10',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp|max:5120',
            'own_rate' => 'required|numeric|min:0',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return back()->withErrors($validator)->withInput();
        }

        $countryData = collect(getCountries())->firstWhere('name', $request->string('name')->toString());
        $countryCode = strtoupper((string) data_get($countryData, 'code', ''));
        $dialCode = (string) data_get($countryData, 'dial_code', '');

        if ($countryCode === '' || $dialCode === '') {
            notify()->error(__('Unable to resolve the selected country details. Please select the country again.'));

            return back()->withInput();
        }

        $oldImagePath = $country->image;
        $newImagePath = null;

        try {
            if ($request->hasFile('image')) {
                // Store the replacement first. Delete the previous flag only after
                // the database update succeeds so a failed update cannot break it.
                $newImagePath = $this->imageUploadTrait($request->file('image'), null, 'country/');
            }

            $country->update([
                'name' => $request->string('name')->toString(),
                'code' => $countryCode,
                'image' => $newImagePath ?: $oldImagePath,
                'currency_code' => strtoupper($request->string('currency_code')->trim()->toString()),
                'own_rate' => $request->get('own_rate'),
                'status' => $request->boolean('status'),
                'dial_code' => $dialCode,
            ]);

            if ($newImagePath && $oldImagePath && $newImagePath !== $oldImagePath) {
                $this->delete($oldImagePath);
            }
        } catch (Exception $e) {
            if ($newImagePath) {
                $this->delete($newImagePath);
            }

            Log::error('COUNTRY_UPDATE_FAILED', [
                'admin_user_id' => auth()->id(),
                'country_id' => $country->id,
                'country_name' => $request->get('name'),
                'country_code' => $countryCode,
                'currency_code' => $request->get('currency_code'),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            report($e);

            notify()->error(__('Country update failed! Please check the entered information and try again.'));

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
            $this->delete($country->image);
        }

        $country->delete();

        notify()->success(__('Country deleted successfully'));

        return to_route('admin.country.index');
    }
}
