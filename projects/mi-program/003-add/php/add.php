#!/usr/bin/env php
<?php
echo "Enter a number: ";
$text = trim(fgets(STDIN));
if (!ctype_digit(ltrim($text, '-'))) {
    echo "Error: not a valid number.\n";
    exit(1);
}
$n = (int)$text;
echo "$n + 1 = " . ($n + 1) . "\n";
