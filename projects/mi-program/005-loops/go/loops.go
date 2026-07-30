package main

import (
	"bufio"
	"fmt"
	"os"
	"strconv"
	"strings"
)

func main() {
	scanner := bufio.NewScanner(os.Stdin)
	argInput := ""
	if len(os.Args) > 1 {
		argInput = os.Args[1]
	}
	for {
		text := argInput
		if argInput != "" {
			argInput = ""
		} else {
			fmt.Print("Enter a number (0-100000): ")
			scanner.Scan()
			text = strings.TrimSpace(scanner.Text())
		}
		n, err := strconv.Atoi(text)
		if err == nil && n >= 0 && n <= 100000 {
			for i := 1; i <= 10; i++ {
				fmt.Printf("%d x %d = %d\n", i, n, i*n)
			}
			break
		}
		fmt.Println("Invalid input. Please enter an integer between 0 and 100000.")
	}
}
