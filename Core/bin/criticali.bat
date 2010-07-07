@ECHO OFF

SET "CRITICALI_ROOT=<?php echo $criticali_root ?>"
SET "CRITICALI_PHP_BIN=<?php echo $php_binary ?>"

IF "<?php echo $installed; ?>" NEQ "1" GOTO NOTINSTALLED

GOTO RUN

:NOTINSTALLED
ECHO The Windows batch file cannot be run directly from the repository.
GOTO END

:RUN

"%CRITICALI_PHP_BIN%" "%CRITICALI_ROOT%/Core/criticali_command.php" %1 %2 %3 %4 %5 %6 %7 %8 %9

:END
@ECHO ON
