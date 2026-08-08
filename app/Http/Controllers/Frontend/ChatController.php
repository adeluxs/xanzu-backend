<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use App\Traits\ImageUpload;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use ImageUpload;

    public function index(Request $request, $username = null)
    {
        $authUser = auth()->id();
        $receiver = null;
        $user = User::where('username', $username)->first();
        if ($user) {
            $currentChat = Chat::where(function ($query) use ($authUser, $user) {
                $query->where('sender_id', $authUser)->where('receiver_id', $user->id);
            })
                ->orWhere(function ($query) use ($authUser, $user) {
                    $query->where('sender_id', $user->id)->where('receiver_id', $authUser);
                })
                ->latest('id')
                ->limit(100)
                ->get()
                ->sortBy('id')
                ->values();
            $receiver = $user;
        } else {
            $currentChat = collect([]);
        }

        if ($currentChat && $currentChat->count()) {

            $receiver = $currentChat->first()->sender_id === $authUser
                ? $currentChat->first()->receiver
                : $currentChat->first()->sender;

            // Mark all unread messages in this conversation in one query instead
            // of issuing an UPDATE for every hydrated chat row.
            Chat::query()
                ->where('sender_id', $receiver->id)
                ->where('receiver_id', $authUser)
                ->where('seen', false)
                ->update(['seen' => true]);
        }

        return view('frontend::chat.index', compact('authUser', 'currentChat', 'receiver'));
    }

    public function store(Request $request, User $receiver)
    {
        $request->validate([
            'message' => ['required'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = auth()->user();

        if ($user->id == $receiver->id) {
            notify()->error(__('You can not send message to yourself'));

            return back();
        }

        $data = [
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'message' => $request->message,
        ];

        $chat = Chat::create($data);

        if ($request->hasFile('attachments')) {
            $chat->attachments()->createMany(collect($request->file('attachments'))->map(function ($file) {

                return [
                    'path' => $this->imageUploadTrait($file),
                ];
            }));
        }

        notify()->success(__('Message sent successfully'));

        return back();
    }
}
