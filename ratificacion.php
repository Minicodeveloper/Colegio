<?php
session_start();
$pageTitle = 'Módulo de Ratificación 2026';
require_once 'config/database.php';

// Handler para Búsqueda AJAX en tiempo real
if (isset($_GET['ajax_search'])) {
    $search = $_GET['search'] ?? '';
    if (strlen($search) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $estudiantes = fetchAll(
        "SELECT dni, CONCAT(apellido_paterno, ' ', apellido_materno, ', ', nombres) as nombre_completo, grado 
         FROM estudiantes 
         WHERE dni LIKE ? OR apellido_paterno LIKE ? OR apellido_materno LIKE ?
         LIMIT 10",
        ["%$search%", "%$search%", "%$search%"]
    );
    header('Content-Type: application/json');
    echo json_encode($estudiantes);
    exit;
}

include 'includes/header.php';

$_SESSION['usuario_id'] = 1;

// Búsqueda
$search = isset($_GET['search']) ? $_GET['search'] : '';
$selected_dni = isset($_GET['dni']) ? $_GET['dni'] : '';

$estudiante_seleccionado = null;
if ($selected_dni) {
    $estudiante_seleccionado = fetchOne(
        "SELECT e.*, CONCAT(e.apellido_paterno, ' ', e.apellido_materno, ', ', e.nombres) as nombre_completo 
         FROM estudiantes e WHERE e.dni = ?", 
        [$selected_dni]
    );
}

// Procesar actualización (Final o Parcial)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = $_POST['dni'];
    $estudiante_id = fetchOne("SELECT id FROM estudiantes WHERE dni = ?", [$dni])['id'];
    $current_step = $_POST['current_step'] ?? 'final';
    $accion = $_POST['accion'] ?? '';
    
    // 1. Guardar Datos de Estudiante (Paso: estudiante)
    if ($current_step == 'estudiante' || $accion == 'actualizar') {
        // Combinar día, mes y año en formato YYYY-MM-DD
        if (isset($_POST['dia_nacimiento']) && isset($_POST['mes_nacimiento']) && isset($_POST['anio_nacimiento'])) {
            $fecha_nacimiento = $_POST['anio_nacimiento'] . '-' . $_POST['mes_nacimiento'] . '-' . $_POST['dia_nacimiento'];
        } else {
            // Fallback para compatibilidad con formato antiguo
            $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
            if (strpos($fecha_nacimiento, '/') !== false) {
                $partes = explode('/', $fecha_nacimiento);
                if (count($partes) == 3) {
                    $fecha_nacimiento = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
                }
            }
        }

        
        query(
            "UPDATE estudiantes SET 
             nombres = ?, apellido_paterno = ?, apellido_materno = ?, 
             fecha_nacimiento = ?, direccion = ?, nivel = ?, grado = ?, seccion = ?,
             updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$_POST['nombres'], $_POST['apellido_paterno'], $_POST['apellido_materno'],
             $fecha_nacimiento, $_POST['direccion'], $_POST['nivel'], 
             $_POST['grado'], $_POST['seccion'], $_SESSION['usuario_id'], $estudiante_id]
        );
    }

    // 2. Guardar Salud (Paso: salud)
    if ($current_step == 'salud' || $accion == 'actualizar') {
        $existe_salud = fetchOne("SELECT id FROM salud_estudiantes WHERE estudiante_id = ?", [$estudiante_id]);
        $tiene_discapacidad = isset($_POST['tiene_discapacidad']) ? 1 : 0;

        if ($existe_salud) {
            query(
                "UPDATE salud_estudiantes SET 
                 tiene_carnet_vacunacion = ?, dosis_covid = ?, peso_kg = ?, talla_cm = ?, 
                 seguro_salud = ?, grupo_sanguineo = ?, detalle_alergias = ?,
                 tiene_discapacidad = ?, detalle_discapacidad = ?
                 WHERE estudiante_id = ?",
                [$_POST['tiene_carnet_vacunacion'] ?? 0, $_POST['dosis_covid'] ?? 0, $_POST['peso_kg'] ?? 0, $_POST['talla_cm'] ?? 0,
                 $_POST['seguro_salud'] ?? '', $_POST['grupo_sanguineo'] ?? '', $_POST['detalle_alergias'] ?? '',
                 $tiene_discapacidad, $_POST['detalle_discapacidad'] ?? '',
                 $estudiante_id]
            );
        } else {
            insert(
                "INSERT INTO salud_estudiantes (estudiante_id, tiene_carnet_vacunacion, dosis_covid, peso_kg, talla_cm, seguro_salud, grupo_sanguineo, detalle_alergias, tiene_discapacidad, detalle_discapacidad)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$estudiante_id, $_POST['tiene_carnet_vacunacion'] ?? 0, $_POST['dosis_covid'] ?? 0, $_POST['peso_kg'] ?? 0, $_POST['talla_cm'] ?? 0,
                 $_POST['seguro_salud'] ?? '', $_POST['grupo_sanguineo'] ?? '', $_POST['detalle_alergias'] ?? '',
                 $tiene_discapacidad, $_POST['detalle_discapacidad'] ?? '']
            );
        }
    }

    // 3. Guardar Familia (Paso: familia)
    if ($current_step == 'familia' || $accion == 'actualizar') {
        // Guardar Representantes
        if (isset($_POST['representantes']) && is_array($_POST['representantes'])) {
            foreach ($_POST['representantes'] as $rep_id => $data) {
                // Actualizar existentes
                if (is_numeric($rep_id)) {
                    query(
                        "UPDATE representantes SET dni=?, apellido_paterno=?, apellido_materno=?, nombres=?, celular=?, whatsapp=?, direccion=? WHERE id=?",
                        [$data['dni'], $data['paterno'], $data['materno'], $data['nombres'], $data['celular'], $data['whatsapp'], $data['direccion'] ?? '', $rep_id]
                    );
                }
            }
        }
        
        // Guardar Contacto Emergencia
        if (!empty($_POST['emergencia_nombre'])) {
            $existe_emergencia = fetchOne("SELECT id FROM contactos_emergencia WHERE estudiante_id = ?", [$estudiante_id]);
            if ($existe_emergencia) {
                query("UPDATE contactos_emergencia SET nombre_completo=?, parentesco=?, celular=? WHERE id=?", 
                      [$_POST['emergencia_nombre'], $_POST['emergencia_parentesco'], $_POST['emergencia_celular'], $existe_emergencia['id']]);
            } else {
                insert("INSERT INTO contactos_emergencia (estudiante_id, nombre_completo, parentesco, celular) VALUES (?, ?, ?, ?)",
                       [$estudiante_id, $_POST['emergencia_nombre'], $_POST['emergencia_parentesco'], $_POST['emergencia_celular']]);
            }
        }

        // Guardar Hermanas (Lógica simple: borrar e insertar o manejo por separado, aquí solo capturamos si se envía algo básico)
        // Para simplificar, asumiremos que se maneja visualmente o se agrega futuramente.
    }

    // 4. Guardar Sacramentos (Paso: sacramentos)
    if ($current_step == 'sacramentos' || $accion == 'actualizar') {
        $existe_sacramento = fetchOne("SELECT id FROM sacramentos WHERE estudiante_id = ?", [$estudiante_id]);
        $bautismo = isset($_POST['bautismo']) ? 1 : 0;
        $comunion = isset($_POST['primera_comunion']) ? 1 : 0;
        $confirmacion = isset($_POST['confirmacion']) ? 1 : 0;
        $matrimonio = $_POST['estado_matrimonio_padres'] ?? 'NINGUNO';

        if ($existe_sacramento) {
            query(
                "UPDATE sacramentos SET bautismo = ?, primera_comunion = ?, confirmacion = ?, estado_matrimonio_padres = ? WHERE estudiante_id = ?",
                [$bautismo, $comunion, $confirmacion, $matrimonio, $estudiante_id]
            );
        } else {
            insert(
                "INSERT INTO sacramentos (estudiante_id, bautismo, primera_comunion, confirmacion, estado_matrimonio_padres) VALUES (?, ?, ?, ?, ?)",
                [$estudiante_id, $bautismo, $comunion, $confirmacion, $matrimonio]
            );
        }
    }
    
    // Redirección
    if ($accion == 'actualizar') {
        // Auditoría Final
        insert(
            "INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_nuevos, ip_address, created_at) 
             VALUES ('estudiantes', ?, 'RATIFICACION_COMPLETA', ?, ?, ?, NOW())",
            [$estudiante_id, $_SESSION['usuario_id'], json_encode($_POST), $_SERVER['REMOTE_ADDR']]
        );
        header("Location: success.php?dni=" . $dni);
        exit;
    } elseif ($accion == 'guardar_y_seguir') {
        $next = ['estudiante' => 'salud', 'salud' => 'familia', 'familia' => 'sacramentos', 'sacramentos' => 'documentos', 'documentos' => 'final'];
        $next_step = $next[$current_step];
        header("Location: ratificacion.php?dni=" . $dni . "&step=" . $next_step);
        exit;
    }
}

// Si no hay estudiante seleccionado, mostrar búsqueda
if (!$estudiante_seleccionado) {
?>

<div class="form-card" style="max-width: 800px; margin: 2rem auto; text-align: center;">
    <div style="width: 80px; height: 80px; background: #eef2ff; border-radius: 20px; display: flex; align-items: center; justify-content: center; color: var(--primary); margin: 0 auto 2rem;">
        <svg viewBox="0 0 24 24" width="40" height="40" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    </div>
    
    <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;">MÓDULO DE RATIFICACIÓN 2026</h2>
    <p style="color: var(--text-muted); margin-bottom: 3rem;">INGRESE EL DNI O APELLIDOS DE LA ALUMNA</p>
    
    <form method="GET" class="search-input-container" style="position: relative;">
        <input type="text" id="liveSearchInput" name="search" class="search-input" placeholder="Comience a escribir DNI o apellidos..." value="<?php echo htmlspecialchars($search); ?>" autofocus autocomplete="off">
        <button type="submit" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: var(--primary); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer;">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </button>
        <div id="liveSearchResults" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); z-index: 100; margin-top: 10px; display: none; overflow: hidden; border: 1px solid #e2e8f0; text-align: left;"></div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('liveSearchInput');
        const resultsDiv = document.getElementById('liveSearchResults');
        let timeoutId;

        if(searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(timeoutId);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                timeoutId = setTimeout(() => {
                    fetch(`ratificacion.php?ajax_search=1&search=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                let html = '';
                                data.forEach(est => {
                                    html += `
                                        <a href="?dni=${est.dni}" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: inherit; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                            <div>
                                                <div style="font-weight: 700; color: var(--text-main);">${est.nombre_completo}</div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);">DNI: ${est.dni} • ${est.grado || 'S/G'}</div>
                                            </div>
                                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="var(--primary)" stroke-width="2" fill="none"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                        </a>
                                    `;
                                });
                                resultsDiv.innerHTML = html;
                                resultsDiv.style.display = 'block';
                            } else {
                                resultsDiv.innerHTML = '<div style="padding: 1rem; color: var(--text-muted); text-align: center;">No se encontraron resultados</div>';
                                resultsDiv.style.display = 'block';
                            }
                        })
                        .catch(err => console.error('Error búsqueda:', err));
                }, 300);
            });
            
            // Cerrar al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                    resultsDiv.style.display = 'none';
                }
            });
        }
    });
    </script>

    <?php if ($search): 
        $estudiantes = fetchAll(
            "SELECT e.*, CONCAT(e.apellido_paterno, ' ', e.apellido_materno, ', ', e.nombres) as nombre_completo 
             FROM estudiantes e 
             WHERE e.dni LIKE ? OR e.apellido_paterno LIKE ? OR e.apellido_materno LIKE ?
             ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres
             LIMIT 20",
            ["%$search%", "%$search%", "%$search%"]
        );
    ?>
        <div style="margin-top: 2rem;">
            <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem;">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="display: inline; vertical-align: middle;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                ALUMNAS ENCONTRADAS (<?php echo count($estudiantes); ?>)
            </h3>
            
            <div style="text-align: left; max-width: 600px; margin: 0 auto;">
                <?php foreach ($estudiantes as $est): ?>
                    <a href="?dni=<?php echo $est['dni']; ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: #f8fafc; border-radius: 12px; margin-bottom: 0.75rem; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background='#f8fafc'">
                        <div>
                            <div style="font-weight: 700; color: var(--text-main); margin-bottom: 4px;"><?php echo $est['nombre_completo']; ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">DNI: <?php echo $est['dni']; ?> • <?php echo $est['grado']; ?></div>
                        </div>
                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" style="color: var(--primary);"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
} else {
    // Mostrar wizard con datos del estudiante
    $step = isset($_GET['step']) ? $_GET['step'] : 'estudiante';
    
    // Obtener datos relacionados
    $salud = fetchOne("SELECT * FROM salud_estudiantes WHERE estudiante_id = ?", [$estudiante_seleccionado['id']]);
    $representantes = fetchAll(
        "SELECT r.*, er.es_principal FROM representantes r 
         INNER JOIN estudiante_representante er ON r.id = er.representante_id 
         WHERE er.estudiante_id = ?",
        [$estudiante_seleccionado['id']]
    );
    $sacramentos = fetchOne("SELECT * FROM sacramentos WHERE estudiante_id = ?", [$estudiante_seleccionado['id']]);
?>

<!-- Wizard Navigation -->
<div class="wizard-nav">
    <a href="?dni=<?php echo $selected_dni; ?>&step=estudiante" class="wizard-step <?php echo $step == 'estudiante' ? 'active step-estudiante' : ''; ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        ESTUDIANTE
    </a>
    <a href="?dni=<?php echo $selected_dni; ?>&step=salud" class="wizard-step <?php echo $step == 'salud' ? 'active step-salud' : ''; ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path></svg>
        SALUD
    </a>
    <a href="?dni=<?php echo $selected_dni; ?>&step=familia" class="wizard-step <?php echo $step == 'familia' ? 'active step-familia' : ''; ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        FAMILIA
    </a>
    <a href="?dni=<?php echo $selected_dni; ?>&step=sacramentos" class="wizard-step <?php echo $step == 'sacramentos' ? 'active step-sacramentos' : ''; ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
        SACRAMENTOS
    </a>
    <a href="?dni=<?php echo $selected_dni; ?>&step=documentos" class="wizard-step <?php echo $step == 'documentos' ? 'active step-documentos' : ''; ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
        DOCUMENTOS
    </a>
    <a href="?dni=<?php echo $selected_dni; ?>&step=final" class="wizard-step <?php echo $step == 'final' ? 'active step-final' : ''; ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        FINALIZAR
    </a>
</div>

<form method="POST" class="form-card animate-fade-in">
    <input type="hidden" name="dni" value="<?php echo $estudiante_seleccionado['dni']; ?>">
    
    <?php if ($step == 'estudiante'): ?>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label required">DNI</label>
                <input type="text" name="dni_display" class="form-control" value="<?php echo $estudiante_seleccionado['dni']; ?>" disabled>
            </div>
            <div class="form-group full-width">
                <label class="form-label required">APELLIDOS Y NOMBRES</label>
                <input type="text" name="apellido_paterno" class="form-control" value="<?php echo $estudiante_seleccionado['apellido_paterno']; ?>" required placeholder="Apellido Paterno">
            </div>
            <div class="form-group">
                <label class="form-label required">APELLIDO MATERNO</label>
                <input type="text" name="apellido_materno" class="form-control" value="<?php echo $estudiante_seleccionado['apellido_materno']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">NOMBRES</label>
                <input type="text" name="nombres" class="form-control" value="<?php echo $estudiante_seleccionado['nombres']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">DÍA DE NACIMIENTO</label>
                <select name="dia_nacimiento" class="form-control" required>
                    <option value="">-- DÍA --</option>
                    <?php 
                    $dia_actual = date('d', strtotime($estudiante_seleccionado['fecha_nacimiento']));
                    for($d = 1; $d <= 31; $d++): 
                        $dia_val = str_pad($d, 2, '0', STR_PAD_LEFT);
                    ?>
                        <option value="<?php echo $dia_val; ?>" <?php echo $dia_actual == $dia_val ? 'selected' : ''; ?>><?php echo $d; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">MES DE NACIMIENTO</label>
                <select name="mes_nacimiento" class="form-control" required>
                    <option value="">-- MES --</option>
                    <?php 
                    $mes_actual = date('m', strtotime($estudiante_seleccionado['fecha_nacimiento']));
                    $meses = ['01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio', 
                              '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];
                    foreach($meses as $num => $nombre): 
                    ?>
                        <option value="<?php echo $num; ?>" <?php echo $mes_actual == $num ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">AÑO DE NACIMIENTO</label>
                <select name="anio_nacimiento" class="form-control" required>
                    <option value="">-- AÑO --</option>
                    <?php 
                    $anio_actual = date('Y', strtotime($estudiante_seleccionado['fecha_nacimiento']));
                    for($a = date('Y'); $a >= date('Y') - 20; $a--): 
                    ?>
                        <option value="<?php echo $a; ?>" <?php echo $anio_actual == $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">NIVEL EDUCATIVO</label>
                <select name="nivel" class="form-control" required>
                    <option value="INICIAL" <?php echo $estudiante_seleccionado['nivel'] == 'INICIAL' ? 'selected' : ''; ?>>INICIAL</option>
                    <option value="PRIMARIA" <?php echo $estudiante_seleccionado['nivel'] == 'PRIMARIA' ? 'selected' : ''; ?>>PRIMARIA</option>
                    <option value="SECUNDARIA" <?php echo $estudiante_seleccionado['nivel'] == 'SECUNDARIA' ? 'selected' : ''; ?>>SECUNDARIA</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">GRADO ACADÉMICO</label>
                <select name="grado" id="grado_select" class="form-control" required>
                    <option value="">-- SELECCIONE --</option>
                    <!-- Se llena con JS -->
                </select>
                <!-- Input oculto para mantener compatibilidad si falla JS o para inicializar -->
                <input type="hidden" id="grado_actual" value="<?php echo $estudiante_seleccionado['grado']; ?>">
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nivelSelect = document.querySelector('select[name="nivel"]');
            const gradoSelect = document.getElementById('grado_select');
            const gradoActual = document.getElementById('grado_actual').value;

            const gradosPorNivel = {
                'INICIAL': ['4 años', '5 años'],
                'PRIMARIA': ['1°', '2°', '3°', '4°', '5°', '6°'],
                'SECUNDARIA': ['1°', '2°', '3°', '4°', '5°']
            };

            function actualizarGrados() {
                const nivel = nivelSelect.value;
                const grados = gradosPorNivel[nivel] || [];
                
                gradoSelect.innerHTML = '<option value="">-- SELECCIONE --</option>';
                
                grados.forEach(grado => {
                    const option = document.createElement('option');
                    option.value = grado;
                    // Mostrar con detalle extra si es primaria/secundaria para claridad, o solo el grado
                    // El usuario pidió "1° A 6° PRIMARIA", así que lo mostraré así en el texto
                    let texto = grado;
                    if (nivel === 'PRIMARIA') texto += ' PRIMARIA';
                    if (nivel === 'SECUNDARIA') texto += ' SECUNDARIA';
                    
                    option.textContent = texto;
                    
                    if (grado === gradoActual) {
                        option.selected = true;
                    }
                    gradoSelect.appendChild(option);
                });
            }

            nivelSelect.addEventListener('change', actualizarGrados);
            actualizarGrados(); // Inicializar al cargar
        });

        </script>    <div class="form-group">
                <label class="form-label required">SECCIÓN 2026</label>
                <select name="seccion" class="form-control" required>
                    <option value="A" <?php echo $estudiante_seleccionado['seccion'] == 'A' ? 'selected' : ''; ?>>A</option>
                    <option value="B" <?php echo $estudiante_seleccionado['seccion'] == 'B' ? 'selected' : ''; ?>>B</option>
                    <option value="C" <?php echo $estudiante_seleccionado['seccion'] == 'C' ? 'selected' : ''; ?>>C</option>
                    <option value="D" <?php echo $estudiante_seleccionado['seccion'] == 'D' ? 'selected' : ''; ?>>D</option>
                    <option value="E" <?php echo $estudiante_seleccionado['seccion'] == 'E' ? 'selected' : ''; ?>>E</option>
                </select>
            </div>
            <div class="form-group full-width">
                <label class="form-label required">DIRECCIÓN</label>
                <input type="text" name="direccion" class="form-control" value="<?php echo $estudiante_seleccionado['direccion']; ?>" required>
            </div>
        </div>

    <?php elseif ($step == 'salud'): ?>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">ESQUEMA VACUNAS</label>
                <select name="tiene_carnet_vacunacion" class="form-control">
                    <option value="0">-- SELECCIONE --</option>
                    <option value="1" <?php echo ($salud['tiene_carnet_vacunacion'] ?? 0) ? 'selected' : ''; ?>>COMPLETO</option>
                    <option value="0" <?php echo !($salud['tiene_carnet_vacunacion'] ?? 0) ? 'selected' : ''; ?>>INCOMPLETO</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">DOSIS COVID-19</label>
                <input type="number" name="dosis_covid" class="form-control" value="<?php echo $salud['dosis_covid'] ?? ''; ?>" min="0" max="6">
            </div>
            <div class="form-group">
                <label class="form-label required">PESO ACTUAL (KG)</label>
                <input type="number" step="0.01" name="peso_kg" class="form-control" value="<?php echo $salud['peso_kg'] ?? ''; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">TALLA ACTUAL (CM)</label>
                <input type="number" step="0.01" name="talla_cm" class="form-control" value="<?php echo $salud['talla_cm'] ?? ''; ?>" required>
            </div>
            <div class="form-group full-width">
                <label class="form-label">SEGURO DE SALUD</label>
                <select name="seguro_salud" class="form-control">
                    <option value="">-- SELECCIONE --</option>
                    <option value="ESSALUD" <?php echo ($salud['seguro_salud'] ?? '') == 'ESSALUD' ? 'selected' : ''; ?>>ESSALUD</option>
                    <option value="SIS" <?php echo ($salud['seguro_salud'] ?? '') == 'SIS' ? 'selected' : ''; ?>>SIS</option>
                    <option value="PRIVADO" <?php echo ($salud['seguro_salud'] ?? '') == 'PRIVADO' ? 'selected' : ''; ?>>PRIVADO</option>
                    <option value="OTRO" <?php echo ($salud['seguro_salud'] ?? '') == 'OTRO' ? 'selected' : ''; ?>>OTRO</option>
                </select>
            </div>
            <div class="form-group full-width">
                <label class="form-label">GRUPO SANGUÍNEO</label>
                <select name="grupo_sanguineo" class="form-control">
                    <option value="">-- SELECCIONE --</option>
                    <option value="O+" <?php echo ($salud['grupo_sanguineo'] ?? '') == 'O+' ? 'selected' : ''; ?>>O+</option>
                    <option value="O-" <?php echo ($salud['grupo_sanguineo'] ?? '') == 'O-' ? 'selected' : ''; ?>>O-</option>
                    <option value="A+" <?php echo ($salud['grupo_sanguineo'] ?? '') == 'A+' ? 'selected' : ''; ?>>A+</option>
                    <option value="A-" <?php echo ($salud['grupo_sanguineo'] ?? '') == 'A-' ? 'selected' : ''; ?>>A-</option>
                    <option value="B+" <?php echo ($salud['grupo_sanguineo'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+</option>
                    <option value="B-" <?php echo ($salud['grupo_sanguineo'] ?? '') == 'B-' ? 'selected' : ''; ?>>B-</option>
                    <option value="AB+" <?php echo ($salud['grupo_sanguineo'] ?? '') == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                    <option value="AB-" <?php echo ($salud['grupo_sanguineo'] ?? '') == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                </select>
            </div>
            <div class="form-group full-width">
                <label class="form-label">¿TIENE ALGUNA DISCAPACIDAD O NECESIDAD ESPECIAL?</label>
                <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="tiene_discapacidad" value="1" <?php echo !empty($salud['tiene_discapacidad']) ? 'checked' : ''; ?> onclick="document.getElementById('detalle_discapacidad').style.display = this.checked ? 'block' : 'none'">
                        <span>Sí, presenta</span>
                    </label>
                </div>
            </div>
            <div class="form-group full-width" id="detalle_discapacidad" style="display: <?php echo !empty($salud['tiene_discapacidad']) ? 'block' : 'none'; ?>;">
                <label class="form-label">DETALLE LA DISCAPACIDAD O NECESIDAD</label>
                <input type="text" name="detalle_discapacidad" class="form-control" value="<?php echo $salud['detalle_discapacidad'] ?? ''; ?>" placeholder="Especifique...">
            </div>
            <div class="form-group full-width">
                <label class="form-label">ALERGIAS (DETALLAR)</label>
                <textarea name="detalle_alergias" class="form-control" rows="2"><?php echo $salud['detalle_alergias'] ?? ''; ?></textarea>
            </div>
        </div>

    <?php elseif ($step == 'familia'): ?>
        <?php
        $madre = null; $padre = null; $otros_reps = [];
        foreach ($representantes as $r) {
            if ($r['parentesco'] == 'MADRE') $madre = $r;
            elseif ($r['parentesco'] == 'PADRE') $padre = $r;
            else $otros_reps[] = $r;
        }
        $emergencia = fetchOne("SELECT * FROM contactos_emergencia WHERE estudiante_id = ?", [$estudiante_seleccionado['id']]);
        $rep_principal = fetchOne("SELECT parentesco FROM estudiante_representante er INNER JOIN representantes r ON er.representante_id = r.id WHERE er.estudiante_id = ? AND er.es_principal = 1", [$estudiante_seleccionado['id']]);
        ?>

        <!-- PADRE -->
        <?php if ($padre): ?>
        <div class="section-title" style="margin-top: 0; display: flex; justify-content: space-between; align-items: center;">
            <span>DATOS DEL PADRE</span>
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                <input type="radio" name="representante_legal" value="PADRE" <?php echo ($rep_principal['parentesco'] ?? '') == 'PADRE' ? 'checked' : ''; ?>>
                <span style="font-weight: 600;">REPRESENTANTE LEGAL</span>
            </label>
        </div>
        <div class="form-grid">
            <input type="hidden" name="representantes[<?php echo $padre['id']; ?>][id]" value="<?php echo $padre['id']; ?>">
            <div class="form-group">
                <label class="form-label required">DNI</label>
                <input type="text" name="representantes[<?php echo $padre['id']; ?>][dni]" class="form-control" value="<?php echo $padre['dni']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">APELLIDO PATERNO</label>
                <input type="text" name="representantes[<?php echo $padre['id']; ?>][paterno]" class="form-control" value="<?php echo $padre['apellido_paterno']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">APELLIDO MATERNO</label>
                <input type="text" name="representantes[<?php echo $padre['id']; ?>][materno]" class="form-control" value="<?php echo $padre['apellido_materno']; ?>" required>
            </div>
            <div class="form-group full-width">
                <label class="form-label required">NOMBRES</label>
                <input type="text" name="representantes[<?php echo $padre['id']; ?>][nombres]" class="form-control" value="<?php echo $padre['nombres']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">CELULAR</label>
                <input type="text" name="representantes[<?php echo $padre['id']; ?>][celular]" class="form-control" value="<?php echo $padre['celular']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">WHATSAPP</label>
                <input type="text" name="representantes[<?php echo $padre['id']; ?>][whatsapp]" class="form-control" value="<?php echo $padre['whatsapp']; ?>">
            </div>
            <div class="form-group full-width">
                <label class="form-label required">DOMICILIO ACTUAL</label>
                <input type="text" name="representantes[<?php echo $padre['id']; ?>][direccion]" class="form-control" value="<?php echo $padre['direccion']; ?>" required>
            </div>
        </div>
        <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #eee;">
        <?php endif; ?>

        <!-- MADRE -->
        <?php if ($madre): ?>
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <span>DATOS DE LA MADRE</span>
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                <input type="radio" name="representante_legal" value="MADRE" <?php echo ($rep_principal['parentesco'] ?? 'MADRE') == 'MADRE' ? 'checked' : ''; ?>>
                <span style="font-weight: 600;">REPRESENTANTE LEGAL</span>
            </label>
        </div>
        <div class="form-grid">
            <input type="hidden" name="representantes[<?php echo $madre['id']; ?>][id]" value="<?php echo $madre['id']; ?>">
            <div class="form-group">
                <label class="form-label required">DNI</label>
                <input type="text" name="representantes[<?php echo $madre['id']; ?>][dni]" class="form-control" value="<?php echo $madre['dni']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">APELLIDO PATERNO</label>
                <input type="text" name="representantes[<?php echo $madre['id']; ?>][paterno]" class="form-control" value="<?php echo $madre['apellido_paterno']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">APELLIDO MATERNO</label>
                <input type="text" name="representantes[<?php echo $madre['id']; ?>][materno]" class="form-control" value="<?php echo $madre['apellido_materno']; ?>" required>
            </div>
            <div class="form-group full-width">
                <label class="form-label required">NOMBRES</label>
                <input type="text" name="representantes[<?php echo $madre['id']; ?>][nombres]" class="form-control" value="<?php echo $madre['nombres']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">CELULAR</label>
                <input type="text" name="representantes[<?php echo $madre['id']; ?>][celular]" class="form-control" value="<?php echo $madre['celular']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">WHATSAPP</label>
                <input type="text" name="representantes[<?php echo $madre['id']; ?>][whatsapp]" class="form-control" value="<?php echo $madre['whatsapp']; ?>">
            </div>
            <div class="form-group full-width">
                <label class="form-label required">DOMICILIO ACTUAL</label>
                <input type="text" name="representantes[<?php echo $madre['id']; ?>][direccion]" class="form-control" value="<?php echo $madre['direccion']; ?>" required>
            </div>
        </div>
        <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #eee;">
        <?php endif; ?>

        <!-- APODERADO / OTROS -->
        <?php foreach ($otros_reps as $ap): ?>
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <span>DATOS DEL APODERADO / <?php echo $ap['parentesco']; ?></span>
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                <input type="radio" name="representante_legal" value="APODERADO" <?php echo ($rep_principal['parentesco'] ?? '') == 'APODERADO' || ($rep_principal['parentesco'] ?? '') == 'TUTOR' ? 'checked' : ''; ?>>
                <span style="font-weight: 600;">REPRESENTANTE LEGAL</span>
            </label>
        </div>
        <div class="form-grid">
            <input type="hidden" name="representantes[<?php echo $ap['id']; ?>][id]" value="<?php echo $ap['id']; ?>">
            <div class="form-group">
                <label class="form-label required">DNI</label>
                <input type="text" name="representantes[<?php echo $ap['id']; ?>][dni]" class="form-control" value="<?php echo $ap['dni']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">APELLIDOS</label>
                <input type="text" name="representantes[<?php echo $ap['id']; ?>][paterno]" class="form-control" value="<?php echo $ap['apellido_paterno'] . ' ' . $ap['apellido_materno']; ?>" required>
                <input type="hidden" name="representantes[<?php echo $ap['id']; ?>][materno]" value="">
            </div>
             <div class="form-group full-width">
                <label class="form-label required">NOMBRES</label>
                <input type="text" name="representantes[<?php echo $ap['id']; ?>][nombres]" class="form-control" value="<?php echo $ap['nombres']; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">CELULAR</label>
                <input type="text" name="representantes[<?php echo $ap['id']; ?>][celular]" class="form-control" value="<?php echo $ap['celular']; ?>" required>
            </div>
            <div class="form-group full-width">
                <label class="form-label required">DOMICILIO</label>
                <input type="text" name="representantes[<?php echo $ap['id']; ?>][direccion]" class="form-control" value="<?php echo $ap['direccion']; ?>" required>
            </div>
        </div>
        <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #eee;">
        <?php endforeach; ?>

        <!-- CONTACTO EMERGENCIA -->
        <div class="section-title orange">CONTACTO DE EMERGENCIA</div>
        <div class="form-grid">
            <div class="form-group full-width">
                <label class="form-label required">NOMBRE COMPLETO</label>
                <input type="text" name="emergencia_nombre" class="form-control" value="<?php echo $emergencia['nombre_completo'] ?? ''; ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">PARENTESCO</label>
                <input type="text" name="emergencia_parentesco" class="form-control" value="<?php echo $emergencia['parentesco'] ?? ''; ?>" required placeholder="Ej: Tía, Abuela">
            </div>
            <div class="form-group">
                <label class="form-label required">CELULAR</label>
                <input type="text" name="emergencia_celular" class="form-control" value="<?php echo $emergencia['celular'] ?? ''; ?>" required>
            </div>
        </div>

        <div class="section-title">HERMANAS EN LA INSTITUCIÓN</div>
        <div class="form-grid">
            <div class="form-group full-width">
               <label class="form-label">MENCIONE NOMBRES Y GRADOS (Si tiene)</label>
               <textarea name="detalle_hermanas" class="form-control" rows="2" placeholder="Ej: Maria Perez (3ro Primaria)..." style="width: 100%;"></textarea>
            </div>
        </div>
    
    <?php elseif ($step == 'sacramentos'): ?>
        <div class="section-title purple">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
            VIDA ESPIRITUAL Y CRISTIANA
        </div>
        <div class="toggle-group">
            <label class="toggle-btn">
                <input type="checkbox" name="bautismo" <?php echo ($sacramentos['bautismo'] ?? 0) ? 'checked' : ''; ?>>
                <div class="toggle-info" style="text-align: right;">
                    <div class="toggle-title">BAUTISMO</div>
                    <div class="toggle-sub">Sacramento de iniciación</div>
                </div>
            </label>
            <label class="toggle-btn">
                <input type="checkbox" name="primera_comunion" <?php echo ($sacramentos['primera_comunion'] ?? 0) ? 'checked' : ''; ?>>
                <div class="toggle-info" style="text-align: right;">
                    <div class="toggle-title">PRIMERA COMUNIÓN</div>
                    <div class="toggle-sub">Cuerpo y sangre de Cristo</div>
                </div>
            </label>
            <label class="toggle-btn">
                <input type="checkbox" name="confirmacion" <?php echo ($sacramentos['confirmacion'] ?? 0) ? 'checked' : ''; ?>>
                <div class="toggle-info" style="text-align: right;">
                    <div class="toggle-title">CONFIRMACIÓN</div>
                    <div class="toggle-sub">Reafirmación de fe</div>
                </div>
            </label>
        </div>
        
        <!-- SACRAMENTOS DE LOS PADRES -->
        <div class="form-group full-width" style="margin-top: 2.5rem;">
            <label class="form-label" style="font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 1.5rem;">SACRAMENTOS DE LOS PADRES</label>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                <!-- Bautismo Papá -->
                <div style="background: #f0f9ff; padding: 1.25rem; border-radius: 12px; border: 2px solid #e0f2fe;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 600; color: #0284c7; font-size: 0.95rem;">
                        <input type="checkbox" name="bautismo_papa" value="1" <?php echo !empty($sacramentos['bautismo_papa']) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                        <span>👨 BAUTISMO PAPÁ</span>
                    </label>
                </div>
                
                <!-- Bautismo Mamá -->
                <div style="background: #fffbeb; padding: 1.25rem; border-radius: 12px; border: 2px solid #fef3c7;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 600; color: #f59e0b; font-size: 0.95rem;">
                        <input type="checkbox" name="bautismo_mama" value="1" <?php echo !empty($sacramentos['bautismo_mama']) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                        <span>👩 BAUTISMO MAMÁ</span>
                    </label>
                </div>
                
                <!-- Matrimonio Religioso -->
                <div style="background: #f5f3ff; padding: 1.25rem; border-radius: 12px; border: 2px solid #e9d5ff;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 600; color: #9333ea; font-size: 0.95rem;">
                        <input type="checkbox" name="matrimonio_religioso" value="1" <?php echo !empty($sacramentos['matrimonio_religioso']) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                        <span>💒 MATRIMONIO RELIGIOSO</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- SITUACIÓN CONYUGAL -->
        <div class="form-group full-width">
            <label class="form-label">SITUACIÓN CONYUGAL DE LOS PADRES</label>
            <select name="estado_matrimonio_padres" class="form-control">
                <option value="NINGUNO">-- SELECCIONE --</option>
                <option value="RELIGIOSO_CIVIL" <?php echo ($sacramentos['estado_matrimonio_padres'] ?? '') == 'RELIGIOSO_CIVIL' ? 'selected' : ''; ?>>CASADOS (CIVIL Y RELIGIOSO)</option>
                <option value="SOLO_CIVIL" <?php echo ($sacramentos['estado_matrimonio_padres'] ?? '') == 'SOLO_CIVIL' ? 'selected' : ''; ?>>SOLO CIVIL</option>
                <option value="SOLO_RELIGIOSO" <?php echo ($sacramentos['estado_matrimonio_padres'] ?? '') == 'SOLO_RELIGIOSO' ? 'selected' : ''; ?>>SOLO RELIGIOSO</option>
                <option value="CONVIVIENTES" <?php echo ($sacramentos['estado_matrimonio_padres'] ?? '') == 'CONVIVIENTES' ? 'selected' : ''; ?>>CONVIVIENTES</option>
                <option value="SEPARADOS" <?php echo ($sacramentos['estado_matrimonio_padres'] ?? '') == 'SEPARADOS' ? 'selected' : ''; ?>>SEPARADOS</option>
                <option value="SOLTERO" <?php echo ($sacramentos['estado_matrimonio_padres'] ?? '') == 'SOLTERO' ? 'selected' : ''; ?>>SOLTERO(A)</option>
            </select>
        </div>

    <?php elseif ($step == 'documentos'): ?>
        <div class="section-title orange">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            CONTROL DE EXPEDIENTE FÍSICO
        </div>
        <div class="document-item">
            <label class="doc-label">
                <input type="checkbox" name="doc_boleta" class="doc-checkbox" checked onclick="return false;">
                INFORME DE LOGROS (BOLETA DE NOTAS)
            </label>
        </div>
        <div class="document-item">
            <label class="doc-label">
                <input type="checkbox" name="doc_dni_estudiante" class="doc-checkbox">
                DNI DE LA ESTUDIANTE
            </label>
        </div>
        <div class="document-item">
            <label class="doc-label">
                <input type="checkbox" name="doc_dni_papa" class="doc-checkbox">
                DNI DEL PAPÁ (Copia)
            </label>
        </div>
        <div class="document-item">
            <label class="doc-label">
                <input type="checkbox" name="doc_dni_mama" class="doc-checkbox">
                DNI DE LA MAMÁ (Copia)
            </label>
        </div>
        <div class="document-item">
            <label class="doc-label">
                <input type="checkbox" name="doc_carta_poder" class="doc-checkbox">
                CARTA PODER NOTARIAL (Solo apoderados)
            </label>
        </div>
        <div class="document-item">
            <label class="doc-label">
                <input type="checkbox" name="doc_carta_compromiso" class="doc-checkbox">
                CARTA DE COMPROMISO
            </label>
        </div>

    <?php elseif ($step == 'final'): ?>
        <div style="text-align: center; padding: 3rem;">
            <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
                <svg viewBox="0 0 24 24" width="50" height="50" stroke="white" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 1rem; color: var(--primary);">DECLARACIÓN JURADA Y COMPROMISO 2026</h2>
            <p style="color: var(--text-muted); margin-bottom: 3rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                Declaro bajo juramento que los datos consignados en este formulario son verídicos. Como representante, me comprometo a cumplir estrictamente con los lineamientos del reglamento interno de la I.E. Las Capullanas.
            </p>
            <button type="submit" name="actualizar" value="1" class="btn btn-primary" style="height: 60px; padding: 0 3rem; font-size: 1.1rem;">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.75rem;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                ACEPTO COMPROMISO Y REGLAMENTOS
            </button>
        </div>
    <?php endif; ?>

    <?php if ($step != 'final'): ?>
    <div class="form-actions">
        <a href="ratificacion.php" class="btn btn-secondary">← BUSCAR OTRA</a>
        <input type="hidden" name="current_step" value="<?php echo $step; ?>">
        <?php 
            $next = ['estudiante' => 'salud', 'salud' => 'familia', 'familia' => 'sacramentos', 'sacramentos' => 'documentos', 'documentos' => 'final'];
            $next_step = $next[$step];
        ?>
        <button type="submit" name="accion" value="guardar_y_seguir" class="btn btn-primary">
            SIGUIENTE PASO →
        </button>
    </div>
    <?php endif; ?>
</form>

<?php } ?>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
