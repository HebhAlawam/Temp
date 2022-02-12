<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    use HasFactory;
    protected $fillable = [
        'rate',
        'user_id',
        'comment_id',
        'post_id'
    ];
    public function user(){
        return $this->belongsTo('\App\Models\User','user_id');
    }
    public function post(){
        return $this->belongsTo('\App\Models\Post','post_id');
    }
    public function comment(){
        return $this->belongsTo('\App\Models\Comment','comment_id');
    }
}
