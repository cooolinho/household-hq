<?php

namespace App\Models;

use Database\Factories\ContactPersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Tags\HasTags;


/**
 * Class ContactPerson
 *
 * @property int $id
 *
 * // core relation to user
 * @property int $user_id
 *
 * // contact person data
 * @property string|null $title
 * @property string $firstname
 * @property string $lastname
 * @property string|null $phone_private
 * @property string|null $phone_business
 * @property string|null $email
 * @property string|null $notes
 * @property string|null $avatar
 * @property string|null $role
 *
 * // timestamps
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * // relation methods
 * @property User $user
 * @property Tag[] $tags
 */
class ContactPerson extends Model
{
    /** @use HasFactory<ContactPersonFactory> */
    use HasFactory;
    use HasTags;

    const string TABLE = 'contact_people';

    // columns
    const string id = 'id';
    const string user_id = 'user_id';
    const string title = 'title';
    const string firstname = 'firstname';
    const string lastname = 'lastname';
    const string phone_private = 'phone_private';
    const string phone_business = 'phone_business';
    const string email = 'email';
    const string notes = 'notes';
    const string avatar = 'avatar';
    const string role = 'role';
    const string created_at = self::CREATED_AT;
    const string updated_at = self::UPDATED_AT;

    // relation methods
    const string belongs_to_user = 'user';
    const string morph_to_many_tags = 'tags';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::title,
        self::firstname,
        self::lastname,
        self::phone_private,
        self::phone_business,
        self::email,
        self::notes,
        self::avatar,
        self::role,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function getName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }
}
