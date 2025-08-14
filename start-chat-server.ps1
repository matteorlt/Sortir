Write-Host "Demarrage du serveur de chat WebSocket..." -ForegroundColor Green
Write-Host ""
Write-Host "Le serveur sera accessible sur ws://localhost:8080" -ForegroundColor Yellow
Write-Host "Appuyez sur Ctrl+C pour arreter le serveur" -ForegroundColor Yellow
Write-Host ""

try {
    php bin/chat-server.php
} catch {
    Write-Host "Erreur lors du demarrage du serveur: $_" -ForegroundColor Red
}

Read-Host "Appuyez sur Entree pour fermer"
