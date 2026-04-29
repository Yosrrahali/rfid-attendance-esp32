// Configuration
const API_URL = 'api.php';

// Charger les données au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    loadLogs();
    loadStats();
    loadAdmins();
    
    // Gérer le formulaire d'ajout
    const addForm = document.getElementById('addUserForm');
    if(addForm) {
        addForm.addEventListener('submit', addUser);
    }
});

// Charger la liste des utilisateurs
function loadUsers() {
    fetch(`${API_URL}?action=get_users`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('usersList');
            if(!tbody) return;
            
            tbody.innerHTML = '';
            
            data.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.nom}</td>
                    <td>${user.prenom}</td>
                    <td>${user.email}</td>
                    <td><code>${user.rfid_uid}</code></td>
                    <td>
                        <button class="delete-btn" onclick="deleteUser(${user.id})">
                            Supprimer
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            // Mettre à jour le total
            document.getElementById('totalUsers').textContent = data.length;
        })
        .catch(error => console.error('Erreur:', error));
}

// Charger l'historique des passages
function loadLogs() {
    fetch(`${API_URL}?action=get_logs`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('logsList');
            if(!tbody) return;
            
            tbody.innerHTML = '';
            
            data.forEach(log => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${log.timestamp}</td>
                    <td>${log.user_nom} ${log.user_prenom}</td>
                    <td><span class="badge ${log.action === 'ENTREE' ? 'badge-success' : 'badge-info'}">${log.action}</span></td>
                    <td><code>${log.rfid_uid}</code></td>
                `;
                tbody.appendChild(row);
            });
            
            // Dernier log
            if(data.length > 0) {
                const last = data[0];
                document.getElementById('lastLog').textContent = 
                    `${last.user_nom} ${last.user_prenom} (${last.timestamp})`;
            }
        })
        .catch(error => console.error('Erreur:', error));
}

// Charger les statistiques
function loadStats() {
    // Compter les passages aujourd'hui
    fetch(`${API_URL}?action=get_today_logs_count`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('todayLogs').textContent = data.count;
        })
        .catch(error => console.error('Erreur:', error));
}

// Ajouter un utilisateur
function addUser(e) {
    e.preventDefault();
    
    const userData = {
        nom: document.getElementById('nom').value,
        prenom: document.getElementById('prenom').value,
        email: document.getElementById('email').value,
        rfid_uid: document.getElementById('rfid_uid').value
    };
    
    fetch(`${API_URL}?action=add_user`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            document.getElementById('addMessage').textContent = '✅ Utilisateur ajouté avec succès !';
            document.getElementById('addUserForm').reset();
            loadUsers(); // Recharger la liste
            setTimeout(() => {
                document.getElementById('addMessage').textContent = '';
            }, 3000);
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'ajout');
    });
}

// Supprimer un utilisateur
function deleteUser(userId) {
    if(confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        fetch(`${API_URL}?action=delete_user&id=${userId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                loadUsers(); // Recharger la liste
                alert('Utilisateur supprimé');
            }
        })
        .catch(error => console.error('Erreur:', error));
    }
}

// Rafraîchir les données toutes les 30 secondes
setInterval(() => {
    if(window.location.pathname.includes('dashboard.html')) {
        loadLogs();
        loadStats();
    }
}, 30000);

// Ajouter à la fin de script.js

// Charger les admins
function loadAdmins() {
    fetch(`${API_URL}?action=get_admins`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('adminsList');
            if(!tbody) return;
            
            tbody.innerHTML = '';
            
            data.forEach(admin => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${admin.id}</td>
                    <td>${admin.nom}</td>
                    <td>${admin.prenom}</td>
                    <td>${admin.email}</td>
                    <td>${admin.username}</td>
                    <td><code>${admin.rfid_uid}</code></td>
                    <td>
                        <button class="delete-btn" onclick="deleteAdmin(${admin.id})">
                            Supprimer
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => console.error('Erreur:', error));
}

// Supprimer un admin
function deleteAdmin(adminId) {
    if(confirm('Supprimer cet administrateur ?')) {
        fetch(`${API_URL}?action=delete_admin&id=${adminId}`)
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    loadAdmins(); 
                }
            });
    }
}

// Appeler loadAdmins au chargement
document.addEventListener('DOMContentLoaded', function() {
    // ... (tes anciens appels)
    loadAdmins(); // Ajoute cette ligne
});

// --- FONCTION SCAN POUR UTILISATEUR (DASHBOARD) ---

// On attend que la page soit chargée pour attacher l'événement
document.addEventListener('DOMContentLoaded', function() {
    const scanUserBtn = document.getElementById('scanUserBtn');
    
    // On vérifie si le bouton existe (pour éviter les erreurs sur les autres pages)
    if(scanUserBtn) {
        let isScanningUser = false;

        scanUserBtn.addEventListener('click', function() {
            if(isScanningUser) return;
            
            isScanningUser = true;
            scanUserBtn.textContent = "En attente...";
            scanUserBtn.style.background = "#ffc107";
            document.getElementById('scanUserStatus').textContent = "Passez la carte près du lecteur...";

            // Appel à l'API pour attendre le scan
            fetch('api.php?action=get_last_scan')
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        document.getElementById('rfid_uid').value = data.uid;
                        document.getElementById('scanUserStatus').textContent = "✅ Carte détectée !";
                        document.getElementById('scanUserStatus').style.color = "green";
                    } else {
                        document.getElementById('scanUserStatus').textContent = "⏱️ Aucune carte détectée (timeout).";
                        document.getElementById('scanUserStatus').style.color = "red";
                    }
                })
                .catch(err => {
                    document.getElementById('scanUserStatus').textContent = "Erreur de connexion.";
                })
                .finally(() => {
                    isScanningUser = false;
                    scanUserBtn.textContent = "📡 Scanner";
                    scanUserBtn.style.background = "#667eea";
                });
        });
    }
});