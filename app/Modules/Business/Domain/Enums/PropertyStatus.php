<?php

namespace App\Modules\Business\Domain\Enums;

enum PropertyStatus: int
{
    case Rented = 1;
    case Owned = 2;
    case Abandoned = 3;
    case Renovation = 4;
    case Construction = 5;
}
