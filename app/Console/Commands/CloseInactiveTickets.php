<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Log;

class CloseInactiveTickets extends Command
{
    protected $signature = 'tickets:close-inactive';
    protected $description = 'Automatically close support tickets that have been inactive for 48 hours';

    public function handle()
    {
        $tickets = SupportTicket::inactiveFor48Hours()->get();

        if ($tickets->isEmpty()) {
            $this->info('No inactive tickets found.');
            return;
        }

        foreach ($tickets as $ticket) {
            $ticket->status = 'closed';
            $ticket->save();

            // Optional: Log the auto-close
            Log::info("Ticket #{$ticket->id} auto-closed due to 48 hours of inactivity.");
        }

        $this->info("Closed {$tickets->count()} inactive ticket(s).");
    }
}