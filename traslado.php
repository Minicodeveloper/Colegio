<?php
$pageTitle = 'Traslado de Sección';
include 'includes/header.php';
require_once 'config/database.php';

// Procesar cambio de sección
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dni']) && isset($_POST['nueva_seccion'])) {
    $dni = $_POST['dni'];
    $nueva_seccion = $_POST['nueva_seccion'];
    
    query("UPDATE estudiantes SET seccion = ? WHERE dni = ?", [$nueva_seccion, $dni]);
    $mensaje = "Sección actualizada correctamente";
}

// Búsqueda y filtros
$search = isset($_GET['search']) ? $_GET['search'] : '';
$nivel_filter = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$grado_filter = isset($_GET['grado']) ? $_GET['grado'] : '';

// Construir query
$where = [];
$params = [];

if ($search) {
    $where[] = "(e.dni LIKE ? OR CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($nivel_filter) {
    $where[] = "e.nivel = ?";
    $params[] = $nivel_filter;
}
if ($grado_filter) {
    $where[] = "e.grado = ?";
    $params[] = $grado_filter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$estudiantes = fetchAll(
    "SELECT e.*, CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo 
     FROM estudiantes e 
     $whereClause
     ORDER BY e.grado, e.seccion, e.apellido_paterno",
    $params
);
?>

<div>
    <div class="page-header">
        <div>
            <h2 class="section-title" style="color: #2563eb; margin-bottom: 0.5rem;">MÓDULO DE TRASLADO DE SECCIÓN</h2>
            <p class="page-subtitle">GESTIÓN DE CAMBIOS DE AULA Y REDISTRIBUCIÓN DE VACANTES 2026</p>
        </div>
    </div>

    <?php if (isset($mensaje)): ?>
        <div style="background: #d1fae5; border: 1px solid #10b981; color: #047857; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 700;">
            ✓ <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="GET" class="filters-bar">
        <div class="search-field">
            <svg class="search-icon-inside" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" name="search" placeholder="BUSCAR ALUMNA..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <select class="filter-select" name="nivel" onchange="this.form.submit()">
            <option value="">TODOS NIVELES</option>
            <option value="PRIMARIA" <?php echo $nivel_filter == 'PRIMARIA' ? 'selected' : ''; ?>>PRIMARIA</option>
            <option value="SECUNDARIA" <?php echo $nivel_filter == 'SECUNDARIA' ? 'selected' : ''; ?>>SECUNDARIA</option>
        </select>
        <select class="filter-select" name="grado" onchange="this.form.submit()">
            <option value="">TODOS GRADOS</option>
            <option value="2° PRIMARIA" <?php echo $grado_filter == '2° PRIMARIA' ? 'selected' : ''; ?>>2° PRIMARIA</option>
            <option value="3° PRIMARIA" <?php echo $grado_filter == '3° PRIMARIA' ? 'selected' : ''; ?>>3° PRIMARIA</option>
            <option value="4° SECUNDARIA" <?php echo $grado_filter == '4° SECUNDARIA' ? 'selected' : ''; ?>>4° SECUNDARIA</option>
        </select>
        <button type="submit" class="btn" style="background: var(--primary); color: white; height: auto; padding: 0.75rem 1.5rem; border-radius: 12px;">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </button>
    </form>

    <!-- Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="50" style="text-align: center;"><input type="checkbox"></th>
                <th>DATOS DE ESTUDIANTE</th>
                <th>UBICACIÓN 2026</th>
                <th style="text-align: center; width: 200px;">CAMBIAR SECCIÓN</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $est): ?>
            <tr>
                <td style="text-align: center;"><input type="checkbox"></td>
                <td>
                    <div class="student-info">
                        <span class="student-name"><?php echo $est['nombre_completo']; ?></span>
                        <span class="student-dni"><?php echo $est['dni']; ?> | <?php echo $est['nivel']; ?></span>
                    </div>
                </td>
                <td style="display: flex; align-items: center; gap: 1rem;">
                    <span style="font-weight: 700; color: var(--text-muted); font-size: 0.85rem;"><?php echo $est['grado']; ?></span>
                    <span class="status-section">SECCIÓN <?php echo $est['seccion']; ?></span>
                </td>
                <td style="text-align: center;">
                    <form method="POST" style="display: inline-flex; gap: 0.5rem; align-items: center;">
                        <input type="hidden" name="dni" value="<?php echo $est['dni']; ?>">
                        <select name="nueva_seccion" class="form-control" style="width: 80px; height: 36px; font-size: 0.85rem; font-weight: 700;">
                            <option value="A" <?php echo $est['seccion'] == 'A' ? 'selected' : ''; ?>>A</option>
                            <option value="B" <?php echo $est['seccion'] == 'B' ? 'selected' : ''; ?>>B</option>
                            <option value="C" <?php echo $est['seccion'] == 'C' ? 'selected' : ''; ?>>C</option>
                            <option value="D" <?php echo $est['seccion'] == 'D' ? 'selected' : ''; ?>>D</option>
                        </select>
                        <button type="submit" class="btn" style="background: #2563eb; color: white; height: 36px; padding: 0 1rem; font-size: 0.75rem;">
                            CAMBIAR
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
