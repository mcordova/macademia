#!/usr/bin/env php
<?php
$arg = $argv[1] ?? null;

while (true) {
    $text = $arg !== null ? $arg : trim(fgets(STDIN));
    if (ctype_digit($text) && $text >= 0 && $text <= 100000) {
        $n = (int)$text;
        for ($i = 1; $i <= 10; $i++) {
            echo "$i x $n = " . ($i * $n) . "\n";
        }
        break;
    }
    echo "Invalid input. Please enter an integer between 0 and 100000.\n";
    $arg = null;
}
