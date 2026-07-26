<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Traits\ImageUpload;
use App\Traits\NotifyTrait;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TicketService
{
    use ImageUpload, NotifyTrait;

    public function validationCreate($data)
    {
        $rules = [
            'title' => 'required',
            'message' => 'required',
            'attachment' => 'nullable',
        ];

        $validator = Validator::make($data, $rules);

        throw_if($validator->fails(), new ValidationException($validator));
    }

    public function create($data, $user_id = null)
    {
        $this->validationCreate($data);

        $attachments = [];

        if (isset($data['attachment'])) {
            foreach ($data['attachment'] as $attachment) {
                if ($attachment instanceof UploadedFile) {
                    $attachments[] = $this->imageUploadTrait(query: $attachment, folder: 'tickets');
                }
            }
        }

        $user = User::find($user_id) ?? auth()->user();

        $ticket = Ticket::create([
            'title' => $data['title'],
            'message' => $data['message'],
            'priority' => $data['priority'] ?? 'low',
            'attachments' => $attachments,
            'user_id' => $user_id ?? auth()->id(),
            'uuid' => 'SUPT' . rand(100000, 999999),
        ]);

        $shortcodes = [
            '[[title]]' => $ticket['title'],
            '[[message]]' => $ticket['message'],
            '[[reply_link]]' => route('admin.ticket.show', $ticket->uuid),
            '[[site_title]]' => setting('site_title', 'global'),
        ];

        $this->sendNotify(setting('support_email', 'global'), 'admin_ticket_reply', 'Admin', $shortcodes, $user->phone, $user->id, route('admin.ticket.show', $ticket->uuid));

        return $ticket;
    }

    public function reply(Request $data, Ticket|string $ticket_id)
    {
        $rules = [
            'message' => 'required',
            'attachments' => 'nullable',
            'attachments.*' => 'nullable|mimes:jpeg,jpg,png,svg,pdf,doc,docx',
            'parent_id' => 'nullable|exists:messages,id',
        ];

        $validator = Validator::make($data->all(), $rules);

        throw_if($validator->fails(), new ValidationException($validator));

        $ticket = $ticket_id instanceof Ticket ? $ticket_id : Ticket::uuid($ticket_id);

        // check if parent message belongs to the same ticket
        if (isset($data['parent_id'])) {
            $parentMessage = $ticket->messages()->where('id', $data['parent_id'])->exists();

            if (!$parentMessage) {
                throw ValidationException::withMessages(['parent_id' => 'The parent message does not belong to the specified ticket.']);
            }
        }

        $attachments = [];

        if (isset($data['attachments'])) {
            $data['attachments'] = is_array($data['attachments']) ? $data['attachments'] : [$data['attachments']];
            foreach ($data['attachments'] as $attachment) {
                if ($attachment instanceof UploadedFile) {
                    $attachments[] = $this->imageUploadTrait(query: $attachment, folder: 'tickets');
                }
            }
        }

        $user = $ticket->user;

        $message = $ticket->messages()->create([
            'model' => 'user',
            'user_id' => $ticket->user_id,
            'message' => $data['message'],
            'attachments' => $attachments,
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        $shortcodes = [
            '[[title]]' => $ticket->title,
            '[[message]]' => $data['message'],
            '[[reply_link]]' => route('admin.ticket.show', $ticket->uuid),
            '[[site_title]]' => setting('site_title', 'global'),
        ];

        $this->sendNotify(setting('support_email', 'global'), 'admin_ticket_reply', 'Admin', $shortcodes, $user->phone, $user->id, route('admin.ticket.show', $ticket->uuid));

        return $message;
    }

    public function close(Ticket|int $ticket_id)
    {
        $ticket = $ticket_id instanceof Ticket ? $ticket_id : Ticket::find($ticket_id);

        $ticket->close();

        return $ticket;
    }
}
