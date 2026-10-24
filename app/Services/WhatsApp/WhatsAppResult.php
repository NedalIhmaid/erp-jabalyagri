<?php

namespace App\Services\WhatsApp;

class WhatsAppResult
{
    /**
     * @param  int|null  $status  HTTP status, or null when no response was received
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?int $status,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
        public readonly ?string $details = null,
    ) {
    }

    public static function failure(string $error, ?int $status = null, ?string $details = null): self
    {
        return new self(false, $status, null, $error, $details);
    }
}
