<?php

namespace App\Modules\Business\Domain\Services;

class BusinessStatusService
{
    public const REGISTERED = 'registered';

    public const UNREGISTERED = 'unregistered';

    public const PENDING_VERIFICATION = 'pending_verification';

    public const STATUSES = [
        self::REGISTERED,
        self::UNREGISTERED,
        self::PENDING_VERIFICATION,
    ];
}
