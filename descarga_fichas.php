<?php
$pageTitle = 'Descargador de Fichas';
include 'includes/header.php';
require_once 'config/database.php';

// Filtros
$nivel_filter = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$grado_filter = isset($_GET['grado']) ? $_GET['grado'] : '';
$seccion_filter = isset($_GET['seccion']) ? $_GET['seccion'] : '';

// Obtener niveles únicos
$niveles = fetchAll("SELECT DISTINCT nivel FROM estudiantes ORDER BY FIELD(nivel, 'INICIAL', 'PRIMARIA', 'SECUNDARIA')");

// Obtener grados únicos (filtrados por nivel si está seleccionado)
$grados = [];
if ($nivel_filter) {
    $grados = fetchAll("SELECT DISTINCT grado FROM estudiantes WHERE nivel = ? ORDER BY grado", [$nivel_filter]);
} else {
    $grados = fetchAll("SELECT DISTINCT grado, nivel FROM estudiantes ORDER BY FIELD(nivel, 'INICIAL', 'PRIMARIA', 'SECUNDARIA'), grado");
}

// Obtener secciones únicas (filtradas por nivel y grado si están seleccionados)
$secciones = [];
if ($nivel_filter && $grado_filter) {
    $secciones = fetchAll("SELECT DISTINCT seccion FROM estudiantes WHERE nivel = ? AND grado = ? ORDER BY seccion", [$nivel_filter, $grado_filter]);
} elseif ($nivel_filter) {
    $secciones = fetchAll("SELECT DISTINCT seccion FROM estudiantes WHERE nivel = ? ORDER BY seccion", [$nivel_filter]);
} else {
    $secciones = fetchAll("SELECT DISTINCT seccion FROM estudiantes ORDER BY seccion");
}

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
     ORDER BY e.nivel, e.grado, e.seccion, e.apellido_paterno",
    $params
);
?>

<div>
    <div class="page-header">
        <div>
            <h2 class="section-title" style="color: var(--primary); margin-bottom: 0.5rem;">DESCARGADOR DE FICHAS 2026</h2>
            <p class="page-subtitle">GENERACIÓN MASIVA DE EXPEDIENTES POR AULA</p>
        </div>
    </div>

    <!-- Filters -->
    <div style="background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
        <form method="GET" style="display: grid; grid-template-columns: repeat(3, 1fr) auto; gap: 1.5rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">NIVEL</label>
                <select name="nivel" class="form-control" onchange="this.form.submit()">
                    <option value="">TODOS</option>
                    <?php foreach ($niveles as $n): ?>
                        <option value="<?php echo $n['nivel']; ?>" <?php echo $nivel_filter == $n['nivel'] ? 'selected' : ''; ?>><?php echo $n['nivel']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">GRADO</label>
                <select name="grado" class="form-control" onchange="this.form.submit()">
                    <option value="">-- SELECCIONE GRADO --</option>
                    <?php foreach ($grados as $g): ?>
                        <option value="<?php echo $g['grado']; ?>" <?php echo $grado_filter == $g['grado'] ? 'selected' : ''; ?>>
                            <?php echo $g['grado']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">SECCIÓN</label>
                <select name="seccion" class="form-control" onchange="this.form.submit()">
                    <option value="">TODAS LAS SECCIONES</option>
                    <?php foreach ($secciones as $s): ?>
                        <option value="<?php echo $s['seccion']; ?>" <?php echo $seccion_filter == $s['seccion'] ? 'selected' : ''; ?>>
                            SECCIÓN <?php echo $s['seccion']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" style="height: 50px;">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                BUSCAR
            </button>
        </form>
    </div>

    <!-- Results Table -->
    <div style="background: #0f172a; border-radius: 20px; padding: 2rem; color: white; margin-bottom: 2rem;">
        <table class="data-table" style="margin: 0;">
            <thead>
                <tr style="border-bottom: 1px solid #334155;">
                    <th style="color: #94a3b8;">ESTUDIANTE</th>
                    <th style="color: #94a3b8;">DNI</th>
                    <th style="color: #94a3b8;">AULA</th>
                    <th style="color: #94a3b8; text-align: center;">ESTADO FICHA</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estudiantes as $est): ?>
                <tr>
                    <td style="background: transparent; color: white; font-weight: 700; border: none; padding: 1rem 1.5rem;">
                        <?php echo $est['nombre_completo']; ?>
                    </td>
                    <td style="background: transparent; color: #94a3b8; border: none; padding: 1rem 1.5rem;">
                        <?php echo $est['dni']; ?>
                    </td>
                    <td style="background: transparent; color: #94a3b8; border: none; padding: 1rem 1.5rem;">
                        <?php echo $est['grado']; ?> - <?php echo $est['seccion']; ?>
                    </td>
                    <td style="background: transparent; border: none; padding: 1rem 1.5rem; text-align: center;">
                        <a href="ficha.php?dni=<?php echo $est['dni']; ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 0.5rem; background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 20px; text-decoration: none; font-size: 0.75rem; font-weight: 700;">
                            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            VER FICHA
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (count($estudiantes) == 0): ?>
            <div style="text-align: center; padding: 3rem; color: #64748b;">
                <svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" stroke-width="2" fill="none" style="margin-bottom: 1rem; opacity: 0.5;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <p>No se encontraron estudiantes con los filtros seleccionados</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (count($estudiantes) > 0): ?>
        <div style="text-align: center; margin-top: 2rem;">
            <p style="color: var(--text-muted); margin-bottom: 1rem;">Total de fichas: <strong><?php echo count($estudiantes); ?></strong></p>
            <a href="ficha_masiva.php?nivel=<?php echo urlencode($nivel_filter); ?>&grado=<?php echo urlencode($grado_filter); ?>&seccion=<?php echo urlencode($seccion_filter); ?>" target="_blank" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                IMPRIMIR FICHAS DEL SALÓN (<?php echo count($estudiantes); ?>)
            </a>
        </div>
    <?php endif; ?>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
