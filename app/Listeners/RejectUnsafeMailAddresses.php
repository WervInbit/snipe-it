<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use InvalidArgumentException;
use Symfony\Component\Mime\Address;

class RejectUnsafeMailAddresses
{
    public function handle(MessageSending $event): bool
    {
        if (! config('mail.enabled', true)) {
            return false;
        }

        $message = $event->message;
        $addresses = array_merge(
            $message->getFrom(),
            $message->getTo(),
            $message->getCc(),
            $message->getBcc(),
            $message->getReplyTo(),
            array_filter([$message->getSender(), $message->getReturnPath()])
        );

        foreach ($addresses as $address) {
            if (!$address instanceof Address) {
                continue;
            }

            if (preg_match('/[\r\n]/', $address->getAddress()) > 0) {
                throw new InvalidArgumentException(
                    'Email addresses may not contain line break characters.'
                );
            }
        }

        return true;
    }
}
