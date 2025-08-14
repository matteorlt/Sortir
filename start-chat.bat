@echo off
echo ========================================
echo    DEMARRAGE DU CHAT SORTIR.COM
echo ========================================
echo.

echo 1. Demarrage du serveur WebSocket...
start "Serveur WebSocket Chat" php bin/chat-server.php

echo 2. Attente du demarrage du serveur...
timeout /t 3 /nobreak >nul

echo 3. Ouverture du raccourci chat...
start chat-shortcut.html

echo 4. Ouverture de l'application web...
start http://127.0.0.1:8000

echo.
echo ========================================
echo    CHAT DEMARRE AVEC SUCCES !
echo ========================================
echo.
echo - Serveur WebSocket: ws://localhost:8080
echo - Application web: http://127.0.0.1:8000
echo - Raccourci chat: chat-shortcut.html
echo.
echo Appuyez sur une touche pour fermer...
pause >nul
