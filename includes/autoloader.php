<?php
/**
 * Autoloader pour charger automatiquement les classes
 */

/**
 * Fonction d'autoloading pour les classes
 * @param string $className Nom de la classe à charger
 */
spl_autoload_register(function ($className) {
    // Chemins possibles pour les différentes catégories de classes
    $paths = [
        MODEL_PATH, 
        CONTROLLER_PATH, 
        INCLUDE_PATH . 'classes/'
    ];
    
    // Extensions de fichiers possibles
    $extensions = ['.php', '.class.php'];
    
    // Pour chaque chemin possible
    foreach ($paths as $path) {
        // Pour chaque extension possible
        foreach ($extensions as $extension) {
            $file = $path . $className . $extension;
            
            // Si le fichier existe, on l'inclut et on termine
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // Gestion spéciale pour les modèles avec le suffixe "Model"
    if (strpos($className, 'Model') !== false) {
        $baseClassName = str_replace('Model', '', $className);
        
        foreach ($extensions as $extension) {
            $file = MODEL_PATH . $baseClassName . $extension;
            
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // Gestion spéciale pour les contrôleurs avec le suffixe "Controller"
    if (strpos($className, 'Controller') !== false) {
        $baseClassName = str_replace('Controller', '', $className);
        
        foreach ($extensions as $extension) {
            $file = CONTROLLER_PATH . $baseClassName . $extension;
            
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
}); 