<?php
$pageTitle = 'Directorio de Secciones';
include 'includes/header.php';
require_once 'config/database.php';

// Búsqueda y filtros
$search = isset($_GET['search']) ? $_GET['search'] : '';
$nivel_filter = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$grado_filter = isset($_GET['grado']) ? $_GET['grado'] : '';
$seccion_filter = isset($_GET['seccion']) ? $_GET['seccion'] : '';

// Construir query
$where = [];
$params = [];

if ($search) {
    $where[] = "(e.dni LIKE ? OR CONCAT(e.apellido_paterno, ' ', e.apellido_materno, ' ', e.nombres) LIKE ?)";
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
if ($seccion_filter) {
    $where[] = "e.seccion = ?";
    $params[] = $seccion_filter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$estudiantes = fetchAll(
    "SELECT e.*, CONCAT(e.apellido_paterno, ' ', e.apellido_materno, ', ', e.nombres) as nombre_completo 
     FROM estudiantes e 
     $whereClause
     ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres ASC",
    $params
);
?>

<div>
    <div class="page-header">
        <div>
            <h2 class="section-title" style="color: var(--primary); margin-bottom: 0.5rem;">DIRECTORIO DE SECCIONES</h2>
            <p class="page-subtitle">ADMINISTRACIÓN DE ALUMNAS Y CAMBIOS DE AULA 2026</p>
        </div>
        <a href="#" class="btn-export">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            EXPORTAR EXCEL
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" class="filters-bar">
        <div class="search-field">
            <svg class="search-icon-inside" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" name="search" placeholder="BUSCAR POR DNI O NOMBRES..." value="<?php echo htmlspecialchars($search); ?>">
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
        <select class="filter-select" name="seccion" onchange="this.form.submit()">
            <option value="">TODAS SECCIONES</option>
            <option value="A" <?php echo $seccion_filter == 'A' ? 'selected' : ''; ?>>SECCIÓN A</option>
            <option value="B" <?php echo $seccion_filter == 'B' ? 'selected' : ''; ?>>SECCIÓN B</option>
            <option value="C" <?php echo $seccion_filter == 'C' ? 'selected' : ''; ?>>SECCIÓN C</option>
        </select>
    </form>

    <!-- Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="50" style="text-align: center;"><input type="checkbox"></th>
                <th>ESTUDIANTE</th>
                <th>GRADO / SECCIÓN</th>
                <th>ESTADO</th>
                <th style="text-align: right;">ACCIÓN</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $est): ?>
            <tr>
                <td style="text-align: center;"><input type="checkbox"></td>
                <td>
                    <div class="student-info">
                        <span class="student-name"><?php echo $est['nombre_completo']; ?></span>
                        <span class="student-dni"><?php echo $est['dni']; ?></span>
                    </div>
                </td>
                <td style="font-weight: 700; color: var(--text-muted); font-size: 0.85rem;"><?php echo $est['grado']; ?> - SEC. <?php echo $est['seccion']; ?></td>
                <td><span class="status-badge status-pending">POR RATIFICAR</span></td>
                <td>
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                         <a href="ficha.php?dni=<?php echo $est['dni']; ?>" target="_blank" class="action-btn" title="Ver ficha"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>
                         <a href="ratificacion.php?dni=<?php echo $est['dni']; ?>" class="action-btn" title="Editar"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a>
                    </div>
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
