<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriversLicense extends Model
{
    use HasFactory;
    public $table = 'drivers_license';
    protected $fillable = ['licenseId', 'issueDate', 'expiryDate', 'userId', 'image'];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
