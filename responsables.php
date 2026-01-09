<?php
session_start();
$pageTitle = 'Equipo de Responsables';
include 'includes/header.php';
require_once 'config/database.php';

// Simular usuario logueado (en producción vendría de la sesión)
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'ADMINISTRADOR';

// Obtener todos los usuarios
$usuarios = fetchAll("SELECT * FROM usuarios ORDER BY created_at DESC");

// Procesar creación de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nombre_completo = $_POST['nombre_completo'];
    $rol = $_POST['rol'];
    
    insert(
        "INSERT INTO usuarios (username, password, nombre_completo, rol, created_at) VALUES (?, ?, ?, ?, NOW())",
        [$username, $password, $nombre_completo, $rol]
    );
    
    // Registrar en auditoría
    $nuevo_usuario_id = fetchOne("SELECT LAST_INSERT_ID() as id")['id'];
    insert(
        "INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_nuevos, ip_address, created_at) 
         VALUES ('usuarios', ?, 'INSERT', ?, ?, ?, NOW())",
        [$nuevo_usuario_id, $_SESSION['usuario_id'], json_encode(['username' => $username, 'rol' => $rol]), $_SERVER['REMOTE_ADDR']]
    );
    
    header("Location: responsables.php?success=1");
    exit;
}

// Procesar eliminación
if (isset($_GET['eliminar']) && $_GET['eliminar'] != 1) {
    $id = $_GET['eliminar'];
    query("UPDATE usuarios SET activo = 0 WHERE id = ?", [$id]);
    
    // Auditoría
    insert(
        "INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, ip_address, created_at) 
         VALUES ('usuarios', ?, 'DELETE', ?, ?, NOW())",
        [$id, $_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR']]
    );
    
    header("Location: responsables.php?deleted=1");
    exit;
}
?>

<div>
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.5rem;">EQUIPO DE</h2>
        <h3 style="color: #8b5cf6; font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;">RESPONSABLES</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">PERSONAL AUTORIZADO PARA EL PROCESO 2026</p>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-bottom: 2rem;">
        <button onclick="document.getElementById('modal-crear').style.display='flex'" class="btn" style="background: #1e293b; color: white; display: flex; align-items: center; gap: 0.5rem;">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            AGREGAR RESPONSABLE
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div style="background: #d1fae5; border: 1px solid #10b981; color: #047857; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 700;">
            ✓ Usuario creado exitosamente
        </div>
    <?php endif; ?>

    <!-- Lista de Usuarios -->
    <div style="display: grid; gap: 1.5rem;">
        <?php foreach ($usuarios as $usuario): ?>
        <div style="background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <div style="width: 60px; height: 60px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; color: var(--primary);">
                    <?php echo strtoupper(substr($usuario['nombre_completo'], 0, 2)); ?>
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 0.25rem;"><?php echo $usuario['nombre_completo']; ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem;">@<?php echo $usuario['username']; ?></p>
                    <span style="background: <?php echo $usuario['rol'] == 'ADMINISTRADOR' ? '#4f46e5' : ($usuario['rol'] == 'SECRETARIA' ? '#10b981' : '#f59e0b'); ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.7rem; font-weight: 700;">
                        <?php echo $usuario['rol']; ?>
                    </span>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="text-align: right;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">REGISTROS</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">
                        <?php 
                        $total_registros = fetchOne("SELECT COUNT(*) as total FROM auditoria WHERE usuario_id = ?", [$usuario['id']]);
                        echo $total_registros['total'];
                        ?>
                    </div>
                </div>
                
                <?php if ($usuario['id'] != 1): ?>
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="if(confirm('¿Desactivar este usuario?')) window.location.href='?eliminar=<?php echo $usuario['id']; ?>'" class="action-btn" style="background: #fee2e2; color: #dc2626;">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div id="modal-crear" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 24px; padding: 3rem; max-width: 500px; width: 90%;">
        <h3 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 2rem; color: var(--primary);">CREAR NUEVO USUARIO</h3>
        
        <form method="POST">
            <input type="hidden" name="crear_usuario" value="1">
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label required">NOMBRE COMPLETO</label>
                <input type="text" name="nombre_completo" class="form-control" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label required">NOMBRE DE USUARIO</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label required">CONTRASEÑA</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label required">ROL</label>
                <select name="rol" class="form-control" required>
                    <option value="">Seleccionar rol...</option>
                    <option value="ADMINISTRADOR">ADMINISTRADOR</option>
                    <option value="SECRETARIA">SECRETARIA</option>
                    <option value="DOCENTE">DOCENTE</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="button" onclick="document.getElementById('modal-crear').style.display='none'" class="btn btn-secondary" style="flex: 1;">CANCELAR</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">CREAR USUARIO</button>
            </div>
        </form>
    </div>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
