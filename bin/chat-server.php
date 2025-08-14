<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\ChatWebSocket;
use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__).'/.env');

// Créer l'application Symfony
$kernel = new \App\Kernel($_SERVER['APP_ENV'] ?? 'dev', $_SERVER['APP_DEBUG'] ?? false);
$kernel->boot();

// Récupérer les services nécessaires
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$serializer = $container->get('serializer');

// Créer le serveur WebSocket
$chatWebSocket = new ChatWebSocket($entityManager, $serializer);

// Configurer le serveur
$server = IoServer::factory(
    new HttpServer(
        new WsServer($chatWebSocket)
    ),
    8080
);

echo "Serveur de chat démarré sur le port 8080\n";
echo "Appuyez sur Ctrl+C pour arrêter le serveur\n";

// Démarrer le serveur
$server->run();
