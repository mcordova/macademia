# 003 — Add

Ask for a number, validate it, then print `n + 1 = result`.

Exits with an error if the input is not a valid integer.

## Run

### Bash

```bash
bash bash/add.sh
```

### Python

```bash
python3 python/add.py
```

### C

```bash
gcc -o add c/add.c && ./add
```

### Go

```bash
go run go/add.go
```

### Rust

```bash
cd rust && cargo run
```

## Example (valid)

```
Enter a number: 5
5 + 1 = 6
```

## Example (invalid)

```
Enter a number: abc
Error: not a valid number.
```

## What to compare

- Input validation: regex, type parsing, or manual checking
- Error handling patterns
- How each language signals failure (exit code, exception, etc.)
