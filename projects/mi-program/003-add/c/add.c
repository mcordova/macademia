#include <stdio.h>
#include <stdlib.h>
#include <ctype.h>

int main(void) {
    char input[100];
    char *end;

    printf("Enter a number: ");
    fgets(input, sizeof(input), stdin);

    long n = strtol(input, &end, 10);
    if (end == input || *end != '\n') {
        printf("Error: not a valid number.\n");
        return 1;
    }

    printf("%ld + 1 = %ld\n", n, n + 1);
    return 0;
}
