<?php

namespace App\Modules\Auth\Domain\Exceptions;

use Exception;

class EmailNotVerifiedException extends Exception
{
    protected $message = 'Tu correo electrónico no ha sido verificado. Revisa tu bandeja y confirma tu email antes de iniciar sesión.';
}
