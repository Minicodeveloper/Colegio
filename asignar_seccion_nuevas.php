<?php
session_start();
$pageTitle = 'Asignar Sección - Alumnas Nuevas';
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
         VALUES ('estudiantes', ?, 'ASIGNACION_SECCION_NUEVA', ?, ?, ?, NOW())",
        [$estudiante_id, $_SESSION['usuario_id'], json_encode(['seccion' => $nueva_seccion]), $_SERVER['REMOTE_ADDR']]
    );
    
    header("Location: asignar_seccion_nuevas.php?success=1");
    exit;
}

// Filtros
$filtro_nivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$filtro_grado = isset($_GET['grado']) ? $_GET['grado'] : '';

// Construir consulta - SOLO ALUMNAS NUEVAS
$where = ["e.seccion = 'ALUMNA NUEVA'"];
$params = [];

if ($filtro_nivel) {
    $where[] = "e.nivel = ?";
    $params[] = $filtro_nivel;
}

if ($filtro_grado) {
    $where[] = "e.grado = ?";
    $params[] = $filtro_grado;
}

$where_clause = 'WHERE ' . implode(' AND ', $where);

$estudiantes = fetchAll(
    "SELECT e.*, CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo 
     FROM estudiantes e 
     $where_clause
     ORDER BY e.nivel, e.grado, e.apellido_paterno, e.apellido_materno",
    $params
);

// Contar total de alumnas nuevas
$total_nuevas = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE seccion = 'ALUMNA NUEVA'")['total'];

// Contar por nivel
$count_inicial = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE seccion = 'ALUMNA NUEVA' AND nivel = 'INICIAL'")['total'];
$count_primaria = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE seccion = 'ALUMNA NUEVA' AND nivel = 'PRIMARIA'")['total'];
$count_secundaria = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE seccion = 'ALUMNA NUEVA' AND nivel = 'SECUNDARIA'")['total'];

include 'includes/header.php';
?>

<style>
.header-banner {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.header-banner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.header-banner h1 {
    font-size: 2rem;
    font-weight: 800;
    margin: 0 0 0.5rem 0;
    position: relative;
    z-index: 2;
}

.header-banner p {
    font-size: 1.1rem;
    margin: 0;
    opacity: 0.9;
    position: relative;
    z-index: 2;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    text-align: center;
    transition: all 0.3s;
}

.stat-card:hover {
    border-color: #fbbf24;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    color: #f59e0b;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.85rem;
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
    background: #fffbeb;
    padding: 1rem;
    text-align: left;
    font-weight: 700;
    color: #92400e;
    font-size: 0.85rem;
    text-transform: uppercase;
    border-bottom: 2px solid #fef3c7;
}

.student-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.student-table tr:hover {
    background: #fffbeb;
}

.badge-nueva {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 700;
    background: #fef3c7;
    color: #92400e;
}

.btn-asignar {
    background: #f59e0b;
    color: white;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-asignar:hover {
    background: #d97706;
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
    color: #f59e0b;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.empty-state svg {
    width: 80px;
    height: 80px;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.empty-state p {
    font-size: 1rem;
}
</style>

<!-- Header Banner -->
<div class="header-banner">
    <h1>🆕 ASIGNACIÓN DE SECCIONES - ALUMNAS NUEVAS</h1>
    <p>Gestiona la asignación de secciones para las alumnas de nueva matrícula 2026</p>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo $total_nuevas; ?></div>
        <div class="stat-label">Total Alumnas Nuevas</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number"><?php echo $count_inicial; ?></div>
        <div class="stat-label">Inicial</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number"><?php echo $count_primaria; ?></div>
        <div class="stat-label">Primaria</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-number"><?php echo $count_secundaria; ?></div>
        <div class="stat-label">Secundaria</div>
    </div>
</div>

<!-- Filtros -->
<div class="filters-bar">
    <form method="GET" class="filters-grid">
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
            <a href="asignar_seccion_nuevas.php" class="btn btn-secondary" style="width: 100%;">Limpiar Filtros</a>
        </div>
    </form>
</div>

<!-- Tabla de Estudiantes -->
<div class="student-table">
    <?php if (empty($estudiantes)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            <h3>¡Excelente!</h3>
            <p>No hay alumnas nuevas pendientes de asignación de sección</p>
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>DNI</th>
                <th>Apellidos y Nombres</th>
                <th>Nivel</th>
                <th>Grado</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $est): ?>
            <tr>
                <td><strong><?php echo $est['dni']; ?></strong></td>
                <td><?php echo $est['nombre_completo']; ?></td>
                <td><?php echo $est['nivel']; ?></td>
                <td><?php echo $est['grado']; ?></td>
                <td>
                    <span class="badge-nueva">🆕 ALUMNA NUEVA</span>
                </td>
                <td>
                    <button class="btn-asignar" onclick="abrirModal(<?php echo $est['id']; ?>, '<?php echo addslashes($est['nombre_completo']); ?>', '<?php echo $est['nivel']; ?>', '<?php echo $est['grado']; ?>')">
                        Asignar Sección
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Modal de Asignación -->
<div id="modalAsignar" class="modal">
    <div class="modal-content">
        <h2 class="modal-title">🎯 Asignar Sección</h2>
        <form method="POST">
            <input type="hidden" name="estudiante_id" id="modal_estudiante_id">
            <input type="hidden" name="asignar_seccion" value="1">
            
            <div class="form-group">
                <label class="form-label">Estudiante</label>
                <input type="text" id="modal_estudiante_nombre" class="form-control" disabled style="font-weight: 700; color: #1f2937;">
            </div>
            
            <div class="form-group">
                <label class="form-label">Nivel y Grado</label>
                <input type="text" id="modal_nivel_grado" class="form-control" disabled>
            </div>
            
            <div class="form-group">
                <label class="form-label required">Sección a Asignar</label>
                <select name="nueva_seccion" class="form-control" required style="font-size: 1.2rem; font-weight: 700; color: #f59e0b;">
                    <option value="">-- Seleccione la Sección --</option>
                    <option value="A">Sección A</option>
                    <option value="B">Sección B</option>
                    <option value="C">Sección C</option>
                    <option value="D">Sección D</option>
                    <option value="E">Sección E</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="background: #f59e0b;">✓ Asignar Sección</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModal(id, nombre, nivel, grado) {
    document.getElementById('modal_estudiante_id').value = id;
    document.getElementById('modal_estudiante_nombre').value = nombre;
    document.getElementById('modal_nivel_grado').value = nivel + ' - ' + grado;
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
    const Toast = {
        show: function(message) {
            const toast = document.createElement('div');
            toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 1rem 1.5rem; border-radius: 8px; font-weight: 600; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    };
    Toast.show('✓ Sección asignada correctamente');
    setTimeout(() => window.location.href = 'asignar_seccion_nuevas.php', 1500);
<?php endif; ?>
</script>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
