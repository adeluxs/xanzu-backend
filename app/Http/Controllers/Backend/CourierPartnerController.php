<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CourierPartner;
use App\Traits\ImageUpload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourierPartnerController extends Controller
{
    use ImageUpload;

    public function __construct()
    {
        $this->middleware('permission:courier-list', ['only' => ['index']]);
        $this->middleware('permission:courier-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:courier-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:courier-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $courierPartners = CourierPartner::when($request->search, function ($query) use ($request) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        })->when($request->sort_field && $request->sort_dir, function ($query) use ($request) {
            $query->orderBy($request->sort_field, $request->sort_dir);
        }, function ($query) {
            $query->orderBy('id', 'desc');
        })->paginate($request->perPage ?? 15);

        return view('backend.courier_partners.index', compact('courierPartners'));
    }

    public function create()
    {
        return view('backend.courier_partners.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:courier_partners,name'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'admin_note' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        try {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $this->imageUploadTrait(query: $request->file('logo'), folder: 'couriers/');
            }

            CourierPartner::create([
                'name' => $request->get('name'),
                'logo' => $logoPath,
                'admin_note' => $request->get('admin_note'),
                'short_description' => $request->get('short_description'),
                'status' => $request->get('status'),
            ]);

            notify()->success(__('Courier partner created successfully!'));
        } catch (Exception $e) {
            notify()->error(__('Courier partner creation failed!'));
        }

        return to_route('admin.courier.index');
    }

    public function edit($id)
    {
        $courierPartner = CourierPartner::findOrFail($id);

        return view('backend.courier_partners.edit', compact('courierPartner'));
    }

    public function update(Request $request, $id)
    {
        $courierPartner = CourierPartner::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:courier_partners,name,' . $courierPartner->id],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'admin_note' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back()->withInput();
        }

        try {
            if ($request->hasFile('logo')) {
                $logoPath = $this->imageUploadTrait($request->file('logo'), $courierPartner->logo, 'couriers/');
            } else {
                $logoPath = $courierPartner->logo;
            }

            $courierPartner->update([
                'name' => $request->get('name'),
                'logo' => $logoPath,
                'admin_note' => $request->get('admin_note'),
                'short_description' => $request->get('short_description'),
                'status' => $request->get('status'),
            ]);

            notify()->success(__('Courier partner updated successfully!'));
        } catch (Exception $e) {
            notify()->error(__('Courier partner update failed!'));
        }

        return to_route('admin.courier.index');
    }

    public function destroy($id)
    {
        $courierPartner = CourierPartner::findOrFail($id);
        if ($courierPartner->logo) {
            $this->delete($courierPartner->logo);
        }

        $courierPartner->delete();

        notify()->success(__('Courier partner deleted successfully!'));

        return to_route('admin.courier.index');
    }
}
