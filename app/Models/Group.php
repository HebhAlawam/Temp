<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Group extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'description',
        'type',
        'creator_id',
        'timeline_id'
    ];

    public function user(){
        return $this->belongsToMany(User::class,'user_groups')->withPivot('user_type');
    }
    public function userAmbassador(){
        return $this->belongsToMany(User::class,'user_groups')->withPivot('user_type')->wherePivot('user_type','ambassador');
    }

    public function Timeline(){
        return $this->belongsTo(Timeline::class);
    }

    public function medias()
    {
        return $this->hasOne(Media::class);
    } 


}