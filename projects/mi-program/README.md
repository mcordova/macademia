# mi-program

Multi-language programming exercises. Each exercise is implemented in **Bash**, **Python**, **C**, **Go**, and **Rust** so the same logic can be compared across languages.

## Index

| # | Exercise | What it demonstrates |
|---|----------|---------------------|
| [000](000-hello/) | Hello, World! | Minimal program — stdout, boilerplate, compilation vs interpretation |
| [001](001-greet/) | Greet | Stdin input, string interpolation, trimming newlines |
| [002](002-table/) | Multiplication table (2×) | Explicit repetition without loops (educational — shows why loops exist) |
| [003](003-add/) | Add | Input validation, error handling, exit codes |
| [005](005-loops/) | Loops | `for`/`while` loops, CLI arguments, retry-until-valid pattern, integer overflow awareness |

## How to run

```bash
# each exercise has its own instructions
cd 000-hello && cat README.md
```

## Languages covered

| Language | Run command | Typical file |
|----------|-------------|--------------|
| Bash | `bash <dir>/bash/<name>.sh` | `.sh` |
| Python | `python3 <dir>/python/<name>.py` | `.py` |
| C | `gcc -o <name> <dir>/c/<name>.c && ./<name>` | `.c` |
| Go | `go run <dir>/go/<name>.go` | `.go` |
| Rust | `cd <dir>/rust && cargo run` | `src/main.rs` |
