<?php
// api.php
session_start(); // Démarrer la session pour connecter les admins
error_reporting(E_ALL);
ini_set('display_errors',1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

 $action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action) {
    
    // --- PARTIE CONNEXION ADMIN ---
    case 'login_admin':
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Note: Pour le test, on compare en texte clair, mais en prod utilise password_verify
        if($admin && $password === $admin['password']) { 
        // Pour un vrai projet sécurisé, utilisez plutôt :
        // if($admin && password_verify($password, $admin['password'])) {
            
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nom'] = $admin['nom'];
            echo json_encode(['status' => 'success', 'message' => 'Connexion réussie']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Identifiants incorrects']);
        }
        break;

    // --- PARTIE SCAN RFID (Le "Pont") ---
    case 'receive_scan':
        // Cette URL est appelée par l'Arduino/ESP32 quand il scanne une carte
        $uid = isset($_GET['uid']) ? $_GET['uid'] : '';
        if($uid) {
            // On vide la table et on insère le nouveau scan
            $pdo->query("TRUNCATE TABLE scanned_cards");
            $stmt = $pdo->prepare("INSERT INTO scanned_cards (uid) VALUES (?)");
            $stmt->execute([$uid]);
            echo json_encode(['status' => 'success', 'uid' => $uid]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Aucun UID reçu']);
        }
        break;

    case 'get_last_scan':
        // Le site web appelle ceci pour voir si une carte a été scannée
        // On attend max 5 secondes (Long Polling simple)
        $startTime = time();
        while(time() - $startTime < 5) {
            $stmt = $pdo->query("SELECT uid FROM scanned_cards ORDER BY id DESC LIMIT 1");
            $scan = $stmt->fetch(PDO::FETCH_ASSOC);
            if($scan) {
                // On supprime le scan après l'avoir lu pour ne pas le réutiliser
                $pdo->query("TRUNCATE TABLE scanned_cards");
                echo json_encode(['status' => 'success', 'uid' => $scan['uid']]);
                exit;
            }
            usleep(500000); // Attendre 0.5 seconde avant de revérifier
        }
        echo json_encode(['status' => 'empty']);
        break;

    // --- PARTIE GESTION ADMINS ---
    case 'get_admins':
        $stmt = $pdo->query("SELECT id, nom, prenom, email, username, rfid_uid FROM admins ORDER BY nom ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'add_admin':
        $data = json_decode(file_get_contents('php://input'), true);
        // Hashage du mot de passe pour la sécurité (même si simple pour l'instant)
        $hashedPassword = $data['password']; // Pour simplicité test, sinon: password_hash($data['password'], PASSWORD_DEFAULT)
        
        $stmt = $pdo->prepare("INSERT INTO admins (nom, prenom, email, username, password, rfid_uid) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$data['nom'], $data['prenom'], $data['email'], $data['username'], $hashedPassword, $data['rfid_uid']]);
            echo json_encode(['status' => 'success']);
        } catch(PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_admin':
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
        echo json_encode(['status' => 'success']);
        break;

    // --- PARTIE UTILISATEURS (Existante) ---
        case 'verify':
        // Vérifier si une carte RFID est autorisée
        $uid = isset($_GET['uid']) ? $_GET['uid'] : '';
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE rfid_uid = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user) {
            // Vérifier si l'utilisateur a déjà scanné aujourd'hui
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) as nb FROM logs WHERE user_id = ? AND DATE(timestamp) = CURDATE()");
            $stmtCheck->execute([$user['id']]);
            $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            // On enregistre le passage dans tous les cas (historique complet)
            $logStmt = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, 'ENTREE')");
            $logStmt->execute([$user['id']]);

            if($result['nb'] > 0) {
                // Déjà scanné aujourd'hui -> BLEU
                echo json_encode([
                    'status' => 'success',
                    'message' => 'deja_present',
                    'user' => $user
                ]);
            } else {
                // Premier scan aujourd'hui -> VERT (Présence validée)
                echo json_encode([
                    'status' => 'success',
                    'message' => 'present',
                    'user' => $user
                ]);
            }
        } else {
            // Carte inconnue -> ROUGE
            echo json_encode([
                'status' => 'error',
                'message' => 'inconnu'
            ]);
        }
        break;
        
    case 'get_users':
        $stmt = $pdo->query("SELECT * FROM users ORDER BY nom ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    case 'get_logs':
        $stmt = $pdo->query("SELECT logs.*, users.nom as user_nom, users.prenom as user_prenom, users.rfid_uid FROM logs JOIN users ON logs.user_id = users.id ORDER BY logs.timestamp DESC LIMIT 50");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    case 'get_today_logs_count':
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM logs WHERE DATE(timestamp) = CURDATE()");
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        break;
        
    case 'add_user':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, rfid_uid) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$data['nom'], $data['prenom'], $data['email'], $data['rfid_uid']]);
            echo json_encode(['status' => 'success']);
        } catch(PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
        
    case 'delete_user':
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $pdo->prepare("DELETE FROM logs WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        echo json_encode(['status' => 'success']);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Action non valide']);
}
?>