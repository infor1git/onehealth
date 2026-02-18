<?php
// Evita acesso direto
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Dashboard - One Health</h1>
    <hr class="wp-header-end">

    <div class="postbox" style="padding: 20px; text-align: center; margin-top: 20px;">
        <h2>Bem-vindo ao One Health</h2>
        <p style="color: #666; font-size: 16px;">
            Utilize o menu lateral para gerenciar suas Unidades, Especialidades, Corpo Clínico e Agendamentos.
        </p>
        <div style="margin-top: 20px;">
            <a href="<?php echo admin_url('admin.php?page=one-health-medicos'); ?>" class="button button-primary button-large">Gerenciar Médicos</a>
            <a href="<?php echo admin_url('admin.php?page=one-health-gerar-agenda'); ?>" class="button button-secondary button-large">Gerar Agenda</a>
        </div>
    </div>
</div>