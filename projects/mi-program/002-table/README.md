# 002 — Multiplication table (2×)

Print the 2 times table from `2×0` to `2×10`.

Written without loops — each line is explicit. Educational: demonstrates
why loops exist by showing the alternative.

## Run

### Bash

```bash
bash bash/table.sh
```

### Python

```bash
python3 python/table.py
```

### C

```bash
gcc -o table c/table.c && ./table
```

### Go

```bash
go run go/table.go
```

### Rust

```bash
cd rust && cargo run
```

### PHP

```bash
php php/table.php
```

## Output

```
2 x 0 = 0
2 x 1 = 2
2 x 2 = 4
2 x 3 = 6
2 x 4 = 8
2 x 5 = 10
2 x 6 = 12
2 x 7 = 14
2 x 8 = 16
2 x 9 = 18
2 x 10 = 20
```

## What to compare

- Lines of code for what should be a 3-line program
- How quickly a reader can spot the pattern vs a loop
- How each language handles string formatting
