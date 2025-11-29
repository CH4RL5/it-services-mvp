<?php

namespace App\Services;

use Stripe\StripeClient;
use App\Models\Ticket;

class StripeService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(env('STRIPE_SECRET'));
    }

    public function createCheckoutSession(Ticket $ticket)
    {
        // Precio fijo por ticket para el MVP: $150.00 MXN
        $price = 15000; // En centavos

        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'customer_email' => $ticket->user->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'mxn',
                    'product_data' => [
                        'name' => 'Soporte IT: ' . $ticket->category,
                        'description' => $ticket->title,
                    ],
                    'unit_amount' => $price,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            // Rutas a donde vuelve el usuario
            'success_url' => route('ticket.chat', $ticket->uuid) . '?payment=success',
            'cancel_url' => route('dashboard'),
            'metadata' => [
                'ticket_uuid' => $ticket->uuid, // IMPORTANTE para saber qué ticket se pagó
            ],
        ]);

        // Guardamos el ID de sesión en el ticket para rastrearlo
        $ticket->update([
            'stripe_session_id' => $session->id,
            'amount' => $price / 100
        ]);

        return $session->url;
    }
}
