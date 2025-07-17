/**
 * Script principal pour l'Agenda Personnel et Gestion de Tâches
 */

// Attendre que le DOM soit complètement chargé
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialiser les popovers Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Gestion des formulaires de tâches
    setupTaskForms();
    
    // Gestion du changement de statut des tâches
    setupTaskStatusChange();
    
    // Gestion des catégories
    setupCategoryManagement();
});

/**
 * Configuration des formulaires de tâches
 */
function setupTaskForms() {
    // Sélection de la date d'échéance
    var dateInputs = document.querySelectorAll('.date-input');
    dateInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            validateDateInput(this);
        });
    });
    
    // Validation de formulaire
    var taskForms = document.querySelectorAll('.task-form');
    taskForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var titleInput = form.querySelector('[name="titre"]');
            if (!titleInput.value.trim()) {
                e.preventDefault();
                titleInput.classList.add('is-invalid');
                alert('Le titre de la tâche est obligatoire');
            }
        });
    });
}

/**
 * Configuration du changement de statut des tâches
 */
function setupTaskStatusChange() {
    var statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            var taskId = this.getAttribute('data-task-id');
            var status = this.value;
            
            // Animation de chargement
            var row = this.closest('tr') || this.closest('.card');
            row.classList.add('opacity-50');
            
            // Envoyer la mise à jour via AJAX
            var formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('status', status);
            formData.append('action', 'update_status');
            
            fetch('index.php?page=taches', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                row.classList.remove('opacity-50');
                if (data.success) {
                    // Mise à jour réussie
                    if (status === 'terminee') {
                        row.classList.add('tache-terminee');
                    } else {
                        row.classList.remove('tache-terminee');
                    }
                } else {
                    // Erreur
                    alert('Erreur lors de la mise à jour du statut');
                    // Réinitialiser la sélection
                    this.value = this.getAttribute('data-original-value');
                }
            })
            .catch(error => {
                row.classList.remove('opacity-50');
                console.error('Erreur:', error);
            });
        });
    });
}

/**
 * Configuration de la gestion des catégories
 */
function setupCategoryManagement() {
    // Prévisualisation de la couleur de la catégorie
    var colorPickers = document.querySelectorAll('.color-picker');
    colorPickers.forEach(function(picker) {
        picker.addEventListener('input', function() {
            var preview = document.querySelector('.color-preview');
            if (preview) {
                preview.style.backgroundColor = this.value;
            }
        });
    });
}

/**
 * Validation des champs de date
 */
function validateDateInput(input) {
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    
    var selectedDate = new Date(input.value);
    selectedDate.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        input.classList.add('is-invalid');
        var feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = 'La date ne peut pas être antérieure à aujourd\'hui';
        }
    } else {
        input.classList.remove('is-invalid');
    }
}

/**
 * Confirmation de suppression
 */
function confirmDelete(message) {
    return confirm(message || 'Êtes-vous sûr de vouloir supprimer cet élément ?');
} 