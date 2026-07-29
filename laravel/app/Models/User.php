<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\AppConfig;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Class User
 *
 * Columns
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $firstname
 * @property string|null $lastname
 * @property Carbon|null $date_of_birth
 * @property string|null $place_of_birth
 * @property string|null $address_street
 * @property string|null $address_street_number
 * @property string|null $address_zip
 * @property string|null $address_city
 * @property string|null $avatar
 * @property string|null $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements FilamentUser
{
    const string TABLE = 'users';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // columns
    const string id = 'id';
    const string name = 'name';
    const string email = 'email';
    const string email_verified_at = 'email_verified_at';
    const string password = 'password';
    const string remember_token = 'remember_token';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    // profile columns
    const string firstname = 'firstname';
    const string lastname = 'lastname';
    const string date_of_birth = 'date_of_birth';
    const string place_of_birth = 'place_of_birth';
    const string address_street = 'address_street';
    const string address_street_number = 'address_street_number';
    const string address_zip = 'address_zip';
    const string address_city = 'address_city';
    const string avatar = 'avatar';
    const string phone = 'phone';
    const string email_business = 'email_business';
    const string email_private = 'email_private';

    protected $table = self::TABLE;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        self::name,
        self::email,
        self::password,
        self::email_verified_at,
        self::remember_token,
        self::firstname,
        self::lastname,
        self::date_of_birth,
        self::place_of_birth,
        self::address_street,
        self::address_street_number,
        self::address_zip,
        self::address_city,
        self::avatar,
        self::phone,
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
            self::date_of_birth => 'date',
        ];
    }

    public function hasAvatar(): bool
    {
        return !empty($this->avatar) && Storage::disk(AppConfig::FILESYSTEM_USER_AVATAR)->exists($this->avatar);
    }

    public function getAvatarUrl(): ?string
    {
        $disk = Storage::disk(AppConfig::FILESYSTEM_USER_AVATAR);
        if (!empty($this->avatar) && $disk->exists($this->avatar)) {
            return $disk->url($this->avatar);
        }

        return null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
