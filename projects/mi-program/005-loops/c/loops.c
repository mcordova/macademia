#include <stdio.h>
#include <stdlib.h>
#include <string.h>

int main(int argc, char *argv[]) {
    char input[100];
    char *end;
    long n;
    const char *arg_val = (argc > 1) ? argv[1] : NULL;

    for (;;) {
        if (arg_val) {
            snprintf(input, sizeof(input), "%s\n", arg_val);
            arg_val = NULL;
        } else {
            printf("Enter a number (0-100000): ");
            fgets(input, sizeof(input), stdin);
        }
        n = strtol(input, &end, 10);
        if (end != input && *end == '\n' && n >= 0 && n <= 100000) {
            for (int i = 1; i <= 10; i++) {
                printf("%d x %ld = %ld\n", i, n, i * n);
            }
            break;
        }
        printf("Invalid input. Please enter an integer between 0 and 100000.\n");
    }
    return 0;
}
