<?php
session_start();
$pageTitle = 'Asignación de Secciones 2026';
require_once 'config/database.php';

$_SESSION['usuario_id'] = 1;

// Procesar asignación de sección
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_seccion'])) {
    $estudiante_id = $_POST['estudiante_id'];
    $nueva_seccion = $_POST['nueva_seccion'];
    
    query(
        "UPDATE estudiantes SET seccion = ?, updated_by = ?, updated_at = NOW() WHERE id = ?",
        [$nueva_seccion, $_SESSION['usuario_id'], $estudiante_id]
    );
    
    // Auditoría
    insert(
        "INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_nuevos, ip_address, created_at) 
         VALUES ('estudiantes', ?, 'ASIGNACION_SECCION', ?, ?, ?, NOW())",
        [$estudiante_id, $_SESSION['usuario_id'], json_encode(['seccion' => $nueva_seccion]), $_SERVER['REMOTE_ADDR']]
    );
    
    header("Location: traslado_seccion.php?success=1");
    exit;
}

// Filtros
$filtro_nivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$filtro_grado = isset($_GET['grado']) ? $_GET['grado'] : '';
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'nuevas'; // 'nuevas' o 'ratificadas'

// Construir consulta
$where = [];
$params = [];

if ($filtro_tipo == 'nuevas') {
    $where[] = "e.seccion = 'ALUMNA NUEVA'";
} else {
    $where[] = "e.seccion != 'ALUMNA NUEVA'";
}

if ($filtro_nivel) {
    $where[] = "e.nivel = ?";
    $params[] = $filtro_nivel;
}

if ($filtro_grado) {
    $where[] = "e.grado = ?";
    $params[] = $filtro_grado;
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$estudiantes = fetchAll(
    "SELECT e.*, CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo 
     FROM estudiantes e 
     $where_clause
     ORDER BY e.nivel, e.grado, e.apellido_paterno, e.apellido_materno",
    $params
);

// Contar por tipo
$count_nuevas = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE seccion = 'ALUMNA NUEVA'")['total'];
$count_ratificadas = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE seccion != 'ALUMNA NUEVA'")['total'];

include 'includes/header.php';
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
}

.stat-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
}

.stat-card.active {
    border-color: var(--primary);
    background: #eef2ff;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
}

.filters-bar {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 2px solid #e5e7eb;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.student-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e5e7eb;
}

.student-table table {
    width: 100%;
    border-collapse: collapse;
}

.student-table th {
    background: #f9fafb;
    padding: 1rem;
    text-align: left;
    font-weight: 700;
    color: #6b7280;
    font-size: 0.85rem;
    text-transform: uppercase;
    border-bottom: 2px solid #e5e7eb;
}

.student-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.student-table tr:hover {
    background: #f9fafb;
}

.badge-seccion {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 700;
}

.badge-nueva {
    background: #fef3c7;
    color: #92400e;
}

.badge-asignada {
    background: #dbeafe;
    color: #1e40af;
}

.btn-asignar {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-asignar:hover {
    background: #4338ca;
    transform: translateY(-1px);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    max-width: 500px;
    width: 90%;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    color: var(--primary);
}
</style>

<!-- Stats Cards -->
<div class="stats-grid">
    <a href="?tipo=nuevas" class="stat-card <?php echo $filtro_tipo == 'nuevas' ? 'active' : ''; ?>" style="text-decoration: none;">
        <div class="stat-number"><?php echo $count_nuevas; ?></div>
        <div class="stat-label">🆕 Alumnas Nuevas</div>
    </a>
    
    <a href="?tipo=ratificadas" class="stat-card <?php echo $filtro_tipo == 'ratificadas' ? 'active' : ''; ?>" style="text-decoration: none;">
        <div class="stat-number"><?php echo $count_ratificadas; ?></div>
        <div class="stat-label">✓ Ratificadas</div>
    </a>
</div>

<!-- Filtros -->
<div class="filters-bar">
    <form method="GET" class="filters-grid">
        <input type="hidden" name="tipo" value="<?php echo $filtro_tipo; ?>">
        
        <div class="form-group">
            <label class="form-label">Nivel Educativo</label>
            <select name="nivel" class="form-control" onchange="this.form.submit()">
                <option value="">Todos los niveles</option>
                <option value="INICIAL" <?php echo $filtro_nivel == 'INICIAL' ? 'selected' : ''; ?>>INICIAL</option>
                <option value="PRIMARIA" <?php echo $filtro_nivel == 'PRIMARIA' ? 'selected' : ''; ?>>PRIMARIA</option>
                <option value="SECUNDARIA" <?php echo $filtro_nivel == 'SECUNDARIA' ? 'selected' : ''; ?>>SECUNDARIA</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Grado</label>
            <select name="grado" class="form-control" onchange="this.form.submit()">
                <option value="">Todos los grados</option>
                <option value="4 años" <?php echo $filtro_grado == '4 años' ? 'selected' : ''; ?>>4 años</option>
                <option value="5 años" <?php echo $filtro_grado == '5 años' ? 'selected' : ''; ?>>5 años</option>
                <option value="1°" <?php echo $filtro_grado == '1°' ? 'selected' : ''; ?>>1°</option>
                <option value="2°" <?php echo $filtro_grado == '2°' ? 'selected' : ''; ?>>2°</option>
                <option value="3°" <?php echo $filtro_grado == '3°' ? 'selected' : ''; ?>>3°</option>
                <option value="4°" <?php echo $filtro_grado == '4°' ? 'selected' : ''; ?>>4°</option>
                <option value="5°" <?php echo $filtro_grado == '5°' ? 'selected' : ''; ?>>5°</option>
                <option value="6°" <?php echo $filtro_grado == '6°' ? 'selected' : ''; ?>>6°</option>
            </select>
        </div>
        
        <div class="form-group" style="display: flex; align-items: flex-end;">
            <a href="?tipo=<?php echo $filtro_tipo; ?>" class="btn btn-secondary" style="width: 100%;">Limpiar Filtros</a>
        </div>
    </form>
</div>

<!-- Tabla de Estudiantes -->
<div class="student-table">
    <table>
        <thead>
            <tr>
                <th>DNI</th>
                <th>Apellidos y Nombres</th>
                <th>Nivel</th>
                <th>Grado</th>
                <th>Sección Actual</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($estudiantes)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                        No hay estudiantes con los filtros seleccionados
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($estudiantes as $est): ?>
                <tr>
                    <td><strong><?php echo $est['dni']; ?></strong></td>
                    <td><?php echo $est['nombre_completo']; ?></td>
                    <td><?php echo $est['nivel']; ?></td>
                    <td><?php echo $est['grado']; ?></td>
                    <td>
                        <span class="badge-seccion <?php echo $est['seccion'] == 'ALUMNA NUEVA' ? 'badge-nueva' : 'badge-asignada'; ?>">
                            <?php echo $est['seccion']; ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn-asignar" onclick="abrirModal(<?php echo $est['id']; ?>, '<?php echo addslashes($est['nombre_completo']); ?>', '<?php echo $est['seccion']; ?>')">
                            <?php echo $est['seccion'] == 'ALUMNA NUEVA' ? 'Asignar Sección' : 'Cambiar Sección'; ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal de Asignación -->
<div id="modalAsignar" class="modal">
    <div class="modal-content">
        <h2 class="modal-title">Asignar Sección</h2>
        <form method="POST">
            <input type="hidden" name="estudiante_id" id="modal_estudiante_id">
            <input type="hidden" name="asignar_seccion" value="1">
            
            <div class="form-group">
                <label class="form-label">Estudiante</label>
                <input type="text" id="modal_estudiante_nombre" class="form-control" disabled>
            </div>
            
            <div class="form-group">
                <label class="form-label">Sección Actual</label>
                <input type="text" id="modal_seccion_actual" class="form-control" disabled>
            </div>
            
            <div class="form-group">
                <label class="form-label required">Nueva Sección</label>
                <select name="nueva_seccion" class="form-control" required>
                    <option value="">-- Seleccione --</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="E">E</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Asignar Sección</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModal(id, nombre, seccionActual) {
    document.getElementById('modal_estudiante_id').value = id;
    document.getElementById('modal_estudiante_nombre').value = nombre;
    document.getElementById('modal_seccion_actual').value = seccionActual;
    document.getElementById('modalAsignar').classList.add('active');
}

function cerrarModal() {
    document.getElementById('modalAsignar').classList.remove('active');
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalAsignar').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

// Mostrar mensaje de éxito
<?php if (isset($_GET['success'])): ?>
    alert('✓ Sección asignada correctamente');
    window.location.href = 'traslado_seccion.php?tipo=<?php echo $filtro_tipo; ?>';
<?php endif; ?>
</script>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
