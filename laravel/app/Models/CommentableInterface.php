<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CommentableInterface
{
    public function comments(): MorphMany;
}
