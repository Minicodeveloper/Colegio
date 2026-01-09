<?php
session_start();
$pageTitle = 'Dashboard';
include 'includes/header.php';
require_once 'config/database.php';

// Obtener estadísticas
$total_estudiantes = fetchOne("SELECT COUNT(*) as total FROM estudiantes")['total'];
$total_inicial = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE nivel = 'INICIAL'")['total'];
$total_primaria = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE nivel = 'PRIMARIA'")['total'];
$total_secundaria = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE nivel = 'SECUNDARIA'")['total'];

// Matriculadas 2026 (asumiendo que todas están activas)
$matriculadas_2026 = $total_estudiantes;

// Pendientes hoy (simulado - puedes ajustar según tu lógica)
$pendientes_hoy = 0;

// Nuevos ingresos (estudiantes creados hoy)
$nuevos_ingresos = fetchOne("SELECT COUNT(*) as total FROM estudiantes WHERE DATE(created_at) = CURDATE()")['total'];

// Áreas activas (número de secciones únicas)
$areas_activas = fetchOne("SELECT COUNT(DISTINCT seccion) as total FROM estudiantes")['total'];

// Eficiencia de ratificación (porcentaje de estudiantes ratificadas)
$total_ratificadas = 0; // Ajustar según tu lógica
$eficiencia = $total_estudiantes > 0 ? round(($total_ratificadas / $total_estudiantes) * 100) : 0;

// Distribución por nivel
$distribucion = [
    ['nivel' => 'INICIAL', 'total' => $total_inicial],
    ['nivel' => 'PRIMARIA', 'total' => $total_primaria],
    ['nivel' => 'SECUNDARIA', 'total' => $total_secundaria]
];
?>

<div style="padding: 2rem;">
    <!-- Header -->
    <div style="margin-bottom: 3rem;">
        <div style="color: var(--primary); font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem; letter-spacing: 1px;">
            ⚪ CAPULLANAS CICLO 2.6
        </div>
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
            RESUMEN DE<br>
            <span style="color: var(--primary);">OPERACIONES</span>
        </h1>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
            <div></div>
            <div style="display: flex; gap: 1rem;">
                <button class="btn" style="background: white; color: var(--primary); border: 2px solid var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    CARGA NUEVA MATRÍCULA 2026
                </button>
                <button onclick="window.location.href='nueva_matricula.php'" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    SUBIR DATOS MATRÍCULA
                </button>
            </div>
        </div>
    </div>

    <!-- Métricas principales -->
    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
        <!-- Eficiencia de Ratificación -->
        <div style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-radius: 24px; padding: 2.5rem; color: white; position: relative; overflow: hidden;">
            <div style="position: absolute; top: 20px; right: 20px; opacity: 0.1;">
                <svg viewBox="0 0 24 24" width="120" height="120" stroke="currentColor" stroke-width="1" fill="none">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div style="font-size: 0.75rem; font-weight: 700; margin-bottom: 1rem; opacity: 0.8; letter-spacing: 1px;">
                EFICIENCIA DE RATIFICACIÓN
            </div>
            <div style="font-size: 4rem; font-weight: 800; margin-bottom: 1rem; line-height: 1;">
                <?php echo $eficiencia; ?>%
            </div>
            <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 2rem;">
                AVANCE PERIODO ESCOLAR 2026
            </div>
            <div style="background: rgba(255,255,255,0.1); border-radius: 12px; height: 8px; overflow: hidden;">
                <div style="background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%); height: 100%; width: <?php echo $eficiencia; ?>%; transition: width 1s ease;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 1rem; font-size: 0.75rem; font-weight: 700;">
                <span>0 LOGRADAS</span>
                <span>0 POR RATIFICAR</span>
            </div>
        </div>

        <!-- Matriculadas 2026 -->
        <div style="background: white; border-radius: 24px; padding: 2rem; box-shadow: var(--shadow-sm);">
            <div style="width: 50px; height: 50px; background: #eef2ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="#4f46e5" stroke-width="2" fill="none">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.5rem;">
                MATRICULADAS 2026
            </div>
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--primary);">
                <?php echo number_format($matriculadas_2026); ?>
            </div>
        </div>

        <!-- Nuevos Ingresos -->
        <div style="background: white; border-radius: 24px; padding: 2rem; box-shadow: var(--shadow-sm);">
            <div style="width: 50px; height: 50px; background: #d1fae5; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="#10b981" stroke-width="2" fill="none">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.5rem;">
                NUEVOS INGRESOS
            </div>
            <div style="font-size: 2.5rem; font-weight: 800; color: #10b981;">
                <?php echo $nuevos_ingresos; ?>
            </div>
        </div>
    </div>

    <!-- Segunda fila de métricas -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; margin-bottom: 3rem;">
        <!-- Pendientes Hoy -->
        <div style="background: white; border-radius: 24px; padding: 2rem; box-shadow: var(--shadow-sm);">
            <div style="width: 50px; height: 50px; background: #fee2e2; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="#ef4444" stroke-width="2" fill="none">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.5rem;">
                PENDIENTES HOY
            </div>
            <div style="font-size: 2.5rem; font-weight: 800; color: #ef4444;">
                <?php echo $pendientes_hoy; ?>
            </div>
        </div>

        <!-- Áreas Activas -->
        <div style="background: white; border-radius: 24px; padding: 2rem; box-shadow: var(--shadow-sm);">
            <div style="width: 50px; height: 50px; background: #fef3c7; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="#f59e0b" stroke-width="2" fill="none">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.5rem;">
                ÁREAS ACTIVAS
            </div>
            <div style="font-size: 2.5rem; font-weight: 800; color: #f59e0b;">
                <?php echo str_pad($areas_activas, 2, '0', STR_PAD_LEFT); ?>
            </div>
        </div>
    </div>

    <!-- Distribución por niveles -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Distribución Niveles -->
        <div style="background: white; border-radius: 24px; padding: 2.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
                <div style="width: 4px; height: 24px; background: var(--primary); border-radius: 2px;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800;">DISTRIBUCIÓN NIVELES</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php 
                $colores = ['#4f46e5', '#10b981', '#f59e0b'];
                $i = 0;
                foreach ($distribucion as $nivel): 
                    $porcentaje = $total_estudiantes > 0 ? round(($nivel['total'] / $total_estudiantes) * 100) : 0;
                ?>
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="font-weight: 700; font-size: 0.9rem;"><?php echo $nivel['nivel']; ?></span>
                        <span style="font-weight: 800; color: <?php echo $colores[$i]; ?>;"><?php echo $nivel['total']; ?></span>
                    </div>
                    <div style="background: #f1f5f9; border-radius: 8px; height: 10px; overflow: hidden;">
                        <div style="background: <?php echo $colores[$i]; ?>; height: 100%; width: <?php echo $porcentaje; ?>%; transition: width 1s ease;"></div>
                    </div>
                </div>
                <?php 
                    $i++;
                endforeach; 
                ?>
            </div>
        </div>

        <!-- Logro de Meta 2026 -->
        <div style="background: white; border-radius: 24px; padding: 2.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
                <div style="width: 4px; height: 24px; background: #ef4444; border-radius: 2px;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800;">LOGRO DE META 2026</h3>
            </div>
            
            <div style="text-align: center; padding: 3rem 0;">
                <div style="font-size: 5rem; font-weight: 800; color: var(--primary); line-height: 1;">
                    <?php echo number_format($total_estudiantes); ?>
                </div>
                <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 1rem; font-weight: 600;">
                    ESTUDIANTES REGISTRADAS
                </div>
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #f1f5f9;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.5rem;">META ANUAL</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #10b981;">2,000</div>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
