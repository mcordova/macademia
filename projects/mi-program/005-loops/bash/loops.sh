#!/usr/bin/env bash

if [[ -n "$1" ]]; then
    input="$1"
else
    printf "Enter a number (0-100000): "
    read -r input
fi

while ! [[ "$input" =~ ^[0-9]+$ ]] || ! ((input >= 0 && input <= 100000)); do
    echo "Invalid input. Please enter an integer between 0 and 100000."
    printf "Enter a number (0-100000): "
    read -r input
done

for i in {1..10}; do
    echo "$i x $input = $((i * input))"
done
