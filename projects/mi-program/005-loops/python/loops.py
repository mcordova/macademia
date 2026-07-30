#!/usr/bin/env python3
import sys

arg = sys.argv[1] if len(sys.argv) > 1 else None

while True:
    text = arg if arg is not None else input("Enter a number (0-100000): ")
    if text.isdigit():
        n = int(text)
        if 0 <= n <= 100000:
            for i in range(1, 11):
                print(f"{i} x {n} = {i * n}")
            break
    print("Invalid input. Please enter an integer between 0 and 100000.")
    arg = None
