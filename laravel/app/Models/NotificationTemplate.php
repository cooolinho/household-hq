<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    const string TABLE = 'notification_templates';

    const string id = 'id';
    const string template_key = 'template_key';
    const string value = 'value';
    const string created_at = Model::CREATED_AT;
    const string updated_at = Model::UPDATED_AT;

    protected $table = self::TABLE;

    protected $fillable = [
        self::template_key,
        self::value,
    ];
}

