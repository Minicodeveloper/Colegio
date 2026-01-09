<?php
$pageTitle = 'Directorio de Secciones';
include 'includes/header.php';
require_once 'config/database.php';

// Búsqueda y filtros
$search = isset($_GET['search']) ? $_GET['search'] : '';
$nivel_filter = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$grado_filter = isset($_GET['grado']) ? $_GET['grado'] : '';
$seccion_filter = isset($_GET['seccion']) ? $_GET['seccion'] : '';

// Lógica de Eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['id'])) {
    $id_borrar = $_POST['id'];
    try {
        // Borrar dependencias (tablas relacionadas)
        query("DELETE FROM salud_estudiantes WHERE estudiante_id = ?", [$id_borrar]);
        query("DELETE FROM sacramentos WHERE estudiante_id = ?", [$id_borrar]);
        query("DELETE FROM contactos_emergencia WHERE estudiante_id = ?", [$id_borrar]);
        query("DELETE FROM estudiante_representante WHERE estudiante_id = ?", [$id_borrar]);
        query("DELETE FROM documentos_fisicos WHERE estudiante_id = ?", [$id_borrar]); // Si existe, por si acaso
        // Borrar estudiante
        query("DELETE FROM estudiantes WHERE id = ?", [$id_borrar]);
        
        // Mensaje de éxito (podemos usar parametro GET)
        header("Location: directorio.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        $error = "Error al eliminar: " . $e->getMessage();
    }
}


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

// 1. Obtener estudiantes con datos básicos y contacto de emergencia
$estudiantes = fetchAll(
    "SELECT e.*, 
            CONCAT(e.apellido_paterno, ' ', e.apellido_materno, ', ', e.nombres) as nombre_completo,
            ce.nombre_completo as emergencia_nombre,
            ce.celular as emergencia_celular,
            ce.whatsapp as emergencia_whatsapp
     FROM estudiantes e 
     LEFT JOIN contactos_emergencia ce ON e.id = ce.estudiante_id
     $whereClause
     ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres ASC",
    $params
);
?>

<div>
    <div class="page-header">
        <div>
            <h2 class="section-title" style="color: var(--primary); margin-bottom: 0.5rem;">DIRECTORIO DE SECCIONES</h2>
            <p class="page-subtitle">ADMINISTRACIÓN DE ALUMNAS Y DATOS FAMILIARES</p>
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
            <input type="text" name="search" placeholder="BUSCAR POR APELLIDOS..." value="<?php echo htmlspecialchars($search); ?>">
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
    <div style="overflow-x: auto;">
        <table class="data-table" style="font-size: 0.85rem; white-space: nowrap;">
            <thead>
                <tr>
                    <th>NIVEL</th>
                    <th>GRADO</th>
                    <th>SECCIÓN</th>
                    <th>APELLIDOS Y NOMBRES</th>
                    <th>DOMICILIO</th>
                    <th style="background: #eef2ff;">DATOS PAPÁ</th>
                    <th style="background: #fff1f2;">DATOS MAMÁ</th>
                    <th style="background: #f0fdf4;">DATOS APODERADO</th>
                    <th style="background: #fff7ed;">EMERGENCIA</th>
                    <th>ACCIÓN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estudiantes as $est): 
                    // Obtener representantes para cada estudiante
                    $reps = fetchAll("SELECT r.*, er.parentesco as rel_parentesco, er.es_principal 
                                     FROM representantes r 
                                     INNER JOIN estudiante_representante er ON r.id = er.representante_id 
                                     WHERE er.estudiante_id = ?", [$est['id']]);
                    
                    $papa = null;
                    $mama = null;
                    $apoderado = null;

                    foreach ($reps as $r) {
                        $p = strtoupper($r['rel_parentesco']); // Usar el parentesco de la relación
                        if ($p == 'PADRE') $papa = $r;
                        if ($p == 'MADRE') $mama = $r;
                        if ($r['es_principal'] == 1) $apoderado = $r;
                    }

                    // Si no hay apoderado marcado, usar al que sea diferente de padre/madre o el padre por defecto?
                    // El usuario pide "Datos del Apoderado" explícitamente. Mostraremos al principal.
                ?>
                <tr>
                    <td><?php echo $est['nivel']; ?></td>
                    <td><?php echo $est['grado']; ?></td>
                    <td style="text-align: center; font-weight: bold;"><?php echo $est['seccion']; ?></td>
                    <td>
                        <div class="student-info">
                            <span class="student-name"><?php echo $est['nombre_completo']; ?></span>
                        </div>
                    </td>
                    <td><?php echo $est['direccion']; ?></td>
                    
                    <!-- PAPÁ -->
                    <td style="background: #eef2ff;">
                        <?php if ($papa): ?>
                            <div style="font-weight: 600;"><?php echo $papa['apellido_paterno'] . ' ' . $papa['nombres']; ?></div>
                            <div style="font-size: 0.75rem; color: #666;">
                                📱 <?php echo $papa['celular']; ?><br>
                                💬 <?php echo $papa['whatsapp']; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #ccc;">-</span>
                        <?php endif; ?>
                    </td>

                    <!-- MAMÁ -->
                    <td style="background: #fff1f2;">
                        <?php if ($mama): ?>
                            <div style="font-weight: 600;"><?php echo $mama['apellido_paterno'] . ' ' . $mama['nombres']; ?></div>
                            <div style="font-size: 0.75rem; color: #666;">
                                📱 <?php echo $mama['celular']; ?><br>
                                💬 <?php echo $mama['whatsapp']; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #ccc;">-</span>
                        <?php endif; ?>
                    </td>

                    <!-- APODERADO -->
                    <td style="background: #f0fdf4;">
                        <?php if ($apoderado): ?>
                            <div style="font-weight: 600;"><?php echo $apoderado['apellido_paterno'] . ' ' . $apoderado['nombres']; ?></div>
                            <div style="font-size: 0.75rem; color: #059669; font-weight: bold;"><?php echo $apoderado['rel_parentesco']; ?></div>
                            <div style="font-size: 0.75rem; color: #666;">
                                📱 <?php echo $apoderado['celular']; ?><br>
                                💬 <?php echo $apoderado['whatsapp']; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #ccc;">-</span>
                        <?php endif; ?>
                    </td>

                    <!-- EMERGENCIA -->
                    <td style="background: #fff7ed;">
                        <?php if ($est['emergencia_nombre']): ?>
                            <div style="font-weight: 600;"><?php echo $est['emergencia_nombre']; ?></div>
                            <div style="font-size: 0.75rem; color: #666;">
                                📱 <?php echo $est['emergencia_celular']; ?><br>
                                💬 <?php echo $est['emergencia_whatsapp']; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #ccc;">-</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                             <a href="ficha.php?dni=<?php echo $est['dni']; ?>" target="_blank" class="action-btn" title="Ver ficha"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a>
                             <a href="ratificacion.php?dni=<?php echo $est['dni']; ?>" class="action-btn" title="Editar"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a>
                             <a href="#" onclick="confirmDelete(<?php echo $est['id']; ?>)" class="action-btn" title="Eliminar" style="color: #ef4444;"><svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Formulario oculto eliminar -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>
<script>
function confirmDelete(id) {
    if(confirm('¿Está seguro de que desea eliminar a esta estudiante y todos sus datos relacionados? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
