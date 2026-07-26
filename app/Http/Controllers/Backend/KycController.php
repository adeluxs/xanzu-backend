<?php

namespace App\Http\Controllers\Backend;

use App\Enums\KYCStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CronJobController;
use App\Models\Kyc;
use App\Models\User;
use App\Models\UserKyc;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class KycController extends Controller
{
    use ImageUpload, NotifyTrait;

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:kyc-form-manage', ['only' => ['create', 'store', 'show', 'edit', 'update', 'destroy']]);
        $this->middleware('permission:kyc-list', ['only' => ['KycPending', 'kycAll', 'KycRejected']]);
        $this->middleware('permission:kyc-action', ['only' => ['depositAction', 'actionNow']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $kycs = Kyc::all();

        return view('backend.kyc.index', compact('kycs'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return string
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => ['required', 'unique:kycs,name'],
            'status' => ['required'],
            'fields' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');
            return back();
        } else if ($input['user_type'] == 'merchant' && Kyc::where('user_type', 'merchant')->count() >= 1) {
            notify()->error(__('Only one KYC form is allowed for merchant type'), 'Error');
            return back();
        }

        // upload instruction image and get path
        foreach ($input['fields'] as $key => $field) {
            if (isset($field['instruction_image']) && $field['instruction_image'] instanceof UploadedFile) {
                $input['fields'][$key]['instruction_image'] = self::imageUploadTrait($field['instruction_image']);
            }
        }

        $data = [
            'name' => $input['name'],
            'icon' => $request->hasFile('icon') ? self::imageUploadTrait($input['icon']) : null,
            'status' => $input['status'],
            'fields' => json_encode($input['fields']),
            'user_type' => $input['user_type'],
        ];

        $kyc = Kyc::create($data);

        if ($request->boolean('unverified_confirmation')) {
            $this->markAsUnverified();
        }

        notify()->success($kyc->name . ' ' . __('KYC added successfully!'));

        return to_route('admin.kyc-form.index');
    }

    protected function markAsUnverified()
    {
        User::where('kyc', 1)->update([
            'kyc' => 0,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        return view('backend.kyc.create');
    }

    /**
     * Display the specified resource.
     *
     * @return Application|Factory|View
     */
    public function show(Kyc $kyc)
    {
        return view('backend.kyc.edit', compact('kyc'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $kyc = Kyc::find($id);

        return view('backend.kyc.edit', compact('kyc'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     */
    public function destroy($id)
    {

        $kyc = Kyc::find($id);

        $kyc->delete();
        notify()->success(__('KYC deleted successfully!'));

        return to_route('admin.kyc-form.index');
    }

    /**
     * @return Application|Factory|View|JsonResponse
     *
     * @throws Exception
     */
    public function KycPending(Request $request)
    {
        $perPage = $request->perPage ?? 15;
        $search = $request->search ?? null;
        $status = $request->status ?? null;

        $kycs = User::where('kyc', KYCStatus::Pending->value)
            ->search($search)
            ->when(in_array(request('sort_field'), ['updated_at', 'username', 'status']), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->status($status)
            ->latest('updated_at')
            ->paginate($perPage);

        return view('backend.kyc.pending', compact('kycs'));
    }

    /**
     * @return Application|Factory|View|JsonResponse
     *
     * @throws Exception
     */
    public function KycRejected(Request $request)
    {
        $perPage = $request->perPage ?? 15;
        $search = $request->search ?? null;
        $status = $request->status ?? null;

        $kycs = User::where('kyc', KYCStatus::Failed->value)
            ->search($search)
            ->status($status)
            ->when(in_array(request('sort_field'), ['updated_at', 'username', 'status']), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->latest('updated_at')
            ->paginate($perPage);

        return view('backend.kyc.rejected', compact('kycs'));
    }

    public function depositAction($id)
    {
        $user = User::find($id);
        $kycs = UserKyc::where('user_id', $user->id)->where('status', '!=', 'pending')->latest()->get();
        $waiting_kycs = UserKyc::where('user_id', $user->id)->where('status', 'pending')->get();

        $kycStatus = $user->kyc;

        return view('backend.kyc.include.__kyc_data', compact('kycs', 'id', 'waiting_kycs', 'kycStatus'))->render();
    }

    /**
     * @return RedirectResponse
     */
    public function actionNow(Request $request)
    {
        $userKyc = UserKyc::find($request->integer('id'));
        $user = User::find($userKyc->user_id);


        $kycCount = Kyc::where('status', true)->userWise($user)->count();
        $approvedKyc = UserKyc::where('user_id', $userKyc->user_id)->where('status', 'approved')->where('is_valid', true);

        $userKyc->message = $request->get('message');
        $userKyc->status = $request->status;
        $userKyc->is_valid = $request->status == 'approved' ? true : false;
        $userKyc->save();

        $pendingKycs = UserKyc::where('user_id', $userKyc->user_id)->where('status', 'pending')->where('is_valid', true)->count();

        $userKycStatus = KYCStatus::Pending;

        if ($kycCount === 0) {
            $userKycStatus = KYCStatus::NOT_SUBMITTED;
        } elseif ($kycCount === $approvedKyc->count() && $pendingKycs === 0) {
            $userKycStatus = KYCStatus::Verified;
        } elseif ($pendingKycs > 0) {
            $userKycStatus = KYCStatus::Pending;
        } else {
            $userKycStatus = KYCStatus::Failed;
        }

        $user->update([
            'kyc' => $userKycStatus,
        ]);

        if ($user->kyc == KYCStatus::Verified->value && $user->user_type != 'buyer') {
            // add credit limit assigned template
            app(CronJobController::class)->processCreditLimit($user, true);
        }

        $shortcodes = [
            '[[full_name]]' => $user->full_name,
            '[[email]]' => $user->email,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[message]]' => $request->get('message'),
            '[[status]]' => $kycCount == $approvedKyc->count() ? 'approved' : 'rejected',
        ];

        $this->sendNotify($user->email, 'kyc_action', 'User', $shortcodes, $user->phone, $user->id, frontendPanelUrl('kyc', null, false));

        notify()->success(__('KYC Updated Successfully'));

        return to_route('admin.kyc.all');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {

        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required|unique:kycs,name,' . $id,
            'status' => ['required'],
            'fields' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        } else if ($input['user_type'] == 'merchant' && Kyc::where('user_type', 'merchant')->where('id', '!=', $id)->count() >= 1) {
            notify()->error(__('Only one KYC form is allowed for merchant type'), 'Error');

            return back();
        }

        $data = [
            'name' => $input['name'],
            'status' => $input['status'],
            'fields' => $input['fields'],
            'user_type' => $input['user_type'],
        ];

        $kyc = Kyc::find($id);

        if ($request->hasFile('icon')) {
            $data['icon'] = self::imageUploadTrait($input['icon'], $kyc->icon);
        }

        foreach ($input['fields'] as $key => $field) {
            if (isset($field['instruction_image']) && $field['instruction_image'] instanceof UploadedFile) {
                $data['fields'][$key]['instruction_image'] = self::imageUploadTrait($field['instruction_image'], data_get($kyc->fields, "$key.instruction_image", null));
            } else {
                $data['fields'][$key]['instruction_image'] = data_get($kyc->fields, "$key.instruction_image", null);
            }
        }
        $data['fields'] = json_encode($data['fields']);

        $kyc->update($data);

        if ($request->boolean('status')) {

            User::with('kycs')->where('id', 18)->each(function ($user) use ($kyc) {
                $submittedKycs = $user->kycs->where('status', 'approved')->where('is_valid', true)->pluck('kyc_id')->toArray();

                $user->kyc = in_array($kyc->id, $submittedKycs) ? 1 : 0;
                $user->save();
            });
        }

        notify()->success($kyc->name . ' ' . __('KYC updated successfully!'));

        return to_route('admin.kyc-form.index');
    }

    /**
     * @return Application|Factory|View|JsonResponse
     *
     * @throws Exception
     */
    public function kycAll(Request $request)
    {
        $perPage = $request->perPage ?? 15;
        $search = $request->search ?? null;
        $status = $request->status ?? 'all';

        $kycs = User::query()
            ->has('kycs')
            ->when(in_array(request('sort_field'), ['updated_at', 'username', 'status']), function ($query) {
                $query->orderBy(request('sort_field'), request('sort_dir'));
            })
            ->search($search)
            ->status($status)
            ->latest('updated_at')
            ->paginate($perPage);

        return view('backend.kyc.all', compact('kycs'));
    }
}
