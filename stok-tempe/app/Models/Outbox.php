<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outbox extends Model
{
    protected $table = 'outbox';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'dikirim_pada' => 'datetime',
    ];
}