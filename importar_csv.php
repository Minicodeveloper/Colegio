<?php
/**
 * Script de importación de datos desde BD_MATRI25.csv
 * 
 * Reglas de promoción 2025 -> 2026:
 * - 5 años inicial -> 1° primaria
 * - 6° primaria -> 1° secundaria  
 * - 5° secundaria -> EXALUMNOS (no se importan)
 * - Resto: se incrementa un grado
 */

require_once 'config/database.php';

echo "=== IMPORTACIÓN DE DATOS BD_MATRI25.csv ===\n\n";

// 1. Limpiar datos de prueba anteriores
echo "1. Limpiando datos de prueba anteriores...\n";
query("DELETE FROM documentos");
query("DELETE FROM sacramentos");
query("DELETE FROM hermanas");
query("DELETE FROM contactos_emergencia");
query("DELETE FROM estudiante_representante");
query("DELETE FROM matriculas");
query("DELETE FROM salud_estudiantes");
query("DELETE FROM representantes");
query("DELETE FROM estudiantes WHERE id > 0");
echo "   ✓ Datos de prueba eliminados\n\n";

// 2. Leer y procesar CSV
echo "2. Leyendo archivo CSV...\n";
$csv_file = __DIR__ . '/database/BD_MATRI25.csv';

if (!file_exists($csv_file)) {
    die("ERROR: No se encuentra el archivo $csv_file\n");
}

$handle = fopen($csv_file, 'r');
if (!$handle) {
    die("ERROR: No se puede abrir el archivo CSV\n");
}

// Leer encabezado
$header = fgetcsv($handle, 0, ';');
echo "   ✓ Archivo abierto correctamente\n";
echo "   Columnas encontradas: " . count($header) . "\n\n";

// Contadores
$total = 0;
$importados = 0;
$exalumnos = 0;
$errores = 0;
$errores_detalle = [];

echo "3. Procesando estudiantes...\n";

while (($data = fgetcsv($handle, 0, ';')) !== FALSE) {
    $total++;
    
    try {
        // Mapear datos con manejo de índices faltantes
        $dni = isset($data[0]) ? trim($data[0]) : '';
        $nombre_completo = isset($data[1]) ? trim($data[1]) : '';
        $sexo = isset($data[2]) ? trim($data[2]) : 'FEMENINO';
        $fecha_nacimiento = isset($data[3]) ? trim($data[3]) : '';
        $pais = isset($data[4]) ? trim($data[4]) : 'PERU';
        $departamento = isset($data[5]) ? trim($data[5]) : 'PIURA';
        $provincia = isset($data[6]) ? trim($data[6]) : 'SULLANA';
        $distrito = isset($data[7]) ? trim($data[7]) : 'SULLANA';
        $ubigeo = isset($data[8]) ? trim($data[8]) : '';
        $direccion = isset($data[9]) ? trim($data[9]) : '';
        $tiene_discapacidad = isset($data[10]) && stripos($data[10], 'SI') !== false ? 1 : 0;
        $tiene_certificado_discapacidad = isset($data[11]) && stripos($data[11], 'SI') !== false ? 1 : 0;
        $tiene_informe_psicopedagogico = isset($data[12]) && stripos($data[12], 'SI') !== false ? 1 : 0;
        $peso = isset($data[13]) ? trim($data[13]) : '';
        $talla = isset($data[14]) ? trim($data[14]) : '';
        $tiene_carnet_vacunacion = isset($data[15]) && stripos($data[15], 'SI') !== false ? 1 : 0;
        $seguro = isset($data[16]) ? trim($data[16]) : '';
        $dosis_covid = isset($data[17]) ? trim($data[17]) : '';
        $alergias = isset($data[18]) ? trim($data[18]) : '';
        $detalle_alergias = isset($data[19]) ? trim($data[19]) : '';
        $experiencias_traumaticas = isset($data[20]) ? trim($data[20]) : '';
        $tipo_sangre = isset($data[21]) ? trim($data[21]) : '';
        $tiene_hermanas = isset($data[22]) && stripos($data[22], 'SI') !== false ? 1 : 0;
        $es_representante_legal = isset($data[23]) && stripos($data[23], 'SI') !== false ? 1 : 0;
        $dni_papa = isset($data[24]) ? trim($data[24]) : '';
        $nombre_papa = isset($data[25]) ? trim($data[25]) : '';
        $celular_papa = isset($data[26]) ? trim($data[26]) : '';
        $dni_mama = isset($data[27]) ? trim($data[27]) : '';
        $nombre_mama = isset($data[28]) ? trim($data[28]) : '';
        $celular_mama = isset($data[29]) ? trim($data[29]) : '';
        $nivel_2025 = isset($data[30]) ? trim($data[30]) : '';
        $grado_2025 = isset($data[31]) ? trim($data[31]) : '';
        $seccion_2025 = isset($data[32]) ? trim($data[32]) : 'A';
        
        // Limpiar y validar DNI - TOLERANTE
        $dni = preg_replace('/[^0-9]/', '', $dni); // Solo números
        if (strlen($dni) > 8) {
            $dni = substr($dni, 0, 8); // Truncar a 8 dígitos
        }
        
        // Si el DNI es inválido, generar uno temporal
        if (empty($dni) || strlen($dni) < 8) {
            $dni = '99' . str_pad($total, 6, '0', STR_PAD_LEFT); // DNI temporal: 99000001, 99000002, etc
            $errores_detalle[] = "Fila $total: DNI inválido generado temporal ($dni) - $nombre_completo";
        }
        
        // YA NO VERIFICAMOS DNI DUPLICADO - Permitir hermanas con mismo DNI de representante
        
        // Si el nombre está vacío, usar "SIN NOMBRE"
        if (empty($nombre_completo)) {
            $nombre_completo = "SIN NOMBRE FILA $total";
            $errores_detalle[] = "Fila $total: Nombre vacío - asignado temporal";
        }
        
        // Separar nombres - TOLERANTE
        $partes_nombre = explode(' ', $nombre_completo);
        if (count($partes_nombre) >= 3) {
            $apellido_paterno = $partes_nombre[0];
            $apellido_materno = $partes_nombre[1];
            $nombres = implode(' ', array_slice($partes_nombre, 2));
        } elseif (count($partes_nombre) == 2) {
            $apellido_paterno = $partes_nombre[0];
            $apellido_materno = '';
            $nombres = $partes_nombre[1];
        } else {
            $apellido_paterno = $nombre_completo;
            $apellido_materno = '';
            $nombres = '';
        }
        
        // Convertir fecha - TOLERANTE
        if (strpos($fecha_nacimiento, '/') !== false) {
            $partes_fecha = explode('/', $fecha_nacimiento);
            if (count($partes_fecha) == 3) {
                $fecha_nacimiento = $partes_fecha[2] . '-' . $partes_fecha[1] . '-' . $partes_fecha[0];
            }
        }
        // Si la fecha es inválida, usar una fecha por defecto
        if (empty($fecha_nacimiento) || !strtotime($fecha_nacimiento)) {
            $fecha_nacimiento = '2015-01-01'; // Fecha por defecto
        }
        
        // PROMOCIÓN DE GRADO 2025 -> 2026 - TOLERANTE
        $nivel_2026 = '';
        $grado_2026 = '';
        
        // Si no hay nivel, intentar inferir por edad o poner por defecto
        if (empty($nivel_2025)) {
            $nivel_2025 = 'PRIMARIA'; // Por defecto
            $grado_2025 = '1°';
        }
        
        if (stripos($nivel_2025, 'INICIAL') !== false) {
            if (stripos($grado_2025, '5') !== false || stripos($grado_2025, 'cinco') !== false) {
                // 5 años inicial -> 1° primaria
                $nivel_2026 = 'PRIMARIA';
                $grado_2026 = '1°';
            } elseif (stripos($grado_2025, '4') !== false || stripos($grado_2025, 'cuatro') !== false) {
                // 4 años -> 5 años inicial
                $nivel_2026 = 'INICIAL';
                $grado_2026 = '5 años';
            } elseif (stripos($grado_2025, '3') !== false || stripos($grado_2025, 'tres') !== false) {
                // 3 años -> 4 años inicial
                $nivel_2026 = 'INICIAL';
                $grado_2026 = '4 años';
            } else {
                // Por defecto, si no se puede determinar, poner 4 años
                $nivel_2026 = 'INICIAL';
                $grado_2026 = '4 años';
            }
        } elseif (stripos($nivel_2025, 'PRIMARIA') !== false) {
            if (stripos($grado_2025, '6') !== false) {
                // 6° primaria -> 1° secundaria
                $nivel_2026 = 'SECUNDARIA';
                $grado_2026 = '1°';
            } else {
                // Incrementar grado en primaria
                $nivel_2026 = 'PRIMARIA';
                if (stripos($grado_2025, '1') !== false) $grado_2026 = '2°';
                else if (stripos($grado_2025, '2') !== false) $grado_2026 = '3°';
                else if (stripos($grado_2025, '3') !== false) $grado_2026 = '4°';
                else if (stripos($grado_2025, '4') !== false) $grado_2026 = '5°';
                else if (stripos($grado_2025, '5') !== false) $grado_2026 = '6°';
                else $grado_2026 = '2°';
            }
        } elseif (stripos($nivel_2025, 'SECUNDARIA') !== false) {
            if (stripos($grado_2025, '5') !== false) {
                // 5° secundaria -> EXALUMNOS (no importar)
                $exalumnos++;
                continue;
            } else {
                // Incrementar grado en secundaria
                $nivel_2026 = 'SECUNDARIA';
                if (stripos($grado_2025, '1') !== false) $grado_2026 = '2°';
                else if (stripos($grado_2025, '2') !== false) $grado_2026 = '3°';
                else if (stripos($grado_2025, '3') !== false) $grado_2026 = '4°';
                else if (stripos($grado_2025, '4') !== false) $grado_2026 = '5°';
                else $grado_2026 = '2°';
            }
        } else {
            // Si no se puede determinar el nivel, usar PRIMARIA 1° por defecto
            $nivel_2026 = 'PRIMARIA';
            $grado_2026 = '1°';
            $errores_detalle[] = "Fila $total: Nivel desconocido ($nivel_2025) - asignado PRIMARIA 1°";
        }
        
        // Limpiar sección
        $seccion_2026 = trim($seccion_2025);
        
        // Para inicial, mantener la sección como está (puede ser palabra/frase)
        if ($nivel_2026 == 'INICIAL') {
            // Mantener sección completa para inicial
            if (empty($seccion_2026)) {
                $seccion_2026 = 'AMOROSOS';
            }
        } else {
            // Para primaria y secundaria, solo la primera letra
            $seccion_2026 = strtoupper(substr($seccion_2026, 0, 1));
            if (!in_array($seccion_2026, ['A', 'B', 'C', 'D', 'E'])) {
                $seccion_2026 = 'A';
            }
        }
        
        // Insertar estudiante
        $estudiante_id = insert(
            "INSERT INTO estudiantes (dni, nombres, apellido_paterno, apellido_materno, fecha_nacimiento, sexo, pais, departamento, provincia, distrito, ubigeo, direccion, tiene_discapacidad, tiene_certificado_discapacidad, tiene_informe_psicopedagogico, nivel, grado, seccion, created_by, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())",
            [$dni, $nombres, $apellido_paterno, $apellido_materno, $fecha_nacimiento, $sexo, $pais, $departamento, $provincia, $distrito, $ubigeo, $direccion, $tiene_discapacidad, $tiene_certificado_discapacidad, $tiene_informe_psicopedagogico, $nivel_2026, $grado_2026, $seccion_2026]
        );
        
        // Insertar datos de salud
        if ($estudiante_id) {
            $tiene_alergias = (stripos($alergias, 'NINGUNA') === false && !empty($alergias) && stripos($alergias, 'SI') !== false) ? 1 : 0;
            
            // Limpiar dosis COVID
            $dosis_limpia = 0;
            if (is_numeric($dosis_covid)) {
                $dosis_limpia = (int)$dosis_covid;
            } elseif (stripos($dosis_covid, 'NINGUNA') === false && !empty($dosis_covid)) {
                $dosis_limpia = 2; // Valor por defecto
            }
            
            // Mapear seguro de salud
            $seguro_mapeado = 'NINGUNO';
            if (stripos($seguro, 'SIS') !== false) $seguro_mapeado = 'SIS';
            elseif (stripos($seguro, 'ESSALUD') !== false) $seguro_mapeado = 'ESSALUD';
            elseif (stripos($seguro, 'PRIVADO') !== false || stripos($seguro, 'OTROS') !== false) $seguro_mapeado = 'PRIVADO';
            
            insert(
                "INSERT INTO salud_estudiantes (estudiante_id, esquema_vacunas, tiene_carnet_vacunacion, dosis_covid, peso_kg, talla_cm, seguro_salud, grupo_sanguineo, tiene_alergias, detalle_alergias, experiencias_traumaticas, created_by) 
                 VALUES (?, 'COMPLETO', ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                [$estudiante_id, $tiene_carnet_vacunacion, $dosis_limpia, $peso ?: 0, $talla ?: 0, $seguro_mapeado, $tipo_sangre ?: 'O+', $tiene_alergias, $detalle_alergias, $experiencias_traumaticas]
            );
            
            // Insertar representantes
            // Limpiar DNIs de representantes
            $dni_mama = preg_replace('/[^0-9]/', '', $dni_mama);
            $dni_papa = preg_replace('/[^0-9]/', '', $dni_papa);
            $celular_mama = preg_replace('/[^0-9]/', '', $celular_mama);
            $celular_papa = preg_replace('/[^0-9]/', '', $celular_papa);
            
            // Truncar si son muy largos
            if (strlen($dni_mama) > 8) $dni_mama = substr($dni_mama, 0, 8);
            if (strlen($dni_papa) > 8) $dni_papa = substr($dni_papa, 0, 8);
            if (strlen($celular_mama) > 15) $celular_mama = substr($celular_mama, 0, 15);
            if (strlen($celular_papa) > 15) $celular_papa = substr($celular_papa, 0, 15);
            
            // Madre
            if (!empty($dni_mama) && strlen($dni_mama) >= 8) {
                $madre_id = insert(
                    "INSERT INTO representantes (dni, nombres, apellido_paterno, apellido_materno, parentesco, celular, whatsapp, created_by) 
                     VALUES (?, ?, '', '', 'MADRE', ?, ?, 1)",
                    [$dni_mama, $nombre_mama, $celular_mama, $celular_mama]
                );
                
                if ($madre_id) {
                    insert(
                        "INSERT INTO estudiante_representante (estudiante_id, representante_id, es_principal) VALUES (?, ?, 1)",
                        [$estudiante_id, $madre_id]
                    );
                }
            }
            
            // Padre
            if (!empty($dni_papa) && strlen($dni_papa) >= 8) {
                $padre_id = insert(
                    "INSERT INTO representantes (dni, nombres, apellido_paterno, apellido_materno, parentesco, celular, whatsapp, created_by) 
                     VALUES (?, ?, '', '', 'PADRE', ?, ?, 1)",
                    [$dni_papa, $nombre_papa, $celular_papa, $celular_papa]
                );
                
                if ($padre_id) {
                    insert(
                        "INSERT INTO estudiante_representante (estudiante_id, representante_id, es_principal) VALUES (?, ?, 0)",
                        [$estudiante_id, $padre_id]
                    );
                }
            }
            
            $importados++;
            if ($importados % 50 == 0) {
                echo "   ✓ Importados: $importados estudiantes...\n";
            }
        }
        
    } catch (Exception $e) {
        $errores_detalle[] = "Fila $total: " . $e->getMessage();
        $errores++;
    }
}

fclose($handle);

echo "\n=== RESUMEN DE IMPORTACIÓN ===\n";
echo "Total de filas procesadas: $total\n";
echo "Estudiantes importados: $importados\n";
echo "Exalumnos (5° secundaria): $exalumnos\n";
echo "Errores: $errores\n";

if ($errores > 0 && count($errores_detalle) > 0) {
    echo "\n=== DETALLE DE ERRORES (primeros 20) ===\n";
    $mostrar = array_slice($errores_detalle, 0, 20);
    foreach ($mostrar as $error) {
        echo "   • $error\n";
    }
    if (count($errores_detalle) > 20) {
        echo "   ... y " . (count($errores_detalle) - 20) . " errores más\n";
    }
}

echo "\n✓ Importación completada!\n";
