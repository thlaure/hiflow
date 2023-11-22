<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Restaurant extends Model
{
    use HasFactory;
    
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
