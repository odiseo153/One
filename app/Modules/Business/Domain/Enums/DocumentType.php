<?php

namespace App\Modules\Business\Domain\Enums;

enum DocumentType: int
{
    case Cedula = 1;
    case Passport = 2;
    case Rnc = 3;
}
