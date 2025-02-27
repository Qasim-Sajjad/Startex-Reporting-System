<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class contests extends Model
{
    use HasFactory;
    protected $fillable = [
        'shop_id',
        'client_id',
        'wave_id',
        'branchName',
        'comentby',
        'comentAcceptReject',
        'clientReply',
        'AdminReply',
        'contest',

    ];
}
