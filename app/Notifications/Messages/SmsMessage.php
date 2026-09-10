<?php

namespace App\Notifications\Messages;

/**
 * The body of one text message, plus the optional one-time code it carries.
 *
 * `code` is kept as a separate field (not parsed back out of the text) so the
 * transport can substitute it into a panel's template pattern — Iranian
 * providers bill and route "pattern"/"lookup" messages differently from free
 * text, and those APIs want the code in its own field.
 */
class SmsMessage
{
    public function __construct(
        public string $text = '',
        public ?string $code = null,
    ) {}

    public static function make(string $text = ''): self
    {
        return new self($text);
    }

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function code(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function isEmpty(): bool
    {
        return trim($this->text) === '';
    }
}
