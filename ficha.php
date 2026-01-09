<?php
require_once 'config/database.php';

// Obtener DNI
$dni = isset($_GET['dni']) ? $_GET['dni'] : '';

$estudiante = fetchOne(
    "SELECT e.*, CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo 
     FROM estudiantes e WHERE e.dni = ?",
    [$dni]
);

if (!$estudiante) {
    die('Estudiante no encontrado');
}

// Obtener datos adicionales
$salud = fetchOne("SELECT * FROM salud_estudiantes WHERE estudiante_id = ?", [$estudiante['id']]);
$representante = fetchOne(
    "SELECT r.* FROM representantes r 
     INNER JOIN estudiante_representante er ON r.id = er.representante_id 
     WHERE er.estudiante_id = ? AND er.es_principal = 1",
    [$estudiante['id']]
);

// Obtener todos los representantes
$representantes = fetchAll(
    "SELECT r.*, er.es_principal FROM representantes r 
     INNER JOIN estudiante_representante er ON r.id = er.representante_id 
     WHERE er.estudiante_id = ?
     ORDER BY er.es_principal DESC, r.parentesco",
    [$estudiante['id']]
);

// Obtener sacramentos
$sacramentos = fetchOne("SELECT * FROM sacramentos WHERE estudiante_id = ?", [$estudiante['id']]);

// Obtener contacto de emergencia
$emergencia = fetchOne("SELECT * FROM contactos_emergencia WHERE estudiante_id = ?", [$estudiante['id']]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha de Matrícula - <?php echo $estudiante['dni']; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: #525659; 
            margin: 0; 
            padding: 2rem; 
            display: flex; 
            justify-content: center; 
        }
        .paper { 
            background: white; 
            width: 210mm; 
            min-height: 297mm; 
            padding: 20mm; 
            box-shadow: 0 0 10px rgba(0,0,0,0.5); 
            position: relative; 
        }
        .header { 
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            height: 120px; 
            margin: -20mm -20mm 15mm -20mm; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .header h1 { 
            margin: 0; 
            font-size: 28px;
            font-weight: 800;
            z-index: 2;
            text-align: center;
        }
        .logo {
            position: absolute;
            left: 30px;
            top: 50%;
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4f46e5;
            font-weight: 800;
            font-size: 12px;
            text-align: center;
            line-height: 1.2;
        }
        .section-title {
            background: #f3f4f6;
            padding: 12px 16px;
            margin: 25px 0 15px 0;
            border-left: 4px solid #4f46e5;
            font-weight: 800;
            font-size: 14px;
            color: #1f2937;
            text-transform: uppercase;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px;
        }
        td, th { 
            border: 1px solid #e5e7eb; 
            padding: 12px; 
            font-size: 13px; 
        }
        th { 
            background: #f9fafb; 
            text-align: left; 
            width: 35%; 
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
        }
        td {
            color: #1f2937;
            font-weight: 600;
        }
        .footer {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        .footer p {
            font-size: 11px;
            color: #9ca3af;
            margin: 5px 0;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        @media print {
            body { background: white; padding: 0; }
            .paper { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="paper">
        <div class="header">
            <div class="logo">LAS<br>CAPULLANAS</div>
            <h1>FICHA DE MATRÍCULA 2026</h1>
        </div>

        <div class="section-title">📋 DATOS DEL ESTUDIANTE</div>
        <table>
            <tr>
                <th>Apellidos y Nombres</th>
                <td><?php echo $estudiante['nombre_completo']; ?></td>
            </tr>
            <tr>
                <th>DNI</th>
                <td><strong style="color: #4f46e5; font-size: 16px;"><?php echo $estudiante['dni']; ?></strong></td>
            </tr>
            <tr>
                <th>Fecha de Nacimiento</th>
                <td><?php echo date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])); ?></td>
            </tr>
            <tr>
                <th>Dirección</th>
                <td><?php echo $estudiante['direccion'] ?: 'No especificada'; ?></td>
            </tr>
        </table>

        <div class="section-title">🎓 UBICACIÓN ACADÉMICA 2026</div>
        <table>
            <tr>
                <th>Nivel Educativo</th>
                <td><?php echo $estudiante['nivel']; ?></td>
            </tr>
            <tr>
                <th>Grado/Año</th>
                <td><?php echo $estudiante['grado']; ?></td>
            </tr>
            <tr>
                <th>Sección Asignada</th>
                <td><span class="badge">ALUMNA NUEVA</span></td>
            </tr>
        </table>

        <?php if ($salud): ?>
        <div class="section-title">🏥 DATOS DE SALUD</div>
        <table>
            <tr>
                <th>Seguro de Salud</th>
                <td><?php echo $salud['seguro_salud'] ?: 'No especificado'; ?></td>
            </tr>
            <tr>
                <th>Grupo Sanguíneo</th>
                <td><?php echo $salud['grupo_sanguineo'] ?: 'No especificado'; ?></td>
            </tr>
            <tr>
                <th>Peso / Talla</th>
                <td><?php echo $salud['peso_kg']; ?> kg / <?php echo $salud['talla_cm']; ?> cm</td>
            </tr>
            <tr>
                <th>Esquema de Vacunas</th>
                <td><?php echo $salud['tiene_carnet_vacunacion'] ? 'COMPLETO' : 'INCOMPLETO'; ?></td>
            </tr>
            <tr>
                <th>Dosis COVID-19</th>
                <td><?php echo $salud['dosis_covid'] ?? '0'; ?> dosis</td>
            </tr>
            <?php if (!empty($salud['tiene_discapacidad'])): ?>
            <tr>
                <th>Discapacidad</th>
                <td style="color: #dc2626; font-weight: 700;">SÍ - <?php echo $salud['detalle_discapacidad']; ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($salud['detalle_alergias'])): ?>
            <tr>
                <th>Alergias</th>
                <td style="color: #ea580c; font-weight: 700;"><?php echo $salud['detalle_alergias']; ?></td>
            </tr>
            <?php endif; ?>
        </table>
        <?php endif; ?>

        <?php if (!empty($representantes)): ?>
        <div class="section-title">👨‍👩‍👧 DATOS DE LA FAMILIA</div>
        <?php foreach ($representantes as $rep): ?>
        <table style="margin-bottom: 10px;">
            <tr>
                <th colspan="2" style="background: #eef2ff; color: #4f46e5; font-size: 12px;">
                    <?php echo strtoupper($rep['parentesco']); ?>
                    <?php if ($rep['es_principal']): ?>
                        <span class="badge" style="background: #fef3c7; color: #92400e; margin-left: 10px;">REPRESENTANTE LEGAL</span>
                    <?php endif; ?>
                </th>
            </tr>
            <tr>
                <th>Nombre Completo</th>
                <td><?php echo $rep['nombres'] . ' ' . $rep['apellido_paterno'] . ' ' . $rep['apellido_materno']; ?></td>
            </tr>
            <tr>
                <th>DNI</th>
                <td><?php echo $rep['dni']; ?></td>
            </tr>
            <tr>
                <th>Celular / WhatsApp</th>
                <td><?php echo $rep['celular']; ?> / <?php echo $rep['whatsapp']; ?></td>
            </tr>
        </table>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($emergencia): ?>
        <div class="section-title" style="border-left-color: #dc2626; background: #fef2f2;">🚨 CONTACTO DE EMERGENCIA</div>
        <table>
            <tr>
                <th>Nombre Completo</th>
                <td><?php echo $emergencia['nombre_completo']; ?></td>
            </tr>
            <tr>
                <th>Parentesco</th>
                <td><?php echo $emergencia['parentesco']; ?></td>
            </tr>
            <tr>
                <th>Celular</th>
                <td style="color: #dc2626; font-weight: 700; font-size: 15px;"><?php echo $emergencia['celular']; ?></td>
            </tr>
        </table>
        <?php endif; ?>

        <?php if ($sacramentos): ?>
        <div class="section-title" style="border-left-color: #7c3aed; background: #faf5ff;">✝️ VIDA SACRAMENTAL</div>
        <table>
            <tr>
                <th>Sacramentos de la Niña</th>
                <td>
                    <?php 
                    $sacs = [];
                    if (!empty($sacramentos['bautismo'])) $sacs[] = 'Bautismo';
                    if (!empty($sacramentos['primera_comunion'])) $sacs[] = 'Primera Comunión';
                    if (!empty($sacramentos['confirmacion'])) $sacs[] = 'Confirmación';
                    echo !empty($sacs) ? implode(', ', $sacs) : 'Ninguno';
                    ?>
                </td>
            </tr>
            <?php if (!empty($sacramentos['bautismo_papa']) || !empty($sacramentos['bautismo_mama'])): ?>
            <tr>
                <th>Bautismo de los Padres</th>
                <td>
                    <?php 
                    $bauts = [];
                    if (!empty($sacramentos['bautismo_papa'])) $bauts[] = 'Papá';
                    if (!empty($sacramentos['bautismo_mama'])) $bauts[] = 'Mamá';
                    echo implode(' y ', $bauts);
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($sacramentos['matrimonio_religioso'])): ?>
            <tr>
                <th>Matrimonio Religioso</th>
                <td style="color: #7c3aed; font-weight: 700;">✓ SÍ</td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($sacramentos['estado_matrimonio_padres']) && $sacramentos['estado_matrimonio_padres'] != 'NINGUNO'): ?>
            <tr>
                <th>Situación Conyugal</th>
                <td><?php echo str_replace('_', ' ', $sacramentos['estado_matrimonio_padres']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
        <?php endif; ?>

        <div class="section-title">📅 INFORMACIÓN DE REGISTRO</div>
        <table>
            <tr>
                <th>Fecha de Registro</th>
                <td><?php echo date('d/m/Y H:i:s'); ?></td>
            </tr>
            <tr>
                <th>Estado de Matrícula</th>
                <td><span class="badge" style="background: #d1fae5; color: #047857;">✓ RATIFICADO</span></td>
            </tr>
        </table>

        <div class="footer">
            <p style="font-weight: 700; color: #4f46e5; margin-bottom: 10px;">IE LAS CAPULLANAS - MATRÍCULA 2026</p>
            <p>Documento generado automáticamente por el Sistema de Gestión Escolar</p>
            <p>Código de Verificación: <?php echo strtoupper(substr(md5($estudiante['dni']), 0, 8)); ?></p>
        </div>
    </div>
</body>
</html>
