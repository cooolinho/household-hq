<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // properties
    const id = 'id';
    const name = 'name';
    const email = 'email';
    const email_verified_at = 'email_verified_at';
    const password = 'password';
    const remember_token = 'remember_token';

    // timestamps;
    const created_at = 'created_at';
    const updated_at = 'updated_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        self::name,
        self::email,
        self::password,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        self::password,
        self::remember_token,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::email_verified_at => 'datetime',
            self::password => 'hashed',
        ];
    }
}
