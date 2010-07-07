@ECHO OFF

SET "VULTURE_ROOT=<?php echo $vulture_root ?>"
SET "VULTURE_PHP_BIN=<?php echo $php_binary ?>"

IF "<?php echo $installed; ?>" NEQ "1" GOTO NOTINSTALLED

GOTO RUN

:NOTINSTALLED
ECHO The Windows batch file cannot be run directly from the repository.
GOTO END

:RUN

"%VULTURE_PHP_BIN%" "%VULTURE_ROOT%/Core/vulture_command.php" %1 %2 %3 %4 %5 %6 %7 %8 %9

:END
@ECHO ON
