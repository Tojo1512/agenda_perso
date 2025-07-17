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

    // Remplacer les images manquantes par des placeholders
    replaceMissingImages();
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
    // Gestionnaire pour les select de statut
    var statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(function(select) {
        select.addEventListener('change', updateTaskStatus);
    });

    // Gestionnaire pour les checkboxes de l'emploi du temps
    var taskCheckboxes = document.querySelectorAll('.task-status');
    taskCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var taskId = this.getAttribute('data-task-id');
            var status = this.checked ? 'terminee' : 'a_faire';
            var taskElement = this.closest('.tache');
            
            updateTaskStatusAjax(taskId, status, taskElement, this);
        });
    });
}

function updateTaskStatus(event) {
    var select = event.target;
    var taskId = select.getAttribute('data-task-id');
    var status = select.value;
    var row = select.closest('tr') || select.closest('.card');
    var originalValue = select.getAttribute('data-original-value');
    
    updateTaskStatusAjax(taskId, status, row, select, originalValue);
}

function updateTaskStatusAjax(taskId, status, element, control, originalValue = null) {
    // Animation de chargement
    element.classList.add('opacity-50');
    
    // Envoyer la mise à jour via AJAX
    var formData = new FormData();
    formData.append('task_id', taskId);
    formData.append('status', status);
    formData.append('action', 'update_status');

    fetch('ajax/update_task_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            element.classList.remove('opacity-50');
            showStatusMessage('Erreur serveur ou session expirée.', 'danger', element);
            if (control instanceof HTMLSelectElement && originalValue) {
                control.value = originalValue;
            } else if (control instanceof HTMLInputElement) {
                control.checked = !control.checked;
            }
            console.error('Réponse inattendue:', text);
            return;
        }

        element.classList.remove('opacity-50');
        if (data.success) {
            if (status === 'terminee') {
                element.classList.add('tache-terminee');
            } else {
                element.classList.remove('tache-terminee');
            }
            showStatusMessage('Statut mis à jour avec succès.', 'success', element);
            if (control instanceof HTMLSelectElement) {
                control.setAttribute('data-original-value', status);
            }
            // Mettre à jour tous les éléments de la même tâche
            updateAllTaskInstances(taskId, status);
        } else {
            showStatusMessage(data.message || 'Erreur lors de la mise à jour.', 'danger', element);
            if (control instanceof HTMLSelectElement && originalValue) {
                control.value = originalValue;
            } else if (control instanceof HTMLInputElement) {
                control.checked = !control.checked;
            }
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        element.classList.remove('opacity-50');
        showStatusMessage('Erreur de connexion.', 'danger', element);
        if (control instanceof HTMLSelectElement && originalValue) {
            control.value = originalValue;
        } else if (control instanceof HTMLInputElement) {
            control.checked = !control.checked;
        }
    });
}

function updateAllTaskInstances(taskId, status) {
    // Mettre à jour tous les selects
    document.querySelectorAll(`.status-select[data-task-id="${taskId}"]`).forEach(select => {
        select.value = status;
        select.setAttribute('data-original-value', status);
    });

    // Mettre à jour toutes les checkboxes
    document.querySelectorAll(`.task-status[data-task-id="${taskId}"]`).forEach(checkbox => {
        checkbox.checked = status === 'terminee';
    });

    // Mettre à jour toutes les instances de la tâche dans l'emploi du temps
    document.querySelectorAll(`.tache[data-task-id="${taskId}"]`).forEach(element => {
        if (status === 'terminee') {
            element.classList.add('tache-terminee');
        } else {
            element.classList.remove('tache-terminee');
        }
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

/**
 * Remplacer les images manquantes par des placeholders
 */
function replaceMissingImages() {
    // Vérifier les images du calendrier
    var calendarImages = document.querySelectorAll('img[src*="calendar.png"]');
    calendarImages.forEach(function(img) {
        img.onerror = function() {
            // Image de calendrier en base64 (bleu simple)
            this.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0NDggNTEyIj48cGF0aCBmaWxsPSIjMDA3YmZmIiBkPSJNMTUyIDY0SDI5NlYyNGEyNCAyNCAwIDAgMSA0OCAwVjY0aDQ4YzI2LjUgMCA0OCAyMS41IDQ4IDQ4djM1MmMwIDI2LjUtMjEuNSA0OC00OCA0OEg1NmMtMjYuNSAwLTQ4LTIxLjUtNDgtNDhWMTEyYzAtMjYuNSAyMS41LTQ4IDQ4LTQ4aDQ4VjI0YTI0IDI0IDAgMCAxIDQ4IDBWNjR6TTQ4IDQwMlYyNTZIMzk5Ljk2djE0NmMwIDMuMzA4LTIuNjg4IDYtNiA2SDU0Yy0zLjMwOCAwLTYtMi42OTItNi02VjQwMnpNNTQgMjA4VjExMmMwLTMuMzA4IDIuNjkyLTYgNi02aDMyOGMzLjMxMiAwIDYgMi42OTIgNiA2djk2SDU0eiIvPjwvc3ZnPg==';
            this.style.width = '200px';
            this.style.height = '200px';
        };
        
        // Déclencher manuellement si l'image est déjà en erreur
        if (img.complete && img.naturalHeight === 0) {
            img.onerror();
        }
    });
}