<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedMessage extends Model
{
    protected $fillable = [
        'message_id',
        'event_name',
        'processed_at',
    ];
}