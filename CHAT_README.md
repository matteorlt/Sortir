# Système de Chat en Direct - Sortir.com

## 🚀 Fonctionnalités

- **Chat en temps réel** avec WebSocket
- **Notifications** push pour les nouveaux messages
- **Indicateur de frappe** ("Quelqu'un tape...")
- **Interface moderne** et responsive
- **Stockage des messages** en base de données
- **Sécurité** - accès réservé aux utilisateurs connectés

## 📋 Prérequis

- PHP 8.1+
- Symfony 6.4
- Composer
- Base de données MySQL/PostgreSQL

## 🛠️ Installation

### 1. Dépendances installées
```bash
composer require cboden/ratchet
```

### 2. Migration de base de données
```bash
php bin/console doctrine:migrations:migrate
```

## 🚀 Démarrage du serveur WebSocket

### Option 1: Script Windows (.bat)
Double-cliquez sur `start-chat-server.bat`

### Option 2: Script PowerShell
```powershell
.\start-chat-server.ps1
```

### Option 3: Commande manuelle
```bash
php bin/chat-server.php
```

Le serveur WebSocket sera accessible sur `ws://localhost:8080`

## 🎯 Utilisation

### Pour les utilisateurs
1. Connectez-vous à l'application
2. Cliquez sur "Chat" dans la navigation
3. Commencez à discuter en temps réel !

### Fonctionnalités
- **Envoi de messages** : Tapez et appuyez sur Entrée ou cliquez sur l'icône d'envoi
- **Notifications** : Les nouveaux messages déclenchent des notifications push
- **Indicateur de frappe** : Voir quand quelqu'un tape un message
- **Historique** : Tous les messages sont sauvegardés et visibles

## 🔧 Configuration

### Port WebSocket
Le port par défaut est 8080. Pour le modifier, éditez `bin/chat-server.php` :

```php
$server = IoServer::factory(
    new HttpServer(
        new WsServer($chatWebSocket)
    ),
    8080  // ← Changez ce numéro
);
```

### Base de données
Les messages sont automatiquement sauvegardés dans la table `message` avec :
- Contenu du message
- Expéditeur (relation avec Participant)
- Date d'envoi
- Statut de lecture

## 🚨 Dépannage

### Le chat ne fonctionne pas
1. Vérifiez que le serveur WebSocket est démarré
2. Vérifiez la console du navigateur pour les erreurs
3. Assurez-vous que le port 8080 n'est pas bloqué

### Erreurs de connexion WebSocket
- Vérifiez que le serveur est en cours d'exécution
- Vérifiez les permissions de pare-feu
- Testez la connexion : `telnet localhost 8080`

### Messages non sauvegardés
- Vérifiez la connexion à la base de données
- Vérifiez les logs Symfony : `var/log/dev.log`

## 📱 Notifications

Le système utilise l'API Notifications du navigateur :
- Demande automatique de permission
- Notifications pour les nouveaux messages
- Badge de notification dans la navigation

## 🔒 Sécurité

- Accès réservé aux utilisateurs connectés (`ROLE_USER`)
- Validation des données côté serveur
- Protection contre les injections XSS
- Authentification requise pour toutes les routes

## 🎨 Personnalisation

### Couleurs
Modifiez les variables CSS dans `templates/chat/index.html.twig` :
```css
.message.own .message-content {
    background: linear-gradient(135deg, #8f00ff, #7300cc);
}
```

### Interface
Le template utilise Bootstrap 5 et Font Awesome pour une interface moderne et responsive.

## 📞 Support

Pour toute question ou problème :
1. Vérifiez les logs dans `var/log/`
2. Consultez la console du navigateur
3. Vérifiez que tous les services sont démarrés

---

**Note** : Le serveur WebSocket doit être démarré séparément de l'application Symfony principale.
