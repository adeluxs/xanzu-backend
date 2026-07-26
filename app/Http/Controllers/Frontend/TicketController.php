<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    use ImageUpload;
    use NotifyTrait;

    public function index()
    {
        $from_date = trim(@explode('to', request('daterange'))[0]);
        $to_date = trim(@explode('to', request('daterange'))[1]);

        $tickets = Ticket::where('user_id', Auth::id())
            ->latest()
            ->when(request('subject'), function ($query) {
                $query->where('title', 'LIKE', '%'.request('subject').'%')
                    ->orWhere('uuid', 'LIKE', '%'.request('subject').'%');
            })
            ->when(request('daterange'), function ($query) use ($from_date, $to_date) {
                $query->whereDate('created_at', '>=', Date::parse($from_date)->format('Y-m-d'));
                $query->whereDate('created_at', '<=', Date::parse($to_date)->format('Y-m-d'));
            })
            ->paginate(request('limit', 15))
            ->withQueryString();

        return view('frontend::ticket.index', compact('tickets'));

    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'title' => ['required'],
            'priority' => ['required'],
            'message' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $user = Auth::user();

        $attachments = [];

        foreach ($request->file('attachments', []) as $attach) {
            $attachments[] = self::imageUploadTrait($attach);
        }

        $data = [
            'uuid' => 'SUPT'.rand(100000, 999999),
            'title' => $request->get('title'),
            'priority' => $request->get('priority'),
            'message' => nl2br($request->get('message')),
            'attachments' => json_encode($attachments),
        ];

        $ticket = $user->tickets()->create($data);

        $shortcodes = [
            '[[full_name]]' => $user->full_name,
            '[[email]]' => $user->email,
            '[[subject]]' => $data['uuid'],
            '[[title]]' => $data['title'],
            '[[message]]' => $data['message'],
            '[[status]]' => 'OPEN',
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
            '[[ticket_url]]' => route('admin.ticket.show', $ticket->uuid),
        ];

        $this->sendNotify(
            setting('support_email', 'global'),
            'admin_support_ticket',
            'Admin',
            $shortcodes,
            $user->phone,
            $user->id,
            route('admin.ticket.show', $ticket->uuid)
        );

        notify()->success(__('Your ticket was created successfully'));

        return to_buyerSellerRoute('ticket.show', $ticket->uuid);

    }

    public function show($uuid)
    {

        $ticket = Ticket::uuid($uuid);

        if (request('action', '') == 'reopen' && $ticket->isClosed()) {
            $ticket->reopen();
        }

        return view('frontend::ticket.show', compact('ticket'));
    }

    public function closeNow($uuid)
    {

        Ticket::uuid($uuid)->close();

        notify()->success(__('Your ticket closed successfully'), 'success');

        return to_buyerSellerRoute('ticket.index');
    }

    public function reply(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'message' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $user = Auth::user();

        $attachments = [];

        foreach ($request->file('attachments', []) as $attach) {
            $attachments[] = self::imageUploadTrait($attach);
        }

        $data = [
            'user_id' => $user->id,
            'message' => nl2br($request->get('message')),
            'attachments' => json_encode($attachments),
        ];

        $ticket = Ticket::uuid($request->get('uuid'));

        $ticket->messages()->create($data);

        $shortcodes = [
            '[[full_name]]' => $user->full_name,
            '[[email]]' => $user->email,
            '[[subject]]' => $request->get('uuid'),
            '[[title]]' => $ticket->title,
            '[[message]]' => $data['message'],
            '[[status]]' => $ticket->status,
            '[[site_title]]' => setting('site_title', 'global'),
            '[[site_url]]' => route('home'),
        ];

        $this->sendNotify(
            setting('support_email', 'global'),
            'admin_support_ticket',
            'Admin',
            $shortcodes,
            $user->phone,
            $user->id,
            route('admin.ticket.show', $ticket->uuid)
        );

        notify()->success(__('Your ticket reply successfully'), 'success');

        return to_buyerSellerRoute('ticket.show', $ticket->uuid);

    }
}
