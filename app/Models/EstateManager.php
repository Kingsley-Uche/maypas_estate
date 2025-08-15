<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateManager extends Model
{
     protected $fillable = ['estate_name', 'slug', 'created_by'];
}
