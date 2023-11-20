<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = [
        'route',
        'postal_code',
        'city',
        'country',
        'client_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
