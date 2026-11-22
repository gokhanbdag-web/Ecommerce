<?php
declare(strict_types=1);
$root = dirname(__DIR__);
foreach (glob($root . '/languages/*.po') ?: [] as $file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    $pairs = [];
    $key = null;
    foreach ($lines as $line) {
        if (preg_match('/^msgid "(.*)"$/', $line, $m)) {
            $key = stripcslashes($m[1]);
        } elseif ($key !== null && preg_match('/^msgstr "(.*)"$/', $line, $m)) {
            if ($key !== '') {
                $pairs[$key] = stripcslashes($m[1]);
            }
            $key = null;
        }
    }
    ksort($pairs, SORT_STRING);
    $count = count($pairs);
    $offset = 28 + 16 * $count;
    $original = '';
    $translated = '';
    $originalTable = '';
    $translatedTable = '';
    foreach ($pairs as $source => $target) {
        $originalTable .= pack('VV', strlen($source), $offset + strlen($original));
        $original .= $source . "\0";
    }
    $offset += strlen($original);
    foreach ($pairs as $source => $target) {
        $translatedTable .= pack('VV', strlen($target), $offset + strlen($translated));
        $translated .= $target . "\0";
    }
    $binary = pack('V7', 0x950412de, 0, $count, 28, 28 + 8 * $count, 0, 0);
    file_put_contents(substr($file, 0, -3) . '.mo', $binary . $originalTable . $translatedTable . $original . $translated);
}
