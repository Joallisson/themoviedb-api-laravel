<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResetCode extends Model
{
    use HasFactory;

    protected $table = 'reset_codes';

    protected $fillable = [
        'code',
        'count',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
