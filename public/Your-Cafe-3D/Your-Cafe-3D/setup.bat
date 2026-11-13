@echo off
setlocal
cd /d "%~dp0"
where node.exe >nul 2>&1
if errorlevel 1 (
    echo Can Node.js de mo ban 3D. Hay cai Node.js roi chay lai.
    pause
    exit /b 1
)
node.exe local-preview.cjs --open
if errorlevel 1 pause
