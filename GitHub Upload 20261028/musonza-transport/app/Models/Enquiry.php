<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['name' => 'encrypted', 'email' => 'encrypted', 'phone' => 'encrypted', 'company' => 'encrypted', 'message' => 'encrypted', 'origin' => 'encrypted', 'destination' => 'encrypted', 'internal_note' => 'encrypted', 'preferred_at' => 'datetime'];
    }
}
