<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordGameInfo extends Model
{
    protected $fillable = [
        'game_title',
        'game_description',
        'game_id',
        'game_effective_date',
        'game_seller',
        'game_images',
        'game_offer_start',
        'game_offer_end',
    ];


    protected $casts = [
        'game_images'     => 'array',
        'game_offer_start'=> 'datetime',
        'game_offer_end'  => 'datetime',
    ];

}

