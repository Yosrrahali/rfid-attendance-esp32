<?php
// Configuration de la base de données
$host = 'localhost';
$dbname = 'clevergate';
$username = 'root';  // Utilisateur MySQL par défaut sur XAMPP/WAMP
$password = 'Yassourarahali17*';      // Mot de passe vide par défaut sur XAMPP/WAMP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Activer les exceptions pour mieux voir les erreurs
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connexion réussie !"; // Tu peux décommenter pour tester
} catch(PDOException $e) {
    // Envoyer l'erreur en JSON pour la voir dans la console
    header('Content-Type: application/json');
    die(json_encode([
        'status' => 'error', 
        'message' => 'Erreur de connexion BDD: ' . $e->getMessage()
    ]));
}
?>