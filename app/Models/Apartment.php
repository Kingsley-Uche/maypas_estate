<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ApartmentCategory;
use App\Models\EstateManager;
use App\Models\ApartmentLocation;

class Apartment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'number_item',
        'location',
        'address',
        'estate_manager_id',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Default eager loads.
     *
     * @var array
     */
    protected $with = ['estateManager', 'apartmentAtLocation'];

    /**
     * Get the category that owns the Apartment.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ApartmentCategory::class, 'category_id')
            ->select('id', 'name', 'description');
    }

    /**
     * Get the estate manager that owns the Apartment.
     */
    public function estateManager(): BelongsTo
    {
        return $this->belongsTo(EstateManager::class, 'estate_manager_id')
            ->select('id', 'estate_name', 'slug');
    }

    /**
     * Get the apartment locations for the Apartment.
     */
    public function apartmentAtLocation(): HasMany
    {
        return $this->hasMany(ApartmentLocation::class, 'apartment_id')
            ->select('id', 'apartment_id', 'apartment_identifier');
    }
}
