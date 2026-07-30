# 005 — Loops

Ask for an integer N (0–100000), then print the multiplication table from 1×N to 10×N.

If the input is invalid, keep asking until a valid integer is entered.

A loop is used for the multiplication table, this time loops are allowed.

## Command-line argument

All versions accept the number as a command-line argument. If provided, it is used
directly; if missing or invalid, the program falls back to interactive stdin input.

```bash
# argument
bash bash/loops.sh 7

# stdin (no argument)
bash bash/loops.sh
```

## Why a 100000 limit?

The result of 10×N must fit in the language's integer type. With the 0–100000 limit the
maximum result is 1000000, which is safe for 32-bit integers and above.

## Run

### Bash

```bash
bash bash/loops.sh [number]
```

### Python

```bash
python3 python/loops.py [number]
```

### C

```bash
gcc -o loops c/loops.c && ./loops [number]
```

### Go

```bash
go run go/loops.go [number]
```

### Rust

```bash
cd rust && cargo run -- [number]
```

### PHP

```bash
php php/loops.php [number]
```

## Example

```
Enter a number (0-100000): 7
1 x 7 = 7
2 x 7 = 14
3 x 7 = 21
4 x 7 = 28
5 x 7 = 35
6 x 7 = 42
7 x 7 = 49
8 x 7 = 56
9 x 7 = 63
10 x 7 = 70
```

## What to compare

- Loop constructs: `for`, `while`, `loop`
- Integer parsing and validation
- Range checking
- Retry / validation loops
- Command-line argument access (`$1`, `sys.argv`, `argc/argv`, `os.Args`, `env::args()`)
- Fallback from argument to stdin
