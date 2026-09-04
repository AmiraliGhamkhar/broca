<?php

namespace App\Support;

class StructuredMessageParser
{
    /**
     * Parses Telegram text/caption forms like:
     *
     * key: value
     * [content]
     * multiline
     * [/content]
     *
     * @return array<string, string>
     */
    public static function parse(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $data = [];
        $blockKey = null;
        $blockLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($blockKey !== null) {
                if (preg_match('/^\[\/'.preg_quote($blockKey, '/').'\]$/i', $trimmed)) {
                    $data[$blockKey] = trim(implode("\n", $blockLines));
                    $blockKey = null;
                    $blockLines = [];
                    continue;
                }

                $blockLines[] = $line;
                continue;
            }

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^\[([a-z0-9_]+)\]$/i', $trimmed, $matches)) {
                $blockKey = strtolower($matches[1]);
                $blockLines = [];
                continue;
            }

            if (preg_match('/^([a-z0-9_]+)\s*[:=]\s*(.*)$/i', $line, $matches)) {
                $data[strtolower(trim($matches[1]))] = trim($matches[2]);
            }
        }

        if ($blockKey !== null) {
            $data[$blockKey] = trim(implode("\n", $blockLines));
        }

        return $data;
    }
}
