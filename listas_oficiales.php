<?php
$pageTitle = 'Listas Oficiales 2026';
include 'includes/header.php';
require_once 'config/database.php';

// Filtros
$nivel_filter = isset($_GET['nivel']) ? $_GET['nivel'] : 'SECUNDARIA';
$grado_filter = isset($_GET['grado']) ? $_GET['grado'] : '';
$seccion_filter = isset($_GET['seccion']) ? $_GET['seccion'] : '';

// Obtener grados únicos por nivel
$grados = fetchAll("SELECT DISTINCT grado FROM estudiantes WHERE nivel = ? ORDER BY grado", [$nivel_filter]);

// Obtener secciones por grado
$secciones = [];
if ($grado_filter) {
    $secciones_data = fetchAll(
        "SELECT DISTINCT seccion, COUNT(*) as total 
         FROM estudiantes 
         WHERE nivel = ? AND grado = ? 
         GROUP BY seccion 
         ORDER BY seccion",
        [$nivel_filter, $grado_filter]
    );
    foreach ($secciones_data as $sec) {
        $secciones[] = $sec;
    }
}
?>

<div>
    <div class="page-header">
        <div>
            <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.5rem;">LISTAS OFICIALES</h2>
            <h3 style="color: var(--primary); font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;">PERIODO 2026</h3>
            <p class="page-subtitle">ORGANIZACIÓN ALFABÉTICA Y CONTROL DE ESTADO POR SECCIÓN</p>
        </div>
        <button class="btn" style="background: #1e293b; color: white; display: flex; align-items: center; gap: 0.5rem;">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            IMPRIMIR LISTAS COMPLETAS
        </button>
    </div>

    <!-- Filters -->
    <div style="background: white; padding: 1.5rem; border-radius: 16px; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">NIVEL EDUCATIVO</label>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="?nivel=INICIAL" class="btn" style="flex: 1; height: 45px; <?php echo $nivel_filter == 'INICIAL' ? 'background: var(--primary); color: white;' : 'background: #f1f5f9; color: var(--text-muted);'; ?>">INICIAL</a>
                    <a href="?nivel=PRIMARIA" class="btn" style="flex: 1; height: 45px; <?php echo $nivel_filter == 'PRIMARIA' ? 'background: var(--primary); color: white;' : 'background: #f1f5f9; color: var(--text-muted);'; ?>">PRIMARIA</a>
                    <a href="?nivel=SECUNDARIA" class="btn" style="flex: 1; height: 45px; <?php echo $nivel_filter == 'SECUNDARIA' ? 'background: var(--primary); color: white;' : 'background: #f1f5f9; color: var(--text-muted);'; ?>">SECUNDARIA</a>
                </div>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">SELECCIONAR GRADO</label>
                <select name="grado" class="form-control" onchange="window.location.href='?nivel=<?php echo $nivel_filter; ?>&grado=' + this.value">
                    <option value="">-- TODOS LOS GRADOS --</option>
                    <?php foreach ($grados as $g): ?>
                        <option value="<?php echo $g['grado']; ?>" <?php echo $grado_filter == $g['grado'] ? 'selected' : ''; ?>><?php echo $g['grado']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">SECCIÓN</label>
                <select name="seccion" class="form-control" onchange="window.location.href='?nivel=<?php echo $nivel_filter; ?>&grado=<?php echo $grado_filter; ?>&seccion=' + this.value">
                    <option value="">-- TODAS LAS SECCIONES --</option>
                    <?php foreach ($secciones as $s): ?>
                        <option value="<?php echo $s['seccion']; ?>" <?php echo $seccion_filter == $s['seccion'] ? 'selected' : ''; ?>>SECCIÓN <?php echo $s['seccion']; ?> (<?php echo $s['total']; ?> estudiantes)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Secciones Cards -->
    <?php if ($grado_filter && !$seccion_filter): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 2rem;">
            <?php foreach ($secciones as $sec): 
                $estudiantes_seccion = fetchAll(
                    "SELECT e.*, CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo 
                     FROM estudiantes e 
                     WHERE e.nivel = ? AND e.grado = ? AND e.seccion = ?
                     ORDER BY e.apellido_paterno, e.apellido_materno",
                    [$nivel_filter, $grado_filter, $sec['seccion']]
                );
            ?>
            <div style="background: #1e293b; border-radius: 20px; padding: 2rem; color: white; position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                
                <div style="position: relative; z-index: 2;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <div>
                            <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 0.25rem;"><?php echo $grado_filter; ?> - <?php echo $sec['seccion']; ?></h3>
                            <p style="color: #94a3b8; font-size: 0.85rem;"><?php echo $sec['total']; ?> ESTUDIANTES TOTALES</p>
                        </div>
                        <div style="background: #ef4444; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem;">
                            <?php echo $sec['total']; ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1rem; max-height: 300px; overflow-y: auto;">
                        <?php foreach ($estudiantes_seccion as $idx => $est): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: rgba(255,255,255,0.03); border-radius: 8px; margin-bottom: 0.5rem;">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.9rem;"><?php echo ($idx + 1); ?>. <?php echo $est['nombre_completo']; ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">DNI: <?php echo $est['dni']; ?></div>
                                </div>
                                <span style="background: #fbbf24; color: #1e293b; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.7rem; font-weight: 700;">POR RATIFICAR</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($seccion_filter): 
        // Mostrar lista detallada de una sección específica
        $estudiantes = fetchAll(
            "SELECT e.*, CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo 
             FROM estudiantes e 
             WHERE e.nivel = ? AND e.grado = ? AND e.seccion = ?
             ORDER BY e.apellido_paterno, e.apellido_materno",
            [$nivel_filter, $grado_filter, $seccion_filter]
        );
    ?>
        <div style="background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow);">
            <h3 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; color: var(--primary);">
                <?php echo $grado_filter; ?> - SECCIÓN <?php echo $seccion_filter; ?>
            </h3>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>APELLIDOS Y NOMBRES</th>
                        <th>DNI</th>
                        <th>ESTADO</th>
                        <th style="text-align: center;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estudiantes as $idx => $est): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--primary);"><?php echo ($idx + 1); ?></td>
                        <td style="font-weight: 700;"><?php echo $est['nombre_completo']; ?></td>
                        <td><?php echo $est['dni']; ?></td>
                        <td><span class="status-badge status-pending">POR RATIFICAR</span></td>
                        <td style="text-align: center;">
                            <a href="ficha.php?dni=<?php echo $est['dni']; ?>" target="_blank" class="btn" style="background: var(--primary); color: white; height: 36px; padding: 0 1rem; font-size: 0.75rem;">
                                VER FICHA
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem; color: var(--text-muted);">
            <svg viewBox="0 0 24 24" width="64" height="64" stroke="currentColor" stroke-width="2" fill="none" style="margin: 0 auto 1rem; opacity: 0.3;"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            <p style="font-size: 1.1rem; font-weight: 600;">Selecciona un grado para ver las listas oficiales</p>
        </div>
    <?php endif; ?>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
