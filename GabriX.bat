@echo off

REM Vérifie si la commande est 'install'
if "%1"=="install" (
    php "D:\GabriX\install.php" %*
    exit /b
)

REM Vérifie si la commande est 'make:entity'
if "%1"=="make:entity" (
    php "D:\GabriX\make_entity.php" %2
    exit /b
)

REM Vérifie si la commande est 'create:database'
if "%1"=="create:database" (
    php "D:\GabriX\create_database.php" %2
    exit /b
)