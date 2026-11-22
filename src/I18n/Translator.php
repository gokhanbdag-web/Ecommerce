<?php
declare(strict_types=1);
namespace Ecommerce\I18n;

final class Translator
{
    private array $messages = [];

    public function __construct(string $root, string $locale)
    {
        if (!preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
            $locale = 'en_US';
        }
        $file = $root . '/languages/' . $locale . '.mo';
        if (!is_file($file)) {
            return;
        }
        $binary = file_get_contents($file);
        if ($binary === false || strlen($binary) < 28) {
            return;
        }
        $header = unpack('Vmagic/Vrevision/Vcount/Voriginal/Vtranslated', substr($binary, 0, 20));
        if ($header['magic'] !== 0x950412de || $header['count'] > 100000) {
            return;
        }
        for ($i = 0; $i < $header['count']; $i++) {
            $a = unpack('Vlength/Voffset', substr($binary, $header['original'] + $i * 8, 8));
            $b = unpack('Vlength/Voffset', substr($binary, $header['translated'] + $i * 8, 8));
            if (!$a || !$b || $a['offset'] + $a['length'] > strlen($binary) || $b['offset'] + $b['length'] > strlen($binary)) {
                continue;
            }
            $key = substr($binary, $a['offset'], $a['length']);
            $value = substr($binary, $b['offset'], $b['length']);
            if ($key !== '' && $value !== '') {
                $this->messages[$key] = $value;
            }
        }
    }

    public function translate(string $message): string
    {
        return $this->messages[$message] ?? $message;
    }
}
