<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
// Add this import
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstName',
        'lastName',
        'otherNames',
        'email',
        'password',
        'role',
        'phoneNumber',
        'otp_code',
        'otp_expires_at',
        'email_verified_at',
        'profileSlug',
        'avatar',
        'profileImage',
        'status',
        'currentPlan', // Add this

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'otp_code',
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'last_seen' => 'datetime', // Add this
            'password' => 'hashed',
        ];
    }

    /**
     * JWT Identifier
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * JWT Custom Claims
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * User role relationship
     */
    public function user_role()
    {
        return $this->belongsTo(Role::class, 'role', 'roleId');
    }


     public function user_plan()
    {
        return $this->belongsTo(Plans::class, 'planId', 'currentPlan');
    }


    // Profile relationship
 public function default_tenant()
{
    return $this->hasMany(Tenant::class, 'ownerId', 'id');
                // ->where('isDefault', 1);
}


 public function currently_active_tenant()
{
    return $this->hasOne(Tenant::class, 'ownerId', 'id')
                ->where('isDefault', 1);
}

public function invoices()
    {
        return $this->hasMany(Invoice::class, 'createdBy', 'id'); // Or direct if invoices belong to users
    }

public function canCreateTenant(): bool
    {
        if ($this->currentPlan === "2") {
            return true;
        }
        return $this->default_tenant()->count() < 2;
    }

    public function canCreateInvoice(): bool
    {
        if ($this->currentPlan === "2") {
            return true;
        }
        return $this->invoices()->count() < 3;
    }

public function subscription()
{
    return $this->hasOne(Subscription::class, 'userId', 'id');
}

}
