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
            return $this->errorResponse($th->getMessage());
        }

        return $this->successResponse(data: new TicketResource($ticket), message: 'Ticket created successfully');
    }

    public function reply(Request $request, $uuid)
    {
        $service = new TicketService;

        try {
            $message = $ticket = $service->reply($request, $uuid);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return $this->successResponse(TicketMessageResource::make($message), 'Ticket replied successfully');
    }

    public function show(string $id)
    {
        $ticket = Ticket::uuid($id);

        $messages = Message::where('ticket_id', $ticket->id)->with(['parent', 'user'])->oldest('id')->get();

        return $this->successResponse(
            data: [
                'ticket' => new TicketResource($ticket),
                'messages' => TicketMessageResource::collection($messages),
            ],
        );
    }

    public function close(Request $request, $uuid)
    {
        $service = new TicketService;

        try {
            $ticket = Ticket::uuid($uuid);
            $ticket = $service->close($ticket);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return $this->successResponse(TicketResource::make($ticket), 'Ticket closed successfully');
    }
}
