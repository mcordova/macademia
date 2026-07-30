use std::io::{self, Write};

fn main() {
    print!("Enter a number: ");
    io::stdout().flush().unwrap();
    let mut input = String::new();
    io::stdin().read_line(&mut input).unwrap();
    let input = input.trim();

    match input.parse::<i32>() {
        Ok(n) => println!("{} + 1 = {}", n, n + 1),
        Err(_) => {
            println!("Error: not a valid number.");
            std::process::exit(1);
        }
    }
}
