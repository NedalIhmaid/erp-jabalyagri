<?php

namespace App\Services\WhatsApp;

class WhatsAppMessage
{
    /**
     * @param  string  $templateKey  Key into config('services.whatsapp.templates')
     * @param  array<int, string|int|float|null>  $parameters  Ordered body parameters for the template
     * @param  string  $text  Free-form body used when the channel runs in "text" mode
     */
    public function __construct(
        public readonly string $templateKey,
        public readonly array $parameters,
        public readonly string $text,
    ) {
    }

    public static function make(string $templateKey, array $parameters, string $text): self
    {
        return new self($templateKey, $parameters, $text);
    }

    /**
     * Meta rejects template parameters containing newlines, tabs, or runs of
     * four or more spaces, so values are flattened before they are sent.
     *
     * @return array<int, string>
     */
    public function sanitizedParameters(): array
    {
        return array_map(function ($value): string {
            $value = (string) ($value ?? '—');
            $value = preg_replace('/\s+/u', ' ', $value);

            return trim($value) === '' ? '—' : trim($value);
        }, array_values($this->parameters));
    }
}
