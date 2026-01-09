<?php
require_once 'config/database.php';

// Filtros
$nivel_filter = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$grado_filter = isset($_GET['grado']) ? $_GET['grado'] : '';
$seccion_filter = isset($_GET['seccion']) ? $_GET['seccion'] : '';

// Construir query
$where = [];
$params = [];

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

$estudiantes = fetchAll(
    "SELECT e.*, CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo 
     FROM estudiantes e 
     $whereClause
     ORDER BY e.grado, e.seccion, e.apellido_paterno",
    $params
);

if (empty($estudiantes)) {
    die('No se encontraron estudiantes con los filtros seleccionados.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fichas Masivas de Matrícula</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: #525659; 
            margin: 0; 
            padding: 0; 
        }
        .page-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }
        .paper { 
            background: white; 
            width: 210mm; 
            height: 297mm; /* Altura fija A4 */
            padding: 20mm; 
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0,0,0,0.5); 
            position: relative; 
            page-break-after: always; /* Importante para impresión */
            margin-bottom: 2rem;
            overflow: hidden; /* Evitar desbordes */
        }
        .paper:last-child {
            page-break-after: auto;
            margin-bottom: 0;
        }
        .header { 
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            height: 100px; 
            margin: -20mm -20mm 10mm -20mm; 
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
            font-size: 24px;
            font-weight: 800;
            z-index: 2;
            text-align: center;
        }
        .logo {
            position: absolute;
            left: 30px;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
        }
        .section-title {
            background: #f3f4f6;
            padding: 8px 12px;
            margin: 15px 0 10px 0;
            border-left: 4px solid #4f46e5;
            font-weight: 800;
            font-size: 12px;
            color: #1f2937;
            text-transform: uppercase;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px;
        }
        td, th { 
            border: 1px solid #e5e7eb; 
            padding: 8px; 
            font-size: 11px; 
        }
        th { 
            background: #f9fafb; 
            text-align: left; 
            width: 35%; 
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
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
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
        }
        .footer p {
            font-size: 10px;
            color: #9ca3af;
            margin: 3px 0;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        @media print {
            body { background: white; padding: 0; }
            .page-container { padding: 0; display: block; }
            .paper { 
                box-shadow: none; 
                margin: 0; 
                width: 100%;
                height: 100vh; /* Forzar altura de página */
                page-break-after: always;
            }
            .paper:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php foreach ($estudiantes as $estudiante): 
            // Obtener datos adicionales para cada estudiante
            $salud = fetchOne("SELECT * FROM salud_estudiantes WHERE estudiante_id = ?", [$estudiante['id']]);
            $representante = fetchOne(
                "SELECT r.* FROM representantes r 
                INNER JOIN estudiante_representante er ON r.id = er.representante_id 
                WHERE er.estudiante_id = ? AND er.es_principal = 1",
                [$estudiante['id']]
            );
        ?>
        <div class="paper">
            <div class="header">
                <div class="logo">
                    <img src="assets/images/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
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
                    <td><strong style="color: #4f46e5; font-size: 14px;"><?php echo $estudiante['dni']; ?></strong></td>
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
                    <td><?php echo $salud['seguro_salud']; ?></td>
                </tr>
                <tr>
                    <th>Grupo Sanguíneo</th>
                    <td><?php echo $salud['grupo_sanguineo'] ?: 'No especificado'; ?></td>
                </tr>
                <tr>
                    <th>Peso / Talla</th>
                    <td><?php echo $salud['peso_kg']; ?> kg / <?php echo $salud['talla_cm']; ?> cm</td>
                </tr>
            </table>
            <?php endif; ?>

            <?php if ($representante): ?>
            <div class="section-title">👨‍👩‍👧 DATOS DEL REPRESENTANTE</div>
            <table>
                <tr>
                    <th>Nombre Completo</th>
                    <td><?php echo $representante['nombres'] . ' ' . $representante['apellido_paterno'] . ' ' . $representante['apellido_materno']; ?></td>
                </tr>
                <tr>
                    <th>Parentesco</th>
                    <td><?php echo $representante['parentesco']; ?></td>
                </tr>
                <tr>
                    <th>Celular / WhatsApp</th>
                    <td><?php echo $representante['celular']; ?> / <?php echo $representante['whatsapp']; ?></td>
                </tr>
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
                <p style="font-weight: 700; color: #4f46e5; margin-bottom: 5px;">IE LAS CAPULLANAS - MATRÍCULA 2026</p>
                <p>Documento generado automáticamente por el Sistema de Gestión Escolar</p>
                <p>Código de Verificación: <?php echo strtoupper(substr(md5($estudiante['dni']), 0, 8)); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <script>
        // Imprimir automáticamente al cargar
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
