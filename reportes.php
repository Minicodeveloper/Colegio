<?php
$pageTitle = 'Reportes 2026';
include 'includes/header.php';
require_once 'config/database.php';

// Obtener estadísticas
$total_estudiantes = fetchOne("SELECT COUNT(*) as total FROM estudiantes")['total'];
$total_primaria = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE nivel = 'PRIMARIA'")['total'];
$total_secundaria = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE nivel = 'SECUNDARIA'")['total'];
$total_inicial = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE nivel = 'INICIAL'")['total'];

// Estadísticas por sección
$por_seccion = fetchAll("SELECT seccion, COUNT(*) as total FROM estudiantes GROUP BY seccion ORDER BY seccion");

// Estadísticas de salud
$con_seguro = fetchOne("SELECT COUNT(*) as total FROM salud_estudiantes WHERE seguro_salud != 'NINGUNO'")['total'];
$con_alergias = fetchOne("SELECT COUNT(*) as total FROM salud_estudiantes WHERE tiene_alergias = 1")['total'];

// Sacramentos
$con_bautismo = fetchOne("SELECT COUNT(*) as total FROM sacramentos WHERE bautismo = 1")['total'];
$con_comunion = fetchOne("SELECT COUNT(*) as total FROM sacramentos WHERE primera_comunion = 1")['total'];

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'resumen';
?>

<div>
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.5rem;">REPORTES</h2>
        <h3 style="color: var(--primary); font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;">IE LAS CAPULLANAS</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">ANALÍTICA DE GESTIÓN, AUDITORÍA Y PASTORAL 2026</p>
    </div>

    <!-- Tabs -->
    <div style="background: white; padding: 0.5rem; border-radius: 50px; width: fit-content; margin-bottom: 2rem; box-shadow: var(--shadow-sm); display: flex; gap: 0.5rem;">
        <a href="?tab=resumen" class="btn" style="height: 45px; border-radius: 25px; <?php echo $tab == 'resumen' ? 'background: var(--primary); color: white;' : 'background: transparent; color: var(--text-muted);'; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            RESUMEN
        </a>
        <a href="?tab=productividad" class="btn" style="height: 45px; border-radius: 25px; <?php echo $tab == 'productividad' ? 'background: var(--primary); color: white;' : 'background: transparent; color: var(--text-muted);'; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            PRODUCTIVIDAD
        </a>
        <a href="?tab=cronologico" class="btn" style="height: 45px; border-radius: 25px; <?php echo $tab == 'cronologico' ? 'background: var(--primary); color: white;' : 'background: transparent; color: var(--text-muted);'; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            CRONOLÓGICO
        </a>
        <a href="?tab=pastoral" class="btn" style="height: 45px; border-radius: 25px; <?php echo $tab == 'pastoral' ? 'background: var(--primary); color: white;' : 'background: transparent; color: var(--text-muted);'; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
            PASTORAL
        </a>
        <a href="?tab=salud" class="btn" style="height: 45px; border-radius: 25px; <?php echo $tab == 'salud' ? 'background: var(--primary); color: white;' : 'background: transparent; color: var(--text-muted);'; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;"><path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path></svg>
            SALUD
        </a>
        <a href="?tab=documentos" class="btn" style="height: 45px; border-radius: 25px; <?php echo $tab == 'documentos' ? 'background: var(--primary); color: white;' : 'background: transparent; color: var(--text-muted);'; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            DOCUMENTOS
        </a>
        <a href="?tab=faltantes" class="btn" style="height: 45px; border-radius: 25px; <?php echo $tab == 'faltantes' ? 'background: var(--primary); color: white;' : 'background: transparent; color: var(--text-muted);'; ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.5rem;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            FALTANTES
        </a>
    </div>

    <?php if ($tab == 'resumen'): ?>
        <!-- Resumen General -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div style="background: #f8fafc; border-radius: 20px; padding: 3rem; text-align: center;">
                <svg viewBox="0 0 24 24" width="64" height="64" stroke="currentColor" stroke-width="2" fill="none" style="color: #94a3b8; margin: 0 auto 1rem;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-muted); margin-bottom: 1rem; text-transform: uppercase;">Analítica Operativa</h3>
                <p style="color: var(--text-light); font-size: 0.9rem;">Genera reportes automatizados por Excel para la gestión de la I.E. Las Capullanas 2026</p>
            </div>
            
            <div style="background: #1e293b; border-radius: 20px; padding: 3rem; text-align: center; color: white; position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                <div style="position: relative; z-index: 2;">
                    <svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" stroke-width="2" fill="none" style="margin: 0 auto 1rem;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    <h3 style="font-size: 0.9rem; font-weight: 700; color: #94a3b8; margin-bottom: 0.5rem; text-transform: uppercase;">Total Consolidados</h3>
                    <div style="font-size: 4rem; font-weight: 800; line-height: 1;"><?php echo $total_estudiantes; ?></div>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">PERIODO LECTIVO 2026</p>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
            <div style="background: white; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-sm);">
                <div style="color: #6366f1; font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;"><?php echo $total_inicial; ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Inicial</div>
            </div>
            <div style="background: white; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-sm);">
                <div style="color: #10b981; font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;"><?php echo $total_primaria; ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Primaria</div>
            </div>
            <div style="background: white; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-sm);">
                <div style="color: #f59e0b; font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;"><?php echo $total_secundaria; ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Secundaria</div>
            </div>
            <div style="background: white; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-sm);">
                <div style="color: #ef4444; font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;"><?php echo count($por_seccion); ?></div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">Secciones</div>
            </div>
        </div>

    <?php elseif ($tab == 'productividad'): ?>
        <div style="background: white; border-radius: 20px; padding: 3rem; box-shadow: var(--shadow);">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none" style="color: var(--primary);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                <div>
                    <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--primary);">PRODUCTIVIDAD</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Control de personal, cantidad de procesos realizados por día y aula</p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <button class="btn" style="background: #4f46e5; color: white; height: 60px; display: flex; align-items: center; justify-content: center; gap: 1rem; font-size: 1rem;">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    PDF
                </button>
                <button class="btn" style="background: #10b981; color: white; height: 60px; display: flex; align-items: center; justify-content: center; gap: 1rem; font-size: 1rem;">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="9"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                    XLS
                </button>
            </div>
        </div>

    <?php elseif ($tab == 'cronologico'): ?>
        <div style="background: white; border-radius: 20px; padding: 3rem; box-shadow: var(--shadow);">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none" style="color: #2563eb;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <div>
                    <h3 style="font-size: 1.3rem; font-weight: 800; color: #2563eb;">CRONOLÓGICO</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Auditoría secuencial de registros con hora y responsable</p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <button class="btn" style="background: #1e293b; color: white; height: 60px; display: flex; align-items: center; justify-content: center; gap: 1rem; font-size: 1rem;">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
                    PDF
                </button>
                <button class="btn" style="background: #10b981; color: white; height: 60px; display: flex; align-items: center; justify-content: center; gap: 1rem; font-size: 1rem;">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>
                    XLS
                </button>
            </div>
        </div>

    <?php elseif ($tab == 'pastoral'): ?>
        <div style="background: white; border-radius: 20px; padding: 3rem; box-shadow: var(--shadow);">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none" style="color: #8b5cf6;"><path d="M3 21h18M5 21V7l8-4 8 4v14M10 9a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v3"></path></svg>
                <div>
                    <h3 style="font-size: 1.3rem; font-weight: 800; color: #8b5cf6;">PASTORAL</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Gestión espiritual, incluye sacramentos y estado matrimonial de padres</p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: 800; color: #8b5cf6; margin-bottom: 0.5rem;"><?php echo $con_bautismo; ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700;">CON BAUTISMO</div>
                </div>
                <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: 800; color: #8b5cf6; margin-bottom: 0.5rem;"><?php echo $con_comunion; ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700;">PRIMERA COMUNIÓN</div>
                </div>
                <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: 800; color: #8b5cf6; margin-bottom: 0.5rem;"><?php echo $total_estudiantes - $con_bautismo; ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700;">SIN BAUTISMO</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <button class="btn" style="background: #8b5cf6; color: white; height: 60px;">PDF</button>
                <button class="btn" style="background: #10b981; color: white; height: 60px;">XLS</button>
            </div>
        </div>

    <?php elseif ($tab == 'salud'): ?>
        <div style="background: white; border-radius: 20px; padding: 3rem; box-shadow: var(--shadow);">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none" style="color: #ef4444;"><path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path></svg>
                <div>
                    <h3 style="font-size: 1.3rem; font-weight: 800; color: #ef4444;">SALUD</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Datos médicos, alergias, seguros y vacunación</p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: #fef2f2; padding: 1.5rem; border-radius: 12px; text-align: center; border: 1px solid #fecaca;">
                    <div style="font-size: 2rem; font-weight: 800; color: #ef4444; margin-bottom: 0.5rem;"><?php echo $con_seguro; ?></div>
                    <div style="font-size: 0.8rem; color: #991b1b; font-weight: 700;">CON SEGURO</div>
                </div>
                <div style="background: #fef2f2; padding: 1.5rem; border-radius: 12px; text-align: center; border: 1px solid #fecaca;">
                    <div style="font-size: 2rem; font-weight: 800; color: #ef4444; margin-bottom: 0.5rem;"><?php echo $con_alergias; ?></div>
                    <div style="font-size: 0.8rem; color: #991b1b; font-weight: 700;">CON ALERGIAS</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <button class="btn" style="background: #ef4444; color: white; height: 60px;">PDF</button>
                <button class="btn" style="background: #10b981; color: white; height: 60px;">XLS</button>
            </div>
        </div>

    <?php elseif ($tab == 'documentos'): ?>
        <?php
        // Obtener estudiantes con documentos faltantes
        $estudiantes_docs = fetchAll(
            "SELECT e.*, CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo,
             (SELECT COUNT(*) FROM documentos d WHERE d.estudiante_id = e.id AND d.tiene_documento = 0) as docs_faltantes
             FROM estudiantes e
             ORDER BY docs_faltantes DESC"
        );
        ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <!-- Expedientes -->
            <div style="background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow);">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none" style="color: #10b981;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 800; color: #10b981;">EXPEDIENTES</h3>
                        <p style="color: var(--text-muted); font-size: 0.75rem;">Nómina seguimiento de expedientes físicos entregados</p>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button class="btn" style="background: #10b981; color: white; height: 55px;">PDF</button>
                    <button class="btn" style="background: #10b981; color: white; height: 55px;">XLS</button>
                </div>
            </div>

            <!-- Ratificaciones -->
            <div style="background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow);">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none" style="color: #f97316;"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 800; color: #f97316;">RATIFICACIONES</h3>
                        <p style="color: var(--text-muted); font-size: 0.75rem;">Control de documentos para alumnas antiguas</p>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button class="btn" style="background: #f97316; color: white; height: 55px;">PDF</button>
                    <button class="btn" style="background: #10b981; color: white; height: 55px;">XLS</button>
                </div>
            </div>
        </div>

    <?php elseif ($tab == 'faltantes'): ?>
        <?php
        // Obtener estudiantes con documentos faltantes
        $faltantes = fetchAll(
            "SELECT e.*, CONCAT(e.nombres, ' ', e.apellido_paterno, ' ', e.apellido_materno) as nombre_completo,
             GROUP_CONCAT(d.tipo_documento SEPARATOR ', ') as documentos_faltantes
             FROM estudiantes e
             LEFT JOIN documentos d ON e.id = d.estudiante_id AND d.tiene_documento = 0
             GROUP BY e.id
             HAVING documentos_faltantes IS NOT NULL
             ORDER BY e.apellido_paterno
             LIMIT 10"
        );
        ?>
        <div style="background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow); margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none" style="color: #ef4444;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                <div>
                    <h3 style="font-size: 1.3rem; font-weight: 800; color: #ef4444;">DOC. FALTANTES</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Lista de alumnas con requisitos mandatorios pendientes (con cobrar carta notarial)</p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                <button class="btn" style="background: #dc2626; color: white; height: 55px;">PDF</button>
                <button class="btn" style="background: #10b981; color: white; height: 55px;">XLS</button>
            </div>
        </div>

        <!-- Tabla de Faltantes -->
        <div style="background: #dc2626; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem;">
            <div style="background: white; border-radius: 12px; padding: 1rem; display: flex; align-items: center; justify-content: space-between;">
                <div style="font-weight: 800; color: #dc2626; text-transform: uppercase; font-size: 0.9rem;">
                    DETALLE DE DOCUMENTOS FALTANTES (MANDATORIOS)
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">
                    SE EXCLUYE CARTA PODER NOTARIAL DE ESTE ANÁLISIS
                </div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ALUMNA</th>
                    <th>AULA</th>
                    <th style="color: #dc2626;">DOCUMENTOS FALTANTES</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($faltantes) > 0): ?>
                    <?php foreach ($faltantes as $est): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700;"><?php echo $est['nombre_completo']; ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">DNI: <?php echo $est['dni']; ?></div>
                        </td>
                        <td style="font-weight: 700; color: var(--text-muted);"><?php echo $est['grado']; ?> "<?php echo $est['seccion']; ?>"</td>
                        <td>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                <?php 
                                $docs = explode(', ', $est['documentos_faltantes']);
                                foreach ($docs as $doc): 
                                ?>
                                <span style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.7rem; font-weight: 700;">
                                    <?php echo str_replace('_', ' ', $doc); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" stroke-width="2" fill="none" style="margin: 0 auto 1rem; color: #10b981;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <p style="font-weight: 700;">¡Excelente! No hay documentos faltantes</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div style="background: white; border-radius: 20px; padding: 3rem; text-align: center;">
            <p style="color: var(--text-muted);">Selecciona una categoría de reporte</p>
        </div>
    <?php endif; ?>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
