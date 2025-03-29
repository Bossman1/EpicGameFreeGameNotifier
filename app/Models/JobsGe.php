<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobsGe extends Model
{
    public $timestamps = true;
    protected $fillable = ['position', 'company', 'start_date', 'end_date', 'link'];

}
