<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model Client
 * 
 * @property string $name
 * @property string $email
 * @property string $siren
 * @property string $phone
 * @property string $contact
 */
class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'siren',
        'phone',
        'contact',
    ];

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }
}
