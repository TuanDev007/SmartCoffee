@echo off
setlocal
where node.exe >nul 2>&1
if errorlevel 1 (
    echo Node.js was not found on PATH.
    pause
    exit /b 1
)

node.exe "%~dp0serve.cjs" %*
if errorlevel 1 (
    pause
    exit /b 1
)
exit /b %errorlevel%
