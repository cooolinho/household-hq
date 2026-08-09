<?php

namespace App\Models\Contracts;

class Commentables
{
    const string TABLE = 'commentables';
    const string MORPH_NAME = 'commentable';
    const string comment_id = 'comment_id';
    const string commentable_id = 'commentable_id';
    const string commentable_type = 'commentable_type';
}
