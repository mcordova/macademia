#!/usr/bin/env python3

text = input("Enter a number: ")

if not text.lstrip('-').isdigit():
    print("Error: not a valid number.")
    exit(1)

n = int(text)
print(f"{n} + 1 = {n + 1}")
