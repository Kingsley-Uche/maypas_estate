<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApartmentCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the apartments for this category.
     */
    public function apartments()
    {
        return $this->hasMany(Apartment::class, 'category_id');
    }
}
