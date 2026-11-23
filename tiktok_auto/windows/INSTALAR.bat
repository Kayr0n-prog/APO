@echo off
rem Clique duas vezes para instalar a rotina diaria e testar uma vez.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0instalar.ps1" -TestarAgora
pause
