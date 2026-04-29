# rfid-attendance-esp32
IoT system for RFID-based attendance tracking using ESP32

##  Objective
CleverGate est un système fonctionnel et complet de gestion de présence par RFID. Le projet intègre
avec sucées les systèmes embarques (ESP32), la communication réseau (Wi-Fi), les bases de données
(MySQL) et le développement web (PHP, HTML, CSS, JavaScript).

---

## Stack Technologique
Backend:
. Serveur: Apache 2.4 | Langage: PHP 7.4+ | Base: MySQL 5.7+
Frontend:
. HTML5 | CSS3 | JavaScript vanilla
Firmware:
. ESP32 | C/C++ | Bibliotheques: MFRC522, WiFi, HTTPClient

---

## Flux de Fonctionnement
1. L'utilisateur présenté sa carte RFID au lecteur
2. L'ESP32 lit l'UID (identifiant unique) de la carte
3. L'ESP32 envoie une requête HTTP POST au serveur
4. Le serveur vérifie la validité de la carte en base de données
5. Le serveur enregistre la présence avec timestamp
6. Une réponse de succès est envoyée a l'ESP32
7. L'ESP32 active la LED correspondante (vert/rouge/bleu)

---

## 🏗 System Architecture (optional but recommended)
Arborescence du projet:
clevergate/
Frontend :(HTML, CSS, JavaScript)
Backend : (API PHP)
Firmware : (Code ESP32)
Database : (Schema SQL)

---

## Instructions d’exécution du Projet
# Prérequis Système
. Arduino IDE 2.0 ou supérieur (pour programmer l'ESP32)
. XAMPP (Apache, MySQL, PHP) ou un serveur web équivalent
. Un navigateur web moderne (Chrome, Firefox, Edge,opera)
. Un réseau Wi-Fi fonctionnel pour la communication ESP32 - Serveur
# Installation du Backend
1. Cloner le dépôt : git clone https://github.com/Yosrrahali/rfid-attendance-esp32.git
2. Placer le dossier backend dans htdocs de XAMPP et nommer le « clevergate »
3. Activer SQL et apache dans XAMPP
4. Créer une base de données nommée : testpfa
5. Importer le fichier testpfa.sql
6. Configurer config.php avec les identifiants MySQL (mot de passe root MySQL)
7. Ouvrir le lien (http://localhost/testpfa/index.html)
3.3 Installation du Firmware ESP32
7. Installer Arduino IDE
8. Ajouter le support ESP32 via le gestionnaire de cartes
9. Installer les bibliotheques : MFRC522, WiFi, HTTPClient
10. Ouvrir esp32.ino
11. Modifier les identifiants Wi-Fi dans le code
12. Selectionner la carte : ESP32 > ESP32 Dev Module
13. Telecharger le code sur la carte ESP32

# montage 
<img width="628" height="327" alt="image" src="https://github.com/user-attachments/assets/ed9d7759-c1a8-4cde-b625-fdaad1d87d42" />



