<?php

namespace PlayOrPay\Domain\Event\DomainEvent;

use PlayOrPay\Domain\Contracts\DomainEvent\DomainEventInterface;

class ReviewAdded implements DomainEventInterface
{
    public function jsonSerialize()
    {
        return [];
    }
}
