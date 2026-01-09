<?php
session_start();
require_once 'config/database.php';

$_SESSION['usuario_id'] = 1;

// Inicializar datos en sesión si no existen
if (!isset($_SESSION['nueva_matricula'])) {
    $_SESSION['nueva_matricula'] = [
        'estudiante' => [],
        'salud' => [],
        'familia' => [
            'madre' => [],
            'padre' => [],
            'apoderado' => [],
            'emergencia' => []
        ],
        'sacramentos' => [],
        'documentos' => []
    ];
}

$valid_steps = ['estudiante', 'salud', 'familia', 'sacramentos', 'documentos', 'final'];
$step = isset($_GET['step']) && in_array($_GET['step'], $valid_steps) ? $_GET['step'] : 'estudiante';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step'])) {
        $current_step = $_POST['step'];
        
        // Guardar datos en sesión
        if ($current_step == 'estudiante') {
            $_SESSION['nueva_matricula']['estudiante'] = $_POST;
        } elseif ($current_step == 'salud') {
            $_SESSION['nueva_matricula']['salud'] = $_POST;
        } elseif ($current_step == 'familia') {
            $_SESSION['nueva_matricula']['familia'] = $_POST;
        } elseif ($current_step == 'sacramentos') {
            $_SESSION['nueva_matricula']['sacramentos'] = $_POST;
        } elseif ($current_step == 'documentos') {
            $_SESSION['nueva_matricula']['documentos'] = $_POST;
        }
        
        // Redirigir al siguiente paso
        $next_steps = [
            'estudiante' => 'salud',
            'salud' => 'familia',
            'familia' => 'sacramentos',
            'sacramentos' => 'documentos',
            'documentos' => 'final'
        ];
        
        if ($current_step == 'final') {
            // Guardar en base de datos
            $est = $_SESSION['nueva_matricula']['estudiante'];
            
            // Combinar día, mes y año en formato YYYY-MM-DD
            if (isset($est['dia_nacimiento']) && isset($est['mes_nacimiento']) && isset($est['anio_nacimiento'])) {
                $fecha_nacimiento = $est['anio_nacimiento'] . '-' . $est['mes_nacimiento'] . '-' . $est['dia_nacimiento'];
            } else {
                // Fallback para compatibilidad con formato antiguo
                $fecha_nacimiento = $est['fecha_nacimiento'] ?? '';
                if (strpos($fecha_nacimiento, '/') !== false) {
                    $partes = explode('/', $fecha_nacimiento);
                    if (count($partes) == 3) {
                        $fecha_nacimiento = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
                    }
                }
            }


            $estudiante_id = insert(
                "INSERT INTO estudiantes (dni, nombres, apellido_paterno, apellido_materno, fecha_nacimiento, direccion, nivel, grado, seccion, created_by, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$est['dni'], $est['nombres'], $est['apellido_paterno'], $est['apellido_materno'], $fecha_nacimiento, $est['direccion'], $est['nivel'], $est['grado'], $est['seccion'], $_SESSION['usuario_id']]
            );
            
            // Guardar salud
            if (!empty($_SESSION['nueva_matricula']['salud'])) {
                $salud = $_SESSION['nueva_matricula']['salud'];
                insert(
                    "INSERT INTO salud_estudiantes (estudiante_id, esquema_vacunas, dosis_covid, peso_kg, talla_cm, seguro_salud, grupo_sanguineo, tiene_alergias, detalle_alergias, created_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$estudiante_id, $salud['esquema_vacunas'], $salud['dosis_covid'], $salud['peso'], $salud['talla'], $salud['seguro'], $salud['grupo_sanguineo'], isset($salud['tiene_alergias']) ? 1 : 0, $salud['detalle_alergias'] ?? '', $_SESSION['usuario_id']]
                );
            }
            
            // Guardar representantes
            if (!empty($_SESSION['nueva_matricula']['familia'])) {
                $fam = $_SESSION['nueva_matricula']['familia'];
                
                // Madre
                if (!empty($fam['madre_dni'])) {
                    $madre_id = insert(
                        "INSERT INTO representantes (dni, nombres, apellido_paterno, apellido_materno, parentesco, celular, whatsapp, created_by) 
                         VALUES (?, ?, ?, ?, 'MADRE', ?, ?, ?)",
                        [$fam['madre_dni'], $fam['madre_nombres'], '', '', $fam['madre_celular'], $fam['madre_whatsapp'], $_SESSION['usuario_id']]
                    );
                    insert("INSERT INTO estudiante_representante (estudiante_id, representante_id, es_principal) VALUES (?, ?, 1)", [$estudiante_id, $madre_id]);
                }
                
                // Padre
                if (!empty($fam['padre_dni'])) {
                    $padre_id = insert(
                        "INSERT INTO representantes (dni, nombres, apellido_paterno, apellido_materno, parentesco, celular, whatsapp, created_by) 
                         VALUES (?, ?, ?, ?, 'PADRE', ?, ?, ?)",
                        [$fam['padre_dni'], $fam['padre_nombres'], '', '', $fam['padre_celular'], $fam['padre_whatsapp'], $_SESSION['usuario_id']]
                    );
                    insert("INSERT INTO estudiante_representante (estudiante_id, representante_id, es_principal) VALUES (?, ?, 0)", [$estudiante_id, $padre_id]);
                }
            }
            
            // Auditoría
            insert(
                "INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_nuevos, ip_address, created_at) 
                 VALUES ('estudiantes', ?, 'INSERT', ?, ?, ?, NOW())",
                [$estudiante_id, $_SESSION['usuario_id'], json_encode($_SESSION['nueva_matricula']), $_SERVER['REMOTE_ADDR']]
            );
            
            // Limpiar sesión
            unset($_SESSION['nueva_matricula']);
            
            header("Location: success.php?dni=" . $est['dni']);
            exit;
        } else {
            header("Location: nueva_matricula.php?step=" . $next_steps[$current_step]);
            exit;
        }
    }
}

$pageTitle = 'Nueva Matrícula 2026';
include 'includes/header.php';
$data = $_SESSION['nueva_matricula'];
?>

<div>
    <!-- Wizard Navigation -->
    <!-- Wizard Navigation (Solo visual, navegación bloqueada) -->
    <div class="wizard-nav">
        <div class="wizard-step <?php echo $step == 'estudiante' ? 'active step-estudiante' : ''; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            ESTUDIANTE
        </div>
        <div class="wizard-step <?php echo $step == 'salud' ? 'active step-salud' : ''; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path></svg>
            SALUD
        </div>
        <div class="wizard-step <?php echo $step == 'familia' ? 'active step-familia' : ''; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            FAMILIA
        </div>
        <div class="wizard-step <?php echo $step == 'sacramentos' ? 'active step-sacramentos' : ''; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
            SACRAMENTOS
        </div>
        <div class="wizard-step <?php echo $step == 'documentos' ? 'active step-documentos' : ''; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            DOCUMENTOS
        </div>
    </div>

    <form method="POST" class="form-card animate-fade-in" id="form-step">
        <input type="hidden" name="step" value="<?php echo $step; ?>">
        
        <?php if ($step == 'estudiante'): ?>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label required">DNI DE LA ALUMNA</label>
                    <input type="text" name="dni" class="form-control" value="<?php echo $data['estudiante']['dni'] ?? ''; ?>" required maxlength="8" pattern="[0-9]{8}">
                </div>
                <div class="form-group full-width">
                    <label class="form-label required">APELLIDOS Y NOMBRES</label>
                    <input type="text" name="apellido_paterno" class="form-control" value="<?php echo $data['estudiante']['apellido_paterno'] ?? ''; ?>" required placeholder="Ingrese apellidos y nombres completos">
                    <input type="hidden" name="apellido_materno" value="">
                    <input type="hidden" name="nombres" value="">
                </div>
                <div class="form-group">
                    <label class="form-label required">DÍA DE NACIMIENTO</label>
                    <select name="dia_nacimiento" class="form-control" required>
                        <option value="">-- DÍA --</option>
                        <?php 
                        $dia_actual = isset($data['estudiante']['fecha_nacimiento']) ? date('d', strtotime($data['estudiante']['fecha_nacimiento'])) : '';
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
                        $mes_actual = isset($data['estudiante']['fecha_nacimiento']) ? date('m', strtotime($data['estudiante']['fecha_nacimiento'])) : '';
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
                        $anio_actual = isset($data['estudiante']['fecha_nacimiento']) ? date('Y', strtotime($data['estudiante']['fecha_nacimiento'])) : '';
                        for($a = date('Y'); $a >= date('Y') - 20; $a--): 
                        ?>
                            <option value="<?php echo $a; ?>" <?php echo $anio_actual == $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label required">NIVEL EDUCATIVO</label>
                    <select name="nivel" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        <option value="INICIAL" <?php echo ($data['estudiante']['nivel'] ?? '') == 'INICIAL' ? 'selected' : ''; ?>>INICIAL</option>
                        <option value="PRIMARIA" <?php echo ($data['estudiante']['nivel'] ?? '') == 'PRIMARIA' ? 'selected' : ''; ?>>PRIMARIA</option>
                        <option value="SECUNDARIA" <?php echo ($data['estudiante']['nivel'] ?? '') == 'SECUNDARIA' ? 'selected' : ''; ?>>SECUNDARIA</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label required">GRADO ACADÉMICO</label>
                    <select name="grado" id="grado_select" class="form-control" required>
                        <option value="">-- SELECCIONE --</option>
                    </select>
                    <input type="hidden" id="grado_actual" value="<?php echo $data['estudiante']['grado'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label required">SECCIÓN</label>
                    <select name="seccion" class="form-control" required>
                        <option value="">-- SELECCIONE --</option>
                        <option value="A" <?php echo ($data['estudiante']['seccion'] ?? '') == 'A' ? 'selected' : ''; ?>>A</option>
                        <option value="B" <?php echo ($data['estudiante']['seccion'] ?? '') == 'B' ? 'selected' : ''; ?>>B</option>
                        <option value="C" <?php echo ($data['estudiante']['seccion'] ?? '') == 'C' ? 'selected' : ''; ?>>C</option>
                        <option value="D" <?php echo ($data['estudiante']['seccion'] ?? '') == 'D' ? 'selected' : ''; ?>>D</option>
                        <option value="E" <?php echo ($data['estudiante']['seccion'] ?? '') == 'E' ? 'selected' : ''; ?>>E</option>
                    </select>
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
                    // Solo ejecutar si ya hay un nivel seleccionado (ej: al volver atrás)
                    if (nivelSelect.value) {
                         actualizarGrados();
                    }
                });
                </script>
                <div class="form-group">
                    <label class="form-label required">SECCIÓN 2026</label>
                    <input type="text" class="form-control" value="ALUMNA NUEVA" disabled style="background-color: #f3f4f6; color: #666;">
                    <input type="hidden" name="seccion" value="ALUMNA NUEVA">
                </div>
                <div class="form-group full-width">
                    <label class="form-label required">DIRECCIÓN DE DOMICILIO</label>
                    <input type="text" name="direccion" class="form-control" value="<?php echo $data['estudiante']['direccion'] ?? ''; ?>" required>
                </div>
            </div>

        <?php elseif ($step == 'salud'): ?>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label required">ESQUEMA DE VACUNAS</label>
                    <select name="esquema_vacunas" class="form-control" required>
                        <option value="">-- SELECCIONE --</option>
                        <option value="COMPLETO" <?php echo ($data['salud']['esquema_vacunas'] ?? '') == 'COMPLETO' ? 'selected' : ''; ?>>Sí, tiene todas sus vacunas</option>
                        <option value="INCOMPLETO" <?php echo ($data['salud']['esquema_vacunas'] ?? '') == 'INCOMPLETO' ? 'selected' : ''; ?>>No, no tiene ninguna vacuna</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label required">DOSIS COVID-19</label>
                    <input type="number" name="dosis_covid" class="form-control" value="<?php echo $data['salud']['dosis_covid'] ?? ''; ?>" required min="0" max="5">
                </div>
                <div class="form-group">
                    <label class="form-label required">PESO ACTUAL (KG)</label>
                    <input type="number" step="0.01" name="peso" class="form-control" value="<?php echo $data['salud']['peso'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">TALLA ACTUAL (CM)</label>
                    <input type="number" step="0.01" name="talla" class="form-control" value="<?php echo $data['salud']['talla'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">SEGURO DE SALUD</label>
                    <select name="seguro" class="form-control" required>
                        <option value="">-- SELECCIONE --</option>
                        <option value="SIS" <?php echo ($data['salud']['seguro'] ?? '') == 'SIS' ? 'selected' : ''; ?>>SIS</option>
                        <option value="ESSALUD" <?php echo ($data['salud']['seguro'] ?? '') == 'ESSALUD' ? 'selected' : ''; ?>>ESSALUD</option>
                        <option value="PRIVADO" <?php echo ($data['salud']['seguro'] ?? '') == 'PRIVADO' ? 'selected' : ''; ?>>PRIVADO</option>
                        <option value="NINGUNO" <?php echo ($data['salud']['seguro'] ?? '') == 'NINGUNO' ? 'selected' : ''; ?>>NINGUNO</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label required">GRUPO SANGUÍNEO</label>
                    <select name="grupo_sanguineo" class="form-control" required>
                        <option value="">-- SELECCIONE --</option>
                        <option value="O+" <?php echo ($data['salud']['grupo_sanguineo'] ?? '') == 'O+' ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo ($data['salud']['grupo_sanguineo'] ?? '') == 'O-' ? 'selected' : ''; ?>>O-</option>
                        <option value="A+" <?php echo ($data['salud']['grupo_sanguineo'] ?? '') == 'A+' ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo ($data['salud']['grupo_sanguineo'] ?? '') == 'A-' ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo ($data['salud']['grupo_sanguineo'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo ($data['salud']['grupo_sanguineo'] ?? '') == 'B-' ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo ($data['salud']['grupo_sanguineo'] ?? '') == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo ($data['salud']['grupo_sanguineo'] ?? '') == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">¿PRESENTA ALERGIAS?</label>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="tiene_alergias" value="1" <?php echo !empty($data['salud']['tiene_alergias']) ? 'checked' : ''; ?> onclick="document.getElementById('detalle_alergias').style.display = this.checked ? 'block' : 'none'">
                            <span>Sí, presenta alergias</span>
                        </label>
                    </div>
                </div>
                <div class="form-group full-width" id="detalle_alergias" style="display: <?php echo !empty($data['salud']['tiene_alergias']) ? 'block' : 'none'; ?>;">
                    <label class="form-label">DETALLE LA ALERGIA</label>
                    <input type="text" name="detalle_alergias" class="form-control" value="<?php echo $data['salud']['detalle_alergias'] ?? ''; ?>">
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">¿PRESENTA ALGUNA DISCAPACIDAD?</label>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="tiene_discapacidad" value="1" <?php echo !empty($data['salud']['tiene_discapacidad']) ? 'checked' : ''; ?> onclick="document.getElementById('detalle_discapacidad').style.display = this.checked ? 'block' : 'none'">
                            <span>Sí, presenta discapacidad</span>
                        </label>
                    </div>
                </div>
                <div class="form-group full-width" id="detalle_discapacidad" style="display: <?php echo !empty($data['salud']['tiene_discapacidad']) ? 'block' : 'none'; ?>;">
                    <label class="form-label">DETALLE LA DISCAPACIDAD</label>
                    <textarea name="detalle_discapacidad" class="form-control" rows="2"><?php echo $data['salud']['detalle_discapacidad'] ?? ''; ?></textarea>
                </div>
            </div>

        <?php elseif ($step == 'familia'): ?>
            <!-- Datos de Padres/Representante -->
            <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0 0 1.5rem 0; color: var(--primary);">DATOS DE PADRES / REPRESENTANTE</h3>
            
            <!-- PADRE -->
            <div class="parent-card" style="background: #f0f9ff; border: 1px solid #e0f2fe;">
                <div class="parent-title" style="color: #0284c7; display: flex; justify-content: space-between; align-items: center;">
                    <span>👨 PADRE</span>
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                        <input type="radio" name="representante_legal" value="PADRE" <?php echo ($data['familia']['representante_legal'] ?? '') == 'PADRE' ? 'checked' : ''; ?>>
                        <span style="font-weight: 600;">REPRESENTANTE LEGAL</span>
                    </label>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">DNI</label>
                        <input type="text" name="padre_dni" class="form-control" value="<?php echo $data['familia']['padre_dni'] ?? ''; ?>" maxlength="8" pattern="[0-9]{8}" inputmode="numeric">
                    </div>
<<<<<<< Updated upstream
                    <div class="form-group">
=======
                    <div class="form-group full-width">
>>>>>>> Stashed changes
                        <label class="form-label">APELLIDOS Y NOMBRES</label>
                        <input type="text" name="padre_nombres" class="form-control" value="<?php echo $data['familia']['padre_nombres'] ?? ''; ?>" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ,]+" title="Solo letras, espacios y comas">
                    </div>
                    <div class="form-group">
                        <label class="form-label">CELULAR</label>
                        <input type="tel" name="padre_celular" class="form-control" value="<?php echo $data['familia']['padre_celular'] ?? ''; ?>" maxlength="9" pattern="[0-9]{9}" inputmode="numeric" title="9 dígitos numéricos">
                    </div>
                    <div class="form-group">
                        <label class="form-label">WHATSAPP</label>
                        <input type="tel" name="padre_whatsapp" class="form-control" value="<?php echo $data['familia']['padre_whatsapp'] ?? ''; ?>" maxlength="9" pattern="[0-9]{9}" inputmode="numeric" title="9 dígitos numéricos">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">DOMICILIO ACTUAL</label>
<<<<<<< Updated upstream
                        <input type="text" name="padre_direccion" class="form-control" value="<?php echo $data['familia']['padre_direccion'] ?? ''; ?>">
=======
                        <input type="text" name="padre_domicilio" class="form-control" value="<?php echo $data['familia']['padre_domicilio'] ?? ''; ?>">
>>>>>>> Stashed changes
                    </div>
                </div>
            </div>

            <!-- MADRE -->
            <div class="parent-card" style="background: #fffbeb; border: 1px solid #fef3c7; margin-top: 1.5rem;">
                <div class="parent-title" style="color: #f59e0b; display: flex; justify-content: space-between; align-items: center;">
                    <span>👩 MADRE</span>
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                        <input type="radio" name="representante_legal" value="MADRE" <?php echo ($data['familia']['representante_legal'] ?? 'MADRE') == 'MADRE' ? 'checked' : ''; ?>>
                        <span style="font-weight: 600;">REPRESENTANTE LEGAL</span>
                    </label>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">DNI</label>
                        <input type="text" name="madre_dni" class="form-control" value="<?php echo $data['familia']['madre_dni'] ?? ''; ?>" maxlength="8" pattern="[0-9]{8}" inputmode="numeric">
                    </div>
<<<<<<< Updated upstream
                    <div class="form-group">
=======
                    <div class="form-group full-width">
>>>>>>> Stashed changes
                        <label class="form-label required">APELLIDOS Y NOMBRES</label>
                        <input type="text" name="madre_nombres" class="form-control" value="<?php echo $data['familia']['madre_nombres'] ?? ''; ?>" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ,]+" title="Solo letras, espacios y comas">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">CELULAR</label>
                        <input type="tel" name="madre_celular" class="form-control" value="<?php echo $data['familia']['madre_celular'] ?? ''; ?>" maxlength="9" pattern="[0-9]{9}" inputmode="numeric" title="9 dígitos numéricos">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">WHATSAPP</label>
                        <input type="tel" name="madre_whatsapp" class="form-control" value="<?php echo $data['familia']['madre_whatsapp'] ?? ''; ?>" maxlength="9" pattern="[0-9]{9}" inputmode="numeric" title="9 dígitos numéricos">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label required">DOMICILIO ACTUAL</label>
<<<<<<< Updated upstream
                        <input type="text" name="madre_direccion" class="form-control" value="<?php echo $data['familia']['madre_direccion'] ?? ''; ?>" required>
=======
                        <input type="text" name="madre_domicilio" class="form-control" value="<?php echo $data['familia']['madre_domicilio'] ?? ''; ?>">
>>>>>>> Stashed changes
                    </div>
                </div>
            </div>

            <!-- APODERADO (OPCIONAL) -->
            <div class="parent-card" style="background: #f5f3ff; border: 1px solid #e9d5ff; margin-top: 1.5rem;">
                <div class="parent-title" style="color: #9333ea; display: flex; justify-content: space-between; align-items: center;">
                    <span>👤 APODERADO (OPCIONAL)</span>
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                        <input type="radio" name="representante_legal" value="APODERADO" <?php echo ($data['familia']['representante_legal'] ?? '') == 'APODERADO' ? 'checked' : ''; ?>>
                        <span style="font-weight: 600;">REPRESENTANTE LEGAL</span>
                    </label>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">DNI</label>
                        <input type="text" name="apoderado_dni" class="form-control" value="<?php echo $data['familia']['apoderado_dni'] ?? ''; ?>" maxlength="8" pattern="[0-9]{8}" inputmode="numeric">
                    </div>
                    <div class="form-group">
<<<<<<< Updated upstream
=======
                        <label class="form-label">PARENTESCO</label>
                        <input type="text" name="apoderado_parentesco" class="form-control" value="<?php echo $data['familia']['apoderado_parentesco'] ?? ''; ?>" placeholder="Ej: Tío, Abuelo">
                    </div>
                    <div class="form-group full-width">
>>>>>>> Stashed changes
                        <label class="form-label">APELLIDOS Y NOMBRES</label>
                        <input type="text" name="apoderado_nombres" class="form-control" value="<?php echo $data['familia']['apoderado_nombres'] ?? ''; ?>" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ,]+" title="Solo letras, espacios y comas">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PARENTESCO</label>
                        <input type="text" name="apoderado_parentesco" class="form-control" value="<?php echo $data['familia']['apoderado_parentesco'] ?? ''; ?>" placeholder="Ej: Tío, Abuelo, etc.">
                    </div>
                    <div class="form-group">
                        <label class="form-label">CELULAR</label>
                        <input type="tel" name="apoderado_celular" class="form-control" value="<?php echo $data['familia']['apoderado_celular'] ?? ''; ?>" maxlength="9" pattern="[0-9]{9}" inputmode="numeric" title="9 dígitos numéricos">
                    </div>
                    <div class="form-group">
                        <label class="form-label">WHATSAPP</label>
                        <input type="tel" name="apoderado_whatsapp" class="form-control" value="<?php echo $data['familia']['apoderado_whatsapp'] ?? ''; ?>" maxlength="9" pattern="[0-9]{9}" inputmode="numeric" title="9 dígitos numéricos">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">DOMICILIO ACTUAL</label>
<<<<<<< Updated upstream
                        <input type="text" name="apoderado_direccion" class="form-control" value="<?php echo $data['familia']['apoderado_direccion'] ?? ''; ?>">
=======
                        <input type="text" name="apoderado_domicilio" class="form-control" value="<?php echo $data['familia']['apoderado_domicilio'] ?? ''; ?>">
>>>>>>> Stashed changes
                    </div>
                </div>
            </div>

            <!-- Contacto de Emergencia -->
            <div class="contact-card" style="background: #fef2f2; border: 1px solid #fecaca; margin-top: 1.5rem;">
                <div class="contact-header" style="color: #dc2626;">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    CONTACTO FAMILIAR DE EMERGENCIA
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">NOMBRE COMPLETO</label>
                        <input type="text" name="emergencia_nombre" class="form-control" value="<?php echo $data['familia']['emergencia_nombre'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">PARENTESCO</label>
                        <input type="text" name="emergencia_parentesco" class="form-control" value="<?php echo $data['familia']['emergencia_parentesco'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">CELULAR DIRECTO</label>
                        <input type="tel" name="emergencia_celular" class="form-control" value="<?php echo $data['familia']['emergencia_celular'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">WHATSAPP ACTIVO</label>
                        <input type="tel" name="emergencia_whatsapp" class="form-control" value="<?php echo $data['familia']['emergencia_whatsapp'] ?? ''; ?>" required>
                    </div>
                </div>
            </div>

            <!-- HERMANAS EN EL COLEGIO -->
            <div class="form-group full-width" style="margin-top: 2rem; padding: 1.5rem; background: #fef3c7; border-radius: 12px; border: 2px solid #fbbf24;">
                <label class="form-label" style="font-size: 1.1rem; font-weight: 700; color: #92400e; margin-bottom: 1rem;">
                    👭 ¿TIENE HERMANAS ESTUDIANDO EN EL COLEGIO?
                </label>
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="tiene_hermanas" value="1" <?php echo !empty($data['familia']['tiene_hermanas']) ? 'checked' : ''; ?> onclick="document.getElementById('detalle_hermanas').style.display = this.checked ? 'block' : 'none'">
                        <span style="font-weight: 600; color: #92400e;">Sí, tiene hermanas en el colegio</span>
                    </label>
                </div>
                <div id="detalle_hermanas" style="display: <?php echo !empty($data['familia']['tiene_hermanas']) ? 'block' : 'none'; ?>; margin-top: 1rem;">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label">NOMBRES DE LAS HERMANAS</label>
                            <input type="text" name="hermanas_nombres" class="form-control" value="<?php echo $data['familia']['hermanas_nombres'] ?? ''; ?>" placeholder="Ej: María García, Ana García">
                        </div>
                        <div class="form-group">
                            <label class="form-label">GRADOS</label>
                            <input type="text" name="hermanas_grados" class="form-control" value="<?php echo $data['familia']['hermanas_grados'] ?? ''; ?>" placeholder="Ej: 3ro, 5to">
                        </div>
                        <div class="form-group">
                            <label class="form-label">SECCIONES</label>
                            <input type="text" name="hermanas_secciones" class="form-control" value="<?php echo $data['familia']['hermanas_secciones'] ?? ''; ?>" placeholder="Ej: A, B">
                        </div>
                    </div>
                </div>
            </div>


        <?php elseif ($step == 'sacramentos'): 
            $nivel = $data['estudiante']['nivel'] ?? '';
            $grado = $data['estudiante']['grado'] ?? '';
            $es_inicial = ($nivel == 'INICIAL');
            // Primaria baja (1 y 2) tampoco suelen hacer comunión, pero dejaremos disponible por si acaso o solo restringimos a inicial.
            // Usuario pidió explícitamente: "SI ES DE INICIAL (4 AÑOS) SOLO DEBE DECIR BAUTISMO"
        ?>
            <div class="section-title purple">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                SACRAMENTOS RECIBIDOS
            </div>
            
            <div class="toggle-group" style="flex-direction: column; gap: 1rem;">
                <!-- BAUTISMO (Para todos) -->
                <div class="sacramento-card">
                    <label class="toggle-btn <?php echo !empty($data['sacramentos']['bautismo']) ? 'active' : ''; ?>" style="width: 100%;">
                        <input type="checkbox" name="bautismo" class="sacramento-check" data-tipo="bautismo" value="1" <?php echo !empty($data['sacramentos']['bautismo']) ? 'checked' : ''; ?> style="display: none;">
                        <div class="toggle-info" style="text-align: right;">
                            <div class="toggle-title">BAUTISMO</div>
                            <div class="toggle-sub">Sacramento de iniciación</div>
                        </div>
                        <div class="check-circle">✓</div>
                    </label>
                    <div id="prep_bautismo" class="preparacion-opt" style="margin-top: 5px; padding-left: 10px; display: <?php echo !empty($data['sacramentos']['bautismo']) ? 'none' : 'block'; ?>;">
                        <label style="font-size: 0.9rem; color: #666; display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="bautismo_prep" value="1" <?php echo !empty($data['sacramentos']['bautismo_prep']) ? 'checked' : ''; ?>>
                            Se está preparando actualmente
                        </label>
                    </div>
                </div>
                
                <?php if (!$es_inicial): ?>
                <!-- PRIMERA COMUNIÓN (No Inicial) -->
                <div class="sacramento-card">
                    <label class="toggle-btn <?php echo !empty($data['sacramentos']['comunion']) ? 'active' : ''; ?>" style="width: 100%;">
                        <input type="checkbox" name="comunion" class="sacramento-check" data-tipo="comunion" value="1" <?php echo !empty($data['sacramentos']['comunion']) ? 'checked' : ''; ?> style="display: none;">
                        <div class="toggle-info" style="text-align: right;">
                            <div class="toggle-title">PRIMERA COMUNIÓN</div>
                            <div class="toggle-sub">Eucaristía</div>
                        </div>
                        <div class="check-circle">✓</div>
                    </label>
                    <div id="prep_comunion" class="preparacion-opt" style="margin-top: 5px; padding-left: 10px; display: <?php echo !empty($data['sacramentos']['comunion']) ? 'none' : 'block'; ?>;">
                        <label style="font-size: 0.9rem; color: #666; display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="comunion_prep" value="1" <?php echo !empty($data['sacramentos']['comunion_prep']) ? 'checked' : ''; ?>>
                            Se está preparando actualmente
                        </label>
                    </div>
                </div>
                
                <!-- CONFIRMACIÓN (No Inicial) -->
                <div class="sacramento-card">
                    <label class="toggle-btn <?php echo !empty($data['sacramentos']['confirmacion']) ? 'active' : ''; ?>" style="width: 100%;">
                        <input type="checkbox" name="confirmacion" class="sacramento-check" data-tipo="confirmacion" value="1" <?php echo !empty($data['sacramentos']['confirmacion']) ? 'checked' : ''; ?> style="display: none;">
                        <div class="toggle-info" style="text-align: right;">
                            <div class="toggle-title">CONFIRMACIÓN</div>
                            <div class="toggle-sub">Espíritu Santo</div>
                        </div>
                        <div class="check-circle">✓</div>
                    </label>
                    <div id="prep_confirmacion" class="preparacion-opt" style="margin-top: 5px; padding-left: 10px; display: <?php echo !empty($data['sacramentos']['confirmacion']) ? 'none' : 'block'; ?>;">
                        <label style="font-size: 0.9rem; color: #666; display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="confirmacion_prep" value="1" <?php echo !empty($data['sacramentos']['confirmacion_prep']) ? 'checked' : ''; ?>>
                            Se está preparando actualmente
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- SACRAMENTOS DE LOS PADRES -->
            <div class="form-group full-width" style="margin-top: 2.5rem;">
                <label class="form-label" style="font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 1.5rem;">SACRAMENTOS DE LOS PADRES</label>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                    <!-- Bautismo Papá -->
                    <div style="background: #f0f9ff; padding: 1.25rem; border-radius: 12px; border: 2px solid #e0f2fe;">
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 600; color: #0284c7; font-size: 0.95rem;">
                            <input type="checkbox" name="bautismo_papa" value="1" <?php echo !empty($data['sacramentos']['bautismo_papa']) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                            <span>👨 BAUTISMO PAPÁ</span>
                        </label>
                    </div>
                    
                    <!-- Bautismo Mamá -->
                    <div style="background: #fffbeb; padding: 1.25rem; border-radius: 12px; border: 2px solid #fef3c7;">
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 600; color: #f59e0b; font-size: 0.95rem;">
                            <input type="checkbox" name="bautismo_mama" value="1" <?php echo !empty($data['sacramentos']['bautismo_mama']) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                            <span>👩 BAUTISMO MAMÁ</span>
                        </label>
                    </div>
                    
                    <!-- Matrimonio Religioso -->
                    <div style="background: #f5f3ff; padding: 1.25rem; border-radius: 12px; border: 2px solid #e9d5ff;">
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 600; color: #9333ea; font-size: 0.95rem;">
                            <input type="checkbox" name="matrimonio_religioso" value="1" <?php echo !empty($data['sacramentos']['matrimonio_religioso']) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                            <span>💒 MATRIMONIO RELIGIOSO</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- SITUACIÓN CONYUGAL -->
            <div class="form-group full-width">
                <label class="form-label" style="font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem;">SITUACIÓN CONYUGAL DE LOS PADRES</label>
                <select name="estado_matrimonio_padres" class="form-control">
                    <option value="NINGUNO">-- SELECCIONE --</option>
                    <option value="RELIGIOSO_CIVIL" <?php echo ($data['sacramentos']['estado_matrimonio_padres'] ?? '') == 'RELIGIOSO_CIVIL' ? 'selected' : ''; ?>>CASADOS (CIVIL Y RELIGIOSO)</option>
                    <option value="SOLO_CIVIL" <?php echo ($data['sacramentos']['estado_matrimonio_padres'] ?? '') == 'SOLO_CIVIL' ? 'selected' : ''; ?>>SOLO CIVIL</option>
                    <option value="SOLO_RELIGIOSO" <?php echo ($data['sacramentos']['estado_matrimonio_padres'] ?? '') == 'SOLO_RELIGIOSO' ? 'selected' : ''; ?>>SOLO RELIGIOSO</option>
                    <option value="CONVIVIENTES" <?php echo ($data['sacramentos']['estado_matrimonio_padres'] ?? '') == 'CONVIVIENTES' ? 'selected' : ''; ?>>CONVIVIENTES</option>
                    <option value="SEPARADOS" <?php echo ($data['sacramentos']['estado_matrimonio_padres'] ?? '') == 'SEPARADOS' ? 'selected' : ''; ?>>SEPARADOS</option>
                    <option value="SOLTERO" <?php echo ($data['sacramentos']['estado_matrimonio_padres'] ?? '') == 'SOLTERO' ? 'selected' : ''; ?>>SOLTERO(A)</option>
                </select>
            </div>

        <?php elseif ($step == 'documentos'): ?>
            <div class="section-title orange">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                DOCUMENTOS FÍSICOS ENTREGADOS
            </div>
            
            <div class="document-item">
                <label class="doc-label">
                    <input type="checkbox" name="doc_ficha" value="1" class="doc-checkbox" <?php echo !empty($data['documentos']['doc_ficha']) ? 'checked' : ''; ?>>
                    FICHA ÚNICA DE MATRÍCULA
                </label>
            </div>
            
            <div class="document-item">
                <label class="doc-label">
                    <input type="checkbox" name="doc_certificado" value="1" class="doc-checkbox" <?php echo !empty($data['documentos']['doc_certificado']) ? 'checked' : ''; ?>>
                    CERTIFICADO DE ESTUDIOS
                </label>
            </div>
            
            <div class="document-item">
                <label class="doc-label">
                    <input type="checkbox" name="doc_boleta" value="1" class="doc-checkbox" <?php echo !empty($data['documentos']['doc_boleta']) ? 'checked' : ''; ?>>
                    BOLETA DE NOTAS
                </label>
            </div>
            
            <div class="document-item">
                <label class="doc-label">
                    <input type="checkbox" name="doc_constancia_matricula" value="1" class="doc-checkbox" <?php echo !empty($data['documentos']['doc_constancia_matricula']) ? 'checked' : ''; ?>>
                    CONSTANCIA DE MATRÍCULA
                </label>
            </div>
            
            <div class="document-item">
                <label class="doc-label">
                    <input type="checkbox" name="doc_acta_nacimiento" value="1" class="doc-checkbox" <?php echo !empty($data['documentos']['doc_acta_nacimiento']) ? 'checked' : ''; ?>>
                    ACTA O PARTIDA DE NACIMIENTO (ORIGINAL ACTUALIZADA)
                </label>
            </div>
            
            <div class="document-item">
                <label class="doc-label">
                    <input type="checkbox" name="doc_dni_estudiante" value="1" class="doc-checkbox" <?php echo !empty($data['documentos']['doc_dni_estudiante']) ? 'checked' : ''; ?>>
                    DNI DE LA ESTUDIANTE (COPIA)
                </label>
            </div>
            
            <div class="document-item">
                <label class="doc-label">
                    <input type="checkbox" name="doc_dni_papa" value="1" class="doc-checkbox" <?php echo !empty($data['documentos']['doc_dni_papa']) ? 'checked' : ''; ?>>
                    DNI DEL PAPÁ (COPIA)
                </label>
            </div>
            
            <div class="document-item">
                <label class="doc-label">
                    <input type="checkbox" name="doc_dni_mama" value="1" class="doc-checkbox" <?php echo !empty($data['documentos']['doc_dni_mama']) ? 'checked' : ''; ?>>
                    DNI DE LA MAMÁ (COPIA)
                </label>
            </div>
            
            <div class="document-item">
                <label class="doc-label">
                    <input type="checkbox" name="doc_carta_poder" value="1" class="doc-checkbox" <?php echo !empty($data['documentos']['doc_carta_poder']) ? 'checked' : ''; ?>>
                    CARTA PODER LEGALIZADA (SOLO APODERADOS)
                </label>
            </div>

        <?php elseif ($step == 'final'): ?>
            <div style="text-align: center; padding: 3rem;">
                <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
                    <svg viewBox="0 0 24 24" width="50" height="50" stroke="white" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 1rem; color: var(--primary);">DECLARACIÓN JURADA Y COMPROMISO 2026</h2>
                <p style="color: var(--text-muted); margin-bottom: 3rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                    Declaro bajo juramento que todos los datos ingresados en este formulario de matrícula son verídicos. Asimismo, me comprometo a cumplir con el reglamento interno de la Institución Educativa Las Capullanas.
                </p>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <?php if ($step != 'estudiante' && $step != 'final'): ?>
                <a href="?step=<?php 
                    $prev = ['salud' => 'estudiante', 'familia' => 'salud', 'sacramentos' => 'familia', 'documentos' => 'sacramentos'];
                    echo $prev[$step];
                ?>" class="btn btn-secondary">← VOLVER</a>
            <?php elseif ($step == 'final'): ?>
                <a href="?step=documentos" class="btn btn-secondary">← VOLVER</a>
            <?php else: ?>
                <a href="index.php" class="btn btn-secondary">← CANCELAR</a>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary">
                <?php echo $step == 'final' ? 'CONFIRMAR MATRÍCULA' : 'SIGUIENTE PASO →'; ?>
            </button>
        </div>
    </form>
</div>

<script>
// Validación de formulario
if(document.getElementById('form-step')) {
    document.getElementById('form-step').addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let allFilled = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                allFilled = false;
                field.style.borderColor = '#ef4444';
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (!allFilled) {
            e.preventDefault();
            alert('Por favor complete todos los campos obligatorios marcados con *');
            return false;
        }
    });

    // Lógica robusta para sacramentos usando eventos 'change' nativos
    // Esto evita conflictos de click y asegura que la UI siempre refleje el estado del input
    const sacramentoChecks = document.querySelectorAll('.sacramento-check');
    
    sacramentoChecks.forEach(check => {
        check.addEventListener('change', function() {
            const btn = this.closest('.toggle-btn');
            const tipo = this.dataset.tipo;
            const prepDiv = document.getElementById('prep_' + tipo);
            
            if (this.checked) {
                // Si tiene sacramento: Botón activo, ocultar preparación
                btn.classList.add('active');
                if(prepDiv) {
                    prepDiv.style.display = 'none';
                    // Desmarcar preparación para evitar datos inconsistentes
                    const prepInput = prepDiv.querySelector('input');
                    if(prepInput) prepInput.checked = false;
                }
            } else {
                // Si NO tiene sacramento: Botón inactivo, mostrar preparación
                btn.classList.remove('active');
                if(prepDiv) {
                    prepDiv.style.display = 'block';
                }
            }
        });
    });
}
</script>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
