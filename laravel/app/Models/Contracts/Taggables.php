<?php

namespace App\Models\Contracts;

class Taggables
{
    const string TABLE = 'taggables';
    const string MORPH_NAME = 'taggable';
    const string tag_id = 'tag_id';
    const string taggable_id = 'taggable_id';
    const string taggable_type = 'taggable_type';
}
