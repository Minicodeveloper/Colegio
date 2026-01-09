<?php
$pageTitle = 'Registro Exitoso';
include 'includes/header.php';
require_once 'config/database.php';

// Obtener DNI
$dni = isset($_GET['dni']) ? $_GET['dni'] : '';

$estudiante = fetchOne(
    "SELECT e.*, CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo 
     FROM estudiantes e WHERE e.dni = ?",
    [$dni]
);

$nombre = $estudiante ? $estudiante['nombre_completo'] : 'ESTUDIANTE';
?>

<div class="animate-fade-in" style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="success-card">
        <div class="success-icon">
            <svg viewBox="0 0 24 24" width="40" height="40" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </div>
        
        <h2 class="success-title">REGISTRO EXITOSO</h2>
        <p class="success-subtitle"><?php echo $nombre; ?></p>
        
        <a href="ficha.php?dni=<?php echo $dni; ?>" target="_blank" class="btn-download">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            DESCARGAR FICHA PDF
        </a>
        
        <a href="ratificacion.php" class="btn-new">
            NUEVO REGISTRO
        </a>
    </div>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
