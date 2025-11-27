<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = [];

    // RELACIONES ------------------------------------------

    // Relación 1: Un mensaje pertenece a UN ticket
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // Relación 2: Un mensaje fue escrito por UN usuario (o es null si es del sistema)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
