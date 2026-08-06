<?php

declare(strict_types=1);

namespace App\Identity\AuthKit;

use App\Shared\Mailer\ConfiguredMailer;
use Nowo\AuthKitBundle\Mailer\OutboundMailReadyCheckerInterface;

final readonly class OutboundMailReadyChecker implements OutboundMailReadyCheckerInterface
{
    public function __construct(
        private ConfiguredMailer $mailer,
    ) {
    }

    public function isReady(): bool
    {
        return $this->mailer->isMagicLoginAvailable();
    }
}
