<?php
session_start();
$pageTitle = 'Gestión de Usuarios';
require_once 'config/database.php';
include 'includes/header.php';

$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'ADMINISTRADOR';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_usuario'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $nombre_completo = $_POST['nombre_completo'];
        $rol = $_POST['rol'];
        
        try {
            $usuario_id = insert(
                "INSERT INTO usuarios (username, password, nombre_completo, rol, activo, created_at) 
                 VALUES (?, ?, ?, ?, 1, NOW())",
                [$username, $password, $nombre_completo, $rol]
            );
            
            // Crear permisos personalizados si se especificaron
            if (isset($_POST['permisos'])) {
                foreach ($_POST['permisos'] as $modulo => $perms) {
                    insert(
                        "INSERT INTO permisos_usuario (usuario_id, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar, puede_exportar) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [
                            $usuario_id,
                            $modulo,
                            isset($perms['ver']) ? 1 : 0,
                            isset($perms['crear']) ? 1 : 0,
                            isset($perms['editar']) ? 1 : 0,
                            isset($perms['eliminar']) ? 1 : 0,
                            isset($perms['exportar']) ? 1 : 0
                        ]
                    );
                }
            }
            
            $mensaje = "Usuario creado exitosamente";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error al crear usuario: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
    
    if (isset($_POST['toggle_usuario'])) {
        $id = $_POST['usuario_id'];
        $activo = $_POST['activo'] == '1' ? 0 : 1;
        
        query("UPDATE usuarios SET activo = ? WHERE id = ?", [$activo, $id]);
        $mensaje = $activo ? "Usuario activado" : "Usuario desactivado";
        $tipo_mensaje = "success";
    }
}

// Obtener todos los usuarios
$usuarios = fetchAll("SELECT * FROM usuarios ORDER BY created_at DESC");

// Módulos disponibles
$modulos = [
    'dashboard' => 'Dashboard',
    'centro_datos' => 'Centro de Datos',
    'nueva_matricula' => 'Nueva Matrícula',
    'ratificacion' => 'Ratificación',
    'directorio' => 'Directorio 2026',
    'traslado' => 'Traslado Sección',
    'descarga_fichas' => 'Descarga Fichas',
    'listas_oficiales' => 'Listas Oficiales',
    'reportes' => 'Reportes 2026',
    'responsables' => 'Responsables',
    'ajustes' => 'Ajustes'
];
?>

<div>
    <?php if (isset($mensaje)): ?>
    <div style="background: <?php echo $tipo_mensaje == 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $tipo_mensaje == 'success' ? '#065f46' : '#991b1b'; ?>; padding: 1rem; border-radius: 12px; margin-bottom: 2rem;">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.5rem;">GESTIÓN DE USUARIOS</h2>
            <p style="color: var(--text-muted);">Administra usuarios, roles y permisos del sistema</p>
        </div>
        <button onclick="document.getElementById('modal-nuevo-usuario').style.display='flex'" class="btn btn-primary">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
            NUEVO USUARIO
        </button>
    </div>

    <!-- Lista de Usuarios -->
    <div class="form-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>USUARIO</th>
                    <th>NOMBRE COMPLETO</th>
                    <th>ROL</th>
                    <th>ESTADO</th>
                    <th>FECHA CREACIÓN</th>
                    <th style="text-align: center;">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td style="font-weight: 700;"><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                    <td>
                        <span style="background: <?php 
                            echo $u['rol'] == 'ADMINISTRADOR' ? '#ede9fe' : 
                                ($u['rol'] == 'SECRETARIA' ? '#d1fae5' : 
                                ($u['rol'] == 'OPERADOR' ? '#dbeafe' : '#fef3c7')); 
                        ?>; color: <?php 
                            echo $u['rol'] == 'ADMINISTRADOR' ? '#8b5cf6' : 
                                ($u['rol'] == 'SECRETARIA' ? '#10b981' : 
                                ($u['rol'] == 'OPERADOR' ? '#2563eb' : '#f59e0b')); 
                        ?>; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                            <?php echo $u['rol']; ?>
                        </span>
                    </td>
                    <td>
                        <span style="background: <?php echo $u['activo'] ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $u['activo'] ? '#065f46' : '#991b1b'; ?>; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                            <?php echo $u['activo'] ? 'ACTIVO' : 'INACTIVO'; ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
                    <td style="text-align: center;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                            <input type="hidden" name="activo" value="<?php echo $u['activo']; ?>">
                            <button type="submit" name="toggle_usuario" class="btn" style="background: <?php echo $u['activo'] ? '#fee2e2' : '#d1fae5'; ?>; color: <?php echo $u['activo'] ? '#991b1b' : '#065f46'; ?>; padding: 0.5rem 1rem; font-size: 0.75rem;">
                                <?php echo $u['activo'] ? 'DESACTIVAR' : 'ACTIVAR'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuevo Usuario -->
<div id="modal-nuevo-usuario" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 20px; padding: 2rem; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h3 style="font-size: 1.5rem; font-weight: 800;">CREAR NUEVO USUARIO</h3>
            <button onclick="document.getElementById('modal-nuevo-usuario').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">×</button>
        </div>
        
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label required">NOMBRE DE USUARIO</label>
                    <input type="text" name="username" class="form-control" required placeholder="ej: maria.lopez">
                </div>
                <div class="form-group">
                    <label class="form-label required">CONTRASEÑA</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group full-width">
                    <label class="form-label required">NOMBRE COMPLETO</label>
                    <input type="text" name="nombre_completo" class="form-control" required>
                </div>
                <div class="form-group full-width">
                    <label class="form-label required">ROL</label>
                    <select name="rol" class="form-control" required id="rol_select">
                        <option value="">-- SELECCIONE --</option>
                        <option value="ADMINISTRADOR">ADMINISTRADOR (Acceso Total)</option>
                        <option value="SECRETARIA">SECRETARIA (Matrícula, Ratificación, Reportes)</option>
                        <option value="OPERADOR">OPERADOR (Solo Matrícula y Ratificación)</option>
                        <option value="DOCENTE">DOCENTE (Solo Lectura)</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">PERMISOS PERSONALIZADOS (OPCIONAL)</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">Deja vacío para usar permisos por defecto del rol</p>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <?php foreach ($modulos as $key => $nombre): ?>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                        <div style="font-weight: 700; margin-bottom: 0.5rem;"><?php echo $nombre; ?></div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <label style="font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                                <input type="checkbox" name="permisos[<?php echo $key; ?>][ver]" value="1">
                                Ver
                            </label>
                            <label style="font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                                <input type="checkbox" name="permisos[<?php echo $key; ?>][crear]" value="1">
                                Crear
                            </label>
                            <label style="font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                                <input type="checkbox" name="permisos[<?php echo $key; ?>][editar]" value="1">
                                Editar
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="button" onclick="document.getElementById('modal-nuevo-usuario').style.display='none'" class="btn btn-secondary" style="flex: 1;">
                    CANCELAR
                </button>
                <button type="submit" name="crear_usuario" class="btn btn-primary" style="flex: 1;">
                    CREAR USUARIO
                </button>
            </div>
        </form>
    </div>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
