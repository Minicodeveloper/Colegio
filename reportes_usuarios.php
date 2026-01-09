<?php
session_start();
$pageTitle = 'Reportes de Usuarios';
require_once 'config/database.php';
include 'includes/header.php';

$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'ADMINISTRADOR';

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$usuario_filtro = $_GET['usuario'] ?? '';

// Obtener estadísticas por usuario
$sql_stats = "
    SELECT 
        u.id,
        u.username,
        u.nombre_completo,
        u.rol,
        COUNT(CASE WHEN a.tabla = 'estudiantes' AND a.accion = 'INSERT' AND DATE(a.created_at) BETWEEN ? AND ? THEN 1 END) as matriculas_nuevas,
        COUNT(CASE WHEN a.tabla = 'estudiantes' AND a.accion = 'RATIFICACION_COMPLETA' AND DATE(a.created_at) BETWEEN ? AND ? THEN 1 END) as ratificaciones,
        COUNT(CASE WHEN DATE(a.created_at) BETWEEN ? AND ? THEN 1 END) as total_acciones
    FROM usuarios u
    LEFT JOIN auditoria a ON u.id = a.usuario_id
    WHERE u.activo = 1
";

if ($usuario_filtro) {
    $sql_stats .= " AND u.id = ?";
    $params = [$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin, $usuario_filtro];
} else {
    $params = [$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin];
}

$sql_stats .= " GROUP BY u.id ORDER BY total_acciones DESC";

try {
    $estadisticas = fetchAll($sql_stats, $params);
} catch (Exception $e) {
    // Si la tabla auditoria no existe, crear datos de ejemplo
    $estadisticas = [];
}

// Obtener todos los usuarios para el filtro
$usuarios = fetchAll("SELECT id, username, nombre_completo FROM usuarios WHERE activo = 1 ORDER BY nombre_completo");

// Calcular totales
$total_matriculas = array_sum(array_column($estadisticas, 'matriculas_nuevas'));
$total_ratificaciones = array_sum(array_column($estadisticas, 'ratificaciones'));
$total_acciones = array_sum(array_column($estadisticas, 'total_acciones'));
?>

<div>
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.5rem;">REPORTES DE ACTIVIDAD</h2>
        <p style="color: var(--text-muted);">Monitorea la productividad y actividad de cada usuario</p>
    </div>

    <!-- Filtros -->
    <div class="form-card" style="margin-bottom: 2rem;">
        <form method="GET" class="form-grid">
            <div class="form-group">
                <label class="form-label">FECHA INICIO</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">FECHA FIN</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">USUARIO</label>
                <select name="usuario" class="form-control">
                    <option value="">TODOS LOS USUARIOS</option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo $usuario_filtro == $u['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['nombre_completo']); ?> (<?php echo $u['username']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    FILTRAR
                </button>
            </div>
        </form>
    </div>

    <!-- Tarjetas de Resumen -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); border-radius: 16px; padding: 1.5rem; color: white;">
            <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem;">TOTAL MATRÍCULAS</div>
            <div style="font-size: 2.5rem; font-weight: 800;"><?php echo $total_matriculas; ?></div>
            <div style="font-size: 0.75rem; opacity: 0.8;">Nuevas matrículas creadas</div>
        </div>
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 16px; padding: 1.5rem; color: white;">
            <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem;">TOTAL RATIFICACIONES</div>
            <div style="font-size: 2.5rem; font-weight: 800;"><?php echo $total_ratificaciones; ?></div>
            <div style="font-size: 0.75rem; opacity: 0.8;">Ratificaciones completadas</div>
        </div>
        <div style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border-radius: 16px; padding: 1.5rem; color: white;">
            <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem;">TOTAL ACCIONES</div>
            <div style="font-size: 2.5rem; font-weight: 800;"><?php echo $total_acciones; ?></div>
            <div style="font-size: 0.75rem; opacity: 0.8;">Todas las operaciones</div>
        </div>
    </div>

    <!-- Tabla de Estadísticas por Usuario -->
    <div class="form-card">
        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.5rem;">ACTIVIDAD POR USUARIO</h3>
        
        <?php if (empty($estadisticas)): ?>
        <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
            <svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" stroke-width="2" fill="none" style="margin: 0 auto 1rem; opacity: 0.5;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <p>No hay datos de actividad para el período seleccionado</p>
            <p style="font-size: 0.85rem;">La tabla de auditoría aún no tiene registros</p>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>USUARIO</th>
                    <th>NOMBRE COMPLETO</th>
                    <th>ROL</th>
                    <th style="text-align: center;">MATRÍCULAS</th>
                    <th style="text-align: center;">RATIFICACIONES</th>
                    <th style="text-align: center;">TOTAL ACCIONES</th>
                    <th style="text-align: center;">PRODUCTIVIDAD</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estadisticas as $stat): ?>
                <tr>
                    <td style="font-weight: 700;"><?php echo htmlspecialchars($stat['username']); ?></td>
                    <td><?php echo htmlspecialchars($stat['nombre_completo']); ?></td>
                    <td>
                        <span style="background: <?php 
                            echo $stat['rol'] == 'ADMINISTRADOR' ? '#ede9fe' : 
                                ($stat['rol'] == 'SECRETARIA' ? '#d1fae5' : 
                                ($stat['rol'] == 'OPERADOR' ? '#dbeafe' : '#fef3c7')); 
                        ?>; color: <?php 
                            echo $stat['rol'] == 'ADMINISTRADOR' ? '#8b5cf6' : 
                                ($stat['rol'] == 'SECRETARIA' ? '#10b981' : 
                                ($stat['rol'] == 'OPERADOR' ? '#2563eb' : '#f59e0b')); 
                        ?>; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                            <?php echo $stat['rol']; ?>
                        </span>
                    </td>
                    <td style="text-align: center; font-weight: 700; color: #8b5cf6;">
                        <?php echo $stat['matriculas_nuevas']; ?>
                    </td>
                    <td style="text-align: center; font-weight: 700; color: #10b981;">
                        <?php echo $stat['ratificaciones']; ?>
                    </td>
                    <td style="text-align: center; font-weight: 700; color: #2563eb;">
                        <?php echo $stat['total_acciones']; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php 
                        $productividad = $stat['matriculas_nuevas'] + $stat['ratificaciones'];
                        $color = $productividad >= 10 ? '#10b981' : ($productividad >= 5 ? '#f59e0b' : '#ef4444');
                        ?>
                        <div style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>; padding: 0.5rem; border-radius: 8px; font-weight: 700;">
                            <?php echo $productividad >= 10 ? '🔥 EXCELENTE' : ($productividad >= 5 ? '👍 BUENO' : '📊 BAJO'); ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Botón Exportar -->
    <div style="margin-top: 2rem; text-align: right;">
        <button onclick="window.print()" class="btn" style="background: #10b981; color: white;">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            EXPORTAR REPORTE
        </button>
    </div>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
