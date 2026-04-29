-- Créer la base de données
CREATE DATABASE IF NOT EXISTS clevergate;
USE clevergate;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    rfid_uid VARCHAR(20) UNIQUE NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des logs
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(20) DEFAULT 'ENTREE',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Ajouter quelques données de test
INSERT INTO users (nom, prenom, email, rfid_uid) VALUES
('Dupont', 'Jean', 'jean.dupont@email.com', 'A1B2C3D4'),
('Martin', 'Sophie', 'sophie.martin@email.com', 'E5F6G7H8'),
('Durand', 'Pierre', 'pierre.durand@email.com', 'I9J10K11L');

INSERT INTO logs (user_id, action) VALUES
(1, 'ENTREE'),
(2, 'ENTREE'),
(1, 'ENTREE');