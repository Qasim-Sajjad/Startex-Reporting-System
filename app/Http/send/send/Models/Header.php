<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Header extends Model
{
      // Specify the fields that can be mass-assigned
      protected $fillable = [
        'format_id',
        'hierarchyids',
        'branch_code',
        'time_in',
        'time_out',
        'date',
    ];

    // Automatically cast 'hierarchyids' as an array
    protected $casts = [
        'hierarchyids' => 'array',
        'time_in' => 'boolean',
        'time_out' => 'boolean',
        'date' => 'boolean',
    ];

    // Relationship with the Format model
    public function format()
    {
        return $this->belongsTo(Format::class);
    }
    use HasFactory;
}
