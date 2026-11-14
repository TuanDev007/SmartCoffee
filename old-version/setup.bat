@echo off
setlocal
cd /d "%~dp0"
where node.exe >nul 2>&1
if errorlevel 1 (
 echo Can cai Node.js LTS tu https://nodejs.org de chay SmartCoffee.
 pause
 exit /b 1
)
node.exe start-smartcoffee.cjs --open
if errorlevel 1 pause
