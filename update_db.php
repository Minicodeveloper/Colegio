<?php
require_once 'config/database.php';

try {
    // Nuevos campos anteriores (por si acaso no corrieron)
    $pdo->exec("ALTER TABLE salud_estudiantes ADD COLUMN IF NOT EXISTS tiene_discapacidad BOOLEAN DEFAULT FALSE AFTER grupo_sanguineo");
    $pdo->exec("ALTER TABLE salud_estudiantes ADD COLUMN IF NOT EXISTS detalle_discapacidad TEXT AFTER tiene_discapacidad");
    
    // Ampliar columna SECCION para permitir "ALUMNA NUEVA"
    $pdo->exec("ALTER TABLE estudiantes MODIFY COLUMN seccion VARCHAR(50) DEFAULT 'A'");
    $pdo->exec("ALTER TABLE matriculas MODIFY COLUMN seccion VARCHAR(50) DEFAULT 'A'");

    echo "Base de datos actualizada (Sección ampliada).";
} catch (PDOException $e) {
    echo "Error actualizando BD: " . $e->getMessage();
}
?>
