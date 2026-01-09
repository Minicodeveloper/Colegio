<?php
// Test de conexión a la base de datos
require_once 'config/database.php';

echo "<h1>Test de Conexión a Base de Datos</h1>";

try {
    // Test 1: Conexión básica
    echo "<h2>✓ Conexión establecida correctamente</h2>";
    echo "<p>Base de datos: <strong>" . DB_NAME . "</strong></p>";
    echo "<p>Usuario: <strong>" . DB_USER . "</strong></p>";
    
    // Test 2: Contar estudiantes
    $total = fetchOne("SELECT COUNT(*) as total FROM estudiantes");
    echo "<h2>✓ Total de estudiantes: " . $total['total'] . "</h2>";
    
    // Test 3: Listar estudiantes
    $estudiantes = fetchAll("SELECT dni, CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) as nombre_completo, grado, seccion FROM estudiantes ORDER BY apellido_paterno");
    echo "<h2>✓ Lista de Estudiantes:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>DNI</th><th>Nombre Completo</th><th>Grado</th><th>Sección</th></tr>";
    foreach ($estudiantes as $est) {
        echo "<tr>";
        echo "<td>" . $est['dni'] . "</td>";
        echo "<td>" . $est['nombre_completo'] . "</td>";
        echo "<td>" . $est['grado'] . "</td>";
        echo "<td>" . $est['seccion'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 style='color: green;'>✓ Todas las pruebas pasaron exitosamente</h2>";
    echo "<p><a href='index.php'>Ir al Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Error: " . $e->getMessage() . "</h2>";
}
?>
