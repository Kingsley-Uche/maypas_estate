<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandlordAgent extends Model
{
    protected $fillable = [
        'user_id',
        'id_card',
        'selfie_photo',
        'cac',
        'business_name',
        'business_state',
        'business_lga',
        'about_business',
        'business_services',
        'business_address',
        'logo',
        'verified'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
