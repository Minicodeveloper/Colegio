<?php
session_start();
$pageTitle = 'Configuración del Sistema';
include 'includes/header.php';
require_once 'config/database.php';

$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'ADMINISTRADOR';

// Obtener permisos por rol
$permisos_admin = fetchAll("SELECT * FROM permisos_rol WHERE rol = 'ADMINISTRADOR' ORDER BY modulo");
$permisos_secretaria = fetchAll("SELECT * FROM permisos_rol WHERE rol = 'SECRETARIA' ORDER BY modulo");
$permisos_docente = fetchAll("SELECT * FROM permisos_rol WHERE rol = 'DOCENTE' ORDER BY modulo");

// Actualizar permisos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_permisos'])) {
    $rol = $_POST['rol'];
    $modulo = $_POST['modulo'];
    $campo = $_POST['campo'];
    $valor = $_POST['valor'];
    
    query(
        "UPDATE permisos_rol SET $campo = ? WHERE rol = ? AND modulo = ?",
        [$valor, $rol, $modulo]
    );
    
    // Auditoría
    insert(
        "INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_nuevos, ip_address, created_at) 
         VALUES ('permisos_rol', 0, 'UPDATE', ?, ?, ?, NOW())",
        [$_SESSION['usuario_id'], json_encode(['rol' => $rol, 'modulo' => $modulo, $campo => $valor]), $_SERVER['REMOTE_ADDR']]
    );
    
    echo json_encode(['success' => true]);
    exit;
}

$modulos_info = [
    'dashboard' => ['nombre' => 'Dashboard', 'icono' => '📊'],
    'centro_datos' => ['nombre' => 'Centro de Datos', 'icono' => '💾'],
    'nueva_matricula' => ['nombre' => 'Nueva Matrícula', 'icono' => '➕'],
    'ratificacion' => ['nombre' => 'Ratificación', 'icono' => '✅'],
    'directorio' => ['nombre' => 'Directorio 2026', 'icono' => '📋'],
    'traslado' => ['nombre' => 'Traslado Sección', 'icono' => '🔄'],
    'descarga_fichas' => ['nombre' => 'Descarga Fichas', 'icono' => '📄'],
    'listas_oficiales' => ['nombre' => 'Listas Oficiales', 'icono' => '📝'],
    'reportes' => ['nombre' => 'Reportes 2026', 'icono' => '📈'],
    'responsables' => ['nombre' => 'Responsables', 'icono' => '👥'],
    'ajustes' => ['nombre' => 'Ajustes', 'icono' => '⚙️']
];
?>

<div>
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.5rem;">CONFIGURACIÓN</h2>
        <h3 style="color: #8b5cf6; font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;">DEL SISTEMA</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">PANEL DE CONTROL ADMINISTRATIVO GLOBAL 2026</p>
    </div>

    <!-- Secciones -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
        <!-- Control de Acceso -->
        <div style="background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow);">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <div style="width: 50px; height: 50px; background: #ede9fe; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #8b5cf6; font-size: 1.5rem;">
                    🔒
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800;">CONTROLES DE ACCESO (OFFICERS)</h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted);">Administración de roles y permisos por personal de acceso</p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                <?php 
                $modulos_control = ['dashboard', 'centro_datos', 'nueva_matricula', 'ratificacion', 'directorio', 'traslado', 'descarga_fichas', 'listas_oficiales', 'reportes', 'ajustes'];
                foreach ($modulos_control as $mod):
                    $permiso = fetchOne("SELECT * FROM permisos_rol WHERE rol = 'ADMINISTRADOR' AND modulo = ?", [$mod]);
                    if (!$permiso) continue;
                ?>
                <div style="background: <?php echo $permiso['puede_ver'] ? '#ede9fe' : '#f3f4f6'; ?>; padding: 1rem; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1.2rem;"><?php echo $modulos_info[$mod]['icono'] ?? '📌'; ?></span>
                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-main);"><?php echo strtoupper(str_replace('_', ' ', $mod)); ?></span>
                    </div>
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" style="color: <?php echo $permiso['puede_ver'] ? '#8b5cf6' : '#9ca3af'; ?>;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Accesos Rápidos -->
            <div style="margin-top: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <a href="usuarios.php" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; padding: 1rem; border-radius: 12px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 700; font-size: 0.85rem;">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    GESTIONAR USUARIOS
                </a>
                <a href="reportes_usuarios.php" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1rem; border-radius: 12px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 700; font-size: 0.85rem;">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M18 20V10"></path>
                        <path d="M12 20V4"></path>
                        <path d="M6 20v-6"></path>
                    </svg>
                    REPORTES ACTIVIDAD
                </a>
            </div>
        </div>

        <!-- Identidad del Centro -->
        <div style="background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow);">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <div style="width: 50px; height: 50px; background: #dbeafe; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #2563eb; font-size: 1.5rem;">
                    🏫
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800;">IDENTIDAD DEL CENTRO</h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted);">Información institucional</p>
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label">NOMBRE DE LA INSTITUCIÓN</label>
                <input type="text" class="form-control" value="IE LAS CAPULLANAS" disabled>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label">AÑO LECTIVO</label>
                <input type="text" class="form-control" value="2026" disabled>
            </div>
            
            <div class="form-group">
                <label class="form-label">LOGO DEL SISTEMA</label>
                <button class="btn" style="background: #f3f4f6; color: var(--text-muted); width: 100%; height: 45px;">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    SUBIR LOGO
                </button>
            </div>
        </div>
    </div>

    <!-- Sincronizar Equipo -->
    <div style="background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow); margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="width: 50px; height: 50px; background: #d1fae5; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1.5rem;">
                🔄
            </div>
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 800;">SINCRONIZAR EQUIPO</h3>
                <p style="font-size: 0.75rem; color: var(--text-muted);">Importa sus usuarios, alumnos y configuración de otros dispositivos de office sin perderlos</p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <button class="btn" style="background: #10b981; color: white; height: 55px; display: flex; align-items: center; justify-content: center; gap: 0.75rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                EXPORTAR TODO (.JSON)
            </button>
            <button class="btn" style="background: #1e293b; color: white; height: 55px; display: flex; align-items: center; justify-content: center; gap: 0.75rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                IMPORTAR DESDE ARCHIVO
            </button>
        </div>
    </div>

    <!-- Permisos Detallados por Rol -->
    <div style="background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow);">
        <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 2rem; color: var(--primary);">MATRIZ DE PERMISOS POR ROL</h3>
        
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>MÓDULO</th>
                        <th style="text-align: center;">VER</th>
                        <th style="text-align: center;">CREAR</th>
                        <th style="text-align: center;">EDITAR</th>
                        <th style="text-align: center;">ELIMINAR</th>
                        <th style="text-align: center;">EXPORTAR</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: #ede9fe;">
                        <td colspan="6" style="font-weight: 800; color: #8b5cf6;">ADMINISTRADOR</td>
                    </tr>
                    <?php foreach ($permisos_admin as $p): ?>
                    <tr>
                        <td style="font-weight: 700;"><?php echo $modulos_info[$p['modulo']]['nombre'] ?? strtoupper($p['modulo']); ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_ver'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_crear'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_editar'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_eliminar'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_exportar'] ? '✅' : '❌'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr style="background: #d1fae5;">
                        <td colspan="6" style="font-weight: 800; color: #10b981;">SECRETARIA</td>
                    </tr>
                    <?php foreach ($permisos_secretaria as $p): ?>
                    <tr>
                        <td style="font-weight: 700;"><?php echo $modulos_info[$p['modulo']]['nombre'] ?? strtoupper($p['modulo']); ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_ver'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_crear'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_editar'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_eliminar'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_exportar'] ? '✅' : '❌'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr style="background: #fef3c7;">
                        <td colspan="6" style="font-weight: 800; color: #f59e0b;">DOCENTE</td>
                    </tr>
                    <?php foreach ($permisos_docente as $p): ?>
                    <tr>
                        <td style="font-weight: 700;"><?php echo $modulos_info[$p['modulo']]['nombre'] ?? strtoupper($p['modulo']); ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_ver'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_crear'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_editar'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_eliminar'] ? '✅' : '❌'; ?></td>
                        <td style="text-align: center;"><?php echo $p['puede_exportar'] ? '✅' : '❌'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
