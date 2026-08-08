<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\PushNotificationTemplate;
use App\Models\SetTune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:push-notification-template', ['only' => ['template', 'editTemplate', 'updateTemplate']]);
    }

    public function latestNotification()
    {
        $notifications = Notification::where('for', 'admin')->latest()->take(10)->get();
        $totalUnread = Notification::where('for', 'admin')->where('read', 0)->count();
        $totalCount = Notification::where('for', 'admin')->count();
        $lucideCall = true;

        return view('global.__notification_data', compact('notifications', 'totalUnread', 'totalCount', 'lucideCall'))->render();
    }

    public function setTune()
    {
        $set_tunes = SetTune::all();

        return view('backend.setting.notification_tune.index', compact('set_tunes'));
    }

    // notify tune setting

    public function all()
    {
        $notifications = Notification::where('for', 'admin')->latest()->paginate(10);

        return view('backend.notification.index', compact('notifications'));
    }

    public function status($id)
    {
        $set_tune = SetTune::find($id);

        if ($set_tune->status == 0) {
            $set_tune->status = 1;
            $set_tune->save();

            SetTune::whereNot('id', $id)->update(['status' => false]);

            notify()->success(__('Settings has been saved'));

            return back();
        }
        $set_tune->status = 0;
        $set_tune->save();

        SetTune::where('id', SetTune::first()->id)->update(['status' => true]);

        notify()->success(__('Settings has been saved'));

        return back();

    }

    public function editTemplate($id)
    {
        $template = PushNotificationTemplate::find($id);

        return view('backend.push_notification.edit', compact('template'));
    }

    public function updateTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_body' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $input = $request->all();
        $data = [
            'message_body' => nl2br($input['message_body']),
            'title' => $input['title'],
            'status' => $input['status'],
        ];

        $template = PushNotificationTemplate::find($input['id']);

        $template->update($data);

        notify()->success(__('Push Notification Template Updated Successfully'));

        return back();
    }

    public function readNotification($id)
    {
        if ($id == 0) {
            Notification::where('for', 'admin')->update(['read' => 1]);

            return back();
        }
        $notification = Notification::find($id);
        if ($notification->read == 0) {
            $notification->read = 1;
            $notification->save();
        }

        return redirect()->to($notification->action_url);
    }
}
