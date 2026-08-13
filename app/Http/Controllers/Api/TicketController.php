<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketMessageResource;
use App\Http\Resources\TicketResource;
use App\Models\Message;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $tickets = Ticket::whereBelongsTo($user)->when($request->filled('status'), function ($query) use ($request) {
            return $query->where('status', $request->input('status'));
        })->when($request->filled('search'), function ($query) use ($request) {
            return $query->where('uuid', 'like', "%{$request->input('search')}%");
        })->latest()->paginate($request->integer('per_page', 10));

        return $this->successResponse(data: TicketResource::collection($tickets), meta: [
            'total' => $tickets->total(),
            'per_page' => $tickets->perPage(),
            'current_page' => $tickets->currentPage(),
            'last_page' => $tickets->lastPage(),
        ]);
    }

    public function store(Request $request)
    {
        $service = new TicketService;
        try {
            $ticket = $service->create($request->all());
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            report($th);

            return $this->errorResponse('Unable to create the ticket. Please try again.', 500);
        }

        return $this->successResponse(data: new TicketResource($ticket), message: 'Ticket created successfully');
    }

    public function reply(Request $request, $uuid)
    {
        $service = new TicketService;

        try {
            $ticket = Ticket::query()
                ->whereBelongsTo($request->user())
                ->where('uuid', $uuid)
                ->first();
            if (! $ticket) {
                return $this->notFoundResponse('Ticket not found');
            }
            $message = $service->reply($request, $ticket);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            report($th);

            return $this->errorResponse('Unable to reply to the ticket. Please try again.', 500);
        }

        return $this->successResponse(TicketMessageResource::make($message), 'Ticket replied successfully');
    }

    public function show(Request $request, string $id)
    {
        $ticket = Ticket::query()
            ->whereBelongsTo($request->user())
            ->where('uuid', $id)
            ->first();
        if (! $ticket) {
            return $this->notFoundResponse('Ticket not found');
        }

        $limit = min(100, max(20, $request->integer('message_limit', 60)));
        $beforeId = $request->integer('before_id');

        $messageQuery = Message::query()
            ->where('ticket_id', $ticket->id)
            ->with([
                'parent:id,message,model',
                'user',
            ]);

        if ($beforeId > 0) {
            $messageQuery->where('id', '<', $beforeId);
        }

        // Fetch newest first so the database can use the ticket/id index, then
        // reverse the small page for normal chronological chat rendering.
        $page = $messageQuery->latest('id')->limit($limit + 1)->get();
        $hasMore = $page->count() > $limit;
        $messages = $page->take($limit)->sortBy('id')->values();

        return $this->successResponse(
            data: [
                'ticket' => new TicketResource($ticket),
                'messages' => TicketMessageResource::collection($messages),
            ],
            meta: [
                'has_more_messages' => $hasMore,
                'oldest_message_id' => $messages->first()?->id,
                'message_limit' => $limit,
            ],
        );
    }

    public function close(Request $request, $uuid)
    {
        $service = new TicketService;

        try {
            $ticket = Ticket::query()
                ->whereBelongsTo($request->user())
                ->where('uuid', $uuid)
                ->first();
            if (! $ticket) {
                return $this->notFoundResponse('Ticket not found');
            }
            $ticket = $service->close($ticket);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            report($th);

            return $this->errorResponse('Unable to close the ticket. Please try again.', 500);
        }

        return $this->successResponse(TicketResource::make($ticket), 'Ticket closed successfully');
    }
}
