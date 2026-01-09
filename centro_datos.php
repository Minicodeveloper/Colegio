<?php
$pageTitle = 'Centro de Datos';
include 'includes/header.php';
?>

<div>
    <div class="section-title orange">
        CENTRO DE IMPORTACIÓN 2026
    </div>
    <p class="page-subtitle" style="margin-top: -1.5rem; margin-bottom: 2rem;">REDISTRIBUCIÓN AUTOMÁTICA EQUITATIVA PARA INICIAL, PRIMARIA Y SECUNDARIA</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
        
        <!-- Card 1 -->
        <div class="form-card" style="padding: 3rem; display: flex; flex-direction: column; align-items: center; text-align: center; min-height: 300px;">
            <div style="width: 60px; height: 60px; background: #fff7ed; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ea580c; margin-bottom: 1.5rem;">
                <svg viewBox="0 0 24 24" width="30" height="30" stroke="currentColor" stroke-width="2" fill="none"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
            </div>
            <h3 style="margin-bottom: 2rem; font-weight: 800; font-size: 1rem;">BD MATRÍCULA 2025</h3>
            <button class="btn" style="background: #ea580c; color: white; border-radius: 8px; font-size: 0.8rem; padding: 0.75rem 2rem;">SUBIR BD 2025</button>
        </div>

        <!-- Card 2 -->
        <div class="form-card" style="padding: 3rem; display: flex; flex-direction: column; align-items: center; text-align: center; min-height: 300px;">
            <div style="width: 60px; height: 60px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #2563eb; margin-bottom: 1.5rem;">
                <svg viewBox="0 0 24 24" width="30" height="30" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <h3 style="margin-bottom: 2rem; font-weight: 800; font-size: 1rem;">LISTA ESTUDIANTES 2025</h3>
            <button class="btn" style="background: #2563eb; color: white; border-radius: 8px; font-size: 0.8rem; padding: 0.75rem 2rem;">SUBIR LISTA 2025</button>
        </div>

    </div>

    <!-- Black Section -->
    <div style="background: #0f172a; border-radius: 24px; padding: 3rem; color: white; margin-top: 3rem; position: relative; overflow: hidden;">
        <div style="position: relative; z-index: 2;">
            <div style="display: flex; align-items: center; gap: 0.5rem; color: #10b981; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 1rem;">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                Algoritmo de Listas Parejas
            </div>
            <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 1rem;">CRUZAR Y EQUILIBRAR SECCIONES</h2>
            <p style="color: #94a3b8; max-width: 600px; font-size: 0.9rem; margin-bottom: 2rem;">
                Este proceso redistribuye a las alumnas de forma <strong style="color: white;">perfectamente balanceada</strong> en las nuevas aulas de Inicial 2026, 4 años (2 aulas) y 5 años (4 aulas) recibirán el mismo número de estudiantes para asegurar la estabilidad académica.
            </p>
        </div>
        <div style="position: absolute; right: 3rem; top: 50%; transform: translateY(-50%); z-index: 2;">
            <button style="background: #334155; color: #94a3b8; border: none; padding: 1rem 2rem; border-radius: 12px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">Procesar Listas Parejas</button>
        </div>
    </div>
</div>

</div> <!-- End Content Wrapper -->
</main>
</div>
</body>
</html>
