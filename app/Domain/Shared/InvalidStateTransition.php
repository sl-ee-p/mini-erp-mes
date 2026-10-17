<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use BackedEnum;
use DomainException;

class InvalidStateTransition extends DomainException
{
    public static function between(BackedEnum $from, BackedEnum $to, string $subject): self
    {
        return new self(sprintf('%s cannot move from "%s" to "%s".', $subject, $from->value, $to->value));
    }
}
