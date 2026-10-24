<?php

namespace App\Enum;

enum TitleType: string
{
    // These strings are what the database stores and what the JSON contains.
    case Film = 'film';
    case Series = 'series';
}
