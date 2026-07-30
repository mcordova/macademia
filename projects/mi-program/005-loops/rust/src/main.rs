use std::env;
use std::io::{self, Write};

fn main() {
    let mut arg = env::args().nth(1);
    loop {
        let input = match arg.take() {
            Some(s) => s,
            None => {
                print!("Enter a number (0-100000): ");
                io::stdout().flush().unwrap();
                let mut buf = String::new();
                io::stdin().read_line(&mut buf).unwrap();
                buf.trim().to_string()
            }
        };
        match input.parse::<i32>() {
            Ok(n) if (0..=100000).contains(&n) => {
                for i in 1..=10 {
                    println!("{} x {} = {}", i, n, i * n);
                }
                break;
            }
            _ => println!("Invalid input. Please enter an integer between 0 and 100000."),
        }
    }
}
