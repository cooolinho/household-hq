<?php

namespace App\Models\Concerns;

use App\Models\Reminder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Model
 */
trait HasReminders
{
    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, Reminder::morph_to_remindable)
            ->orderByDesc(Reminder::created_at);
    }
}
