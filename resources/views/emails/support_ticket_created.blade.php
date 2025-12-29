<div class="container">
    <div class="header">
        <img src="{{ asset('images/clickinvoice-logo.png') }}" alt="ClickInvoice" width="180">
        <h2 style="color: white; margin-top: 20px;">New Support Ticket</h2>
    </div>

    <div class="content">
        <p><strong>User:</strong> {{ $user->name }} ({{ $user->email }})</p>
        <p><strong>Ticket ID:</strong> #{{ $ticket->ticketId }}</p>
        <p><strong>Subject:</strong> {{ $ticket->subject }}</p>

        <div class="highlight">
            <p><strong>Message:</strong></p>
            <p>{{ nl2br(e($ticket->message)) }}</p>
        </div>

        <a href="https://admin.clickinvoice.app/support/tickets/{{ $ticket->ticketId }}" class="btn">View in Admin Panel</a>
    </div>
</div>

