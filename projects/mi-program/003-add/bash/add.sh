#!/usr/bin/env bash

read -p "Enter a number: " input

if ! [[ "$input" =~ ^-?[0-9]+$ ]]; then
    echo "Error: not a valid number."
    exit 1
fi

result=$((input + 1))
echo "$input + 1 = $result"
