@echo off
setlocal
cd /d "%~dp0"
set "PHP_BIN=php"
where php >nul 2>&1
if errorlevel 1 (
  if exist "D:\xampp\php\php.exe" (set "PHP_BIN=D:\xampp\php\php.exe") else (
    echo Can cai PHP 8+ va them php vao PATH.
    pause
    exit /b 1
  )
)
start "" http://127.0.0.1:4178/
call "%PHP_BIN%" -S 127.0.0.1:4178 -t public router.php
if errorlevel 1 pause
