<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $guarded = [];
    protected $fillable = [
        'uuid',
        'user_id',
        'expert_id',
        'title',
        'description',
        'category',
        'status',
        'stripe_session_id',
        'amount',
        'is_paid',
        'rating',
        'review',
        'is_disputed', 
        'assigned_at',
        'closed_at'
    ];
    public function getRouteKeyName()
    {
        return 'uuid'; // Para usar URLs seguras
    }
    // RELACIONES ------------------------------------------

    // Relación 1: Un ticket pertenece a UN solo usuario (Cliente)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación 2: Un ticket pertenece (opcionalmente) a UN experto
    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }

    // Relación 3: Un ticket tiene MUCHOS mensajes de chat
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
