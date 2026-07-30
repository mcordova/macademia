package main

import (
	"bufio"
	"fmt"
	"os"
	"strconv"
	"strings"
)

func main() {
	fmt.Print("Enter a number: ")
	text, _ := bufio.NewReader(os.Stdin).ReadString('\n')
	text = strings.TrimSpace(text)

	n, err := strconv.Atoi(text)
	if err != nil {
		fmt.Println("Error: not a valid number.")
		return
	}

	fmt.Printf("%d + 1 = %d\n", n, n+1)
}
