<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CardApplication;
use App\Services\CardApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage-card-application', ['only' => ['index', 'show', 'approve', 'hold', 'reject']]);
    }

    public function index(Request $request)
    {
        $perPage = $request->perPage ?? 15;
        $search = $request->search ?? null;
        $status = $request->status ?? null;

        $applications = CardApplication::query()
            ->with('user')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', '%'.$search.'%')
                        ->orWhere('last_name', 'LIKE', '%'.$search.'%')
                        ->orWhere('email', 'LIKE', '%'.$search.'%')
                        ->orWhere('phone_number', 'LIKE', '%'.$search.'%');
                })->orWhereHas('user', function ($q) use ($search) {
                    $q->where('username', 'LIKE', '%'.$search.'%')
                        ->orWhere('email', 'LIKE', '%'.$search.'%');
                });
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(in_array($request->input('sort_field'), ['created_at', 'status', 'email']), function ($query) use ($request) {
                $query->orderBy($request->input('sort_field'), $request->input('sort_dir'));
            }, function ($query) {
                $query->latest();
            })
            ->paginate($perPage);

        return view('backend.card_applications', compact('applications'));
    }

    public function show(CardApplication $cardApplication)
    {
        $cardApplication->load('user');

        return view('backend.card_application_show', compact('cardApplication'));
    }

    public function approve(Request $request, CardApplication $cardApplication): RedirectResponse
    {
        try {
            app(CardApplicationService::class)->approve($cardApplication, $request->input('note'));

            notify()->success(__('Card application approved successfully.'));

        } catch (\Throwable $th) {
            // throw $th;
            notify()->error($th->getMessage());
        }

        return back();
    }

    public function hold(Request $request, CardApplication $cardApplication): RedirectResponse
    {
        app(CardApplicationService::class)->hold($cardApplication, $request->input('note'));

        notify()->success(__('Card application moved to on-hold.'));

        return back();
    }

    public function reject(Request $request, CardApplication $cardApplication): RedirectResponse
    {
        $disableCard = $request->boolean('disable_card_status');

        app(CardApplicationService::class)->reject($cardApplication, $request->input('note'), $disableCard);

        notify()->success(__('Card application rejected successfully.'));

        return back();
    }
}
