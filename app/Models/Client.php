<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
