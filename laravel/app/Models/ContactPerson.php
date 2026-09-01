<?php

namespace App\Models;

use App\Models\Concerns\HasComments;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\EnergyTracker\MeasurementDeviceContractContact;
use Database\Factories\ContactPersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property string $type
 *
 * // timestamps
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * // relation methods
 * @property User $user
 * @property Tag[] $tags
 * @property MeasurementDeviceContract[] $contracts
 */
class ContactPerson extends Model implements CommentableInterface
{
    /** @use HasFactory<ContactPersonFactory> */
    use HasFactory;
    use HasTags;
    use HasComments;

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
    const string type = 'type';
    const string created_at = self::CREATED_AT;
    const string updated_at = self::UPDATED_AT;

    // relation methods
    const string belongs_to_user = 'user';
    const string belongs_to_many_contracts = 'contracts';
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
        self::type,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(
            MeasurementDeviceContract::class,
            MeasurementDeviceContractContact::TABLE,
            MeasurementDeviceContractContact::contact_person_id,
            MeasurementDeviceContractContact::measurement_device_contract_id,
        );
    }

    public function getName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }
}
