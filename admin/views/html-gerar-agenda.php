<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'GH_Medico' ) ) {
    echo '<div class="notice notice-error"><p>Erro: Classes não carregadas.</p></div>';
    return;
}

$medico_model = new GH_Medico();
$medicos = $medico_model->get_all();

if ( isset( $_GET['message'] ) ) {
    $c = isset($_GET['count']) ? intval($_GET['count']) : 0;
    echo '<div class="notice notice-success is-dismissible"><p>Agenda gerada! ' . $c . ' novos horários disponíveis.</p></div>';
}
?>

<div class="wrap">
    <h1>Gerar Agenda (Slots)</h1>
    <div class="card" style="max-width:600px; padding:20px;">
        <p>Gera os horários disponíveis com base nos turnos cadastrados.</p>
        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <input type="hidden" name="action" value="gh_generate_slots">
            <?php wp_nonce_field( 'gh_generate_slots_nonce', 'gh_security' ); ?>
            
            <p>
                <label><b>Data Início:</b></label><br>
                <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
            </p>
            <p>
                <label><b>Data Fim:</b></label><br>
                <input type="date" name="end_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
            </p>
            <p>
                <label><b>Médico:</b></label><br>
                <select name="medico_id">
                    <option value="">Todos</option>
                    <?php if($medicos): foreach($medicos as $m): ?>
                        <option value="<?php echo $m->id; ?>"><?php echo esc_html($m->nome); ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </p>
            <p>
                <label><input type="checkbox" name="clear_existing" value="1"> Limpar horários livres existentes neste período?</label>
            </p>
            <p><button type="submit" class="button button-primary">Gerar Horários</button></p>
        </form>
    </div>
</div>