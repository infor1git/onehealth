<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$medico_model = new GH_Medico();
$unidade_model = new GH_Unidade();
$esp_model = new GH_Especialidade();
// Fallback se a agenda não for carregada
$agenda_model = isset($agenda_model) ? $agenda_model : (class_exists('GH_Agenda') ? new GH_Agenda() : null);

$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
$base_url = admin_url( 'admin.php?page=one-health-medicos' );

$todas_unidades = $unidade_model->get_all();
$todas_esps = $esp_model->get_all();
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'geral';

if ( isset( $_GET['message'] ) ) {
    $m = $_GET['message'];
    if($m=='created') echo '<div class="notice notice-success is-dismissible"><p>Médico criado! Configure os horários na aba "Horários".</p></div>';
    if($m=='updated' || $m=='saved') echo '<div class="notice notice-success is-dismissible"><p>Salvo com sucesso.</p></div>';
    if($m=='schedule_created') echo '<div class="notice notice-success is-dismissible"><p>Turno de atendimento adicionado.</p></div>';
    if($m=='schedule_deleted') echo '<div class="notice notice-success is-dismissible"><p>Turno removido.</p></div>';
}
?>

<div class="wrap">
    
    <?php if ( $action == 'list' ) : ?>
        <h1 class="wp-heading-inline">Corpo Clínico</h1>
        <a href="<?php echo $base_url . '&action=new'; ?>" class="page-title-action">Adicionar Médico</a>
        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;">Foto</th>
                    <th>Nome / CRM</th>
                    <th>Espec.</th>
                    <th>Unidades</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $medicos = $medico_model->get_all();
                if ( $medicos ) :
                    foreach ( $medicos as $medico ) : 
                        $esps_ids = $medico_model->get_especialidades_ids($medico->id);
                        $unis_ids = $medico_model->get_unidades_ids($medico->id);
                        ?>
                        <tr>
                            <td>
                                <?php if($medico->foto_url): ?>
                                    <img src="<?php echo esc_url($medico->foto_url); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: #f0f0f1; display:flex; align-items:center; justify-content:center;"><span class="dashicons dashicons-businessman"></span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><a href="<?php echo $base_url . '&action=edit&id=' . $medico->id; ?>"><?php echo esc_html( $medico->nome ); ?></a></strong><br>
                                <span class="description">CRM: <?php echo esc_html( $medico->crm ); ?></span>
                            </td>
                            <td><?php echo count($esps_ids); ?></td>
                            <td><?php echo count($unis_ids); ?></td>
                            <td><?php echo $medico->is_active ? 'Ativo' : 'Inativo'; ?></td>
                            <td>
                                <a href="<?php echo $base_url . '&action=edit&id=' . $medico->id; ?>" class="button button-small">Editar</a>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=gh_delete_medico&id=' . $medico->id ), 'delete_medico_' . $medico->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('Tem certeza?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; 
                else : ?>
                    <tr><td colspan="6">Nenhum médico cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ( $action == 'new' || $action == 'edit' ) : 
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $dados = $id ? $medico_model->get( $id ) : null;
        $selected_unis = $id ? $medico_model->get_unidades_ids($id) : array();
        $selected_esps = $id ? $medico_model->get_especialidades_ids($id) : array();
    ?>
        <h1 class="wp-heading-inline"><?php echo $id ? 'Editar Médico' : 'Novo Médico'; ?></h1>
        <a href="<?php echo $base_url; ?>" class="page-title-action">Voltar</a>
        <hr class="wp-header-end">

        <?php if($id): ?>
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo $base_url . '&action=edit&id=' . $id . '&tab=geral'; ?>" class="nav-tab <?php echo $active_tab == 'geral' ? 'nav-tab-active' : ''; ?>">Dados Gerais</a>
            <a href="<?php echo $base_url . '&action=edit&id=' . $id . '&tab=turnos'; ?>" class="nav-tab <?php echo $active_tab == 'turnos' ? 'nav-tab-active' : ''; ?>">Horários de Atendimento</a>
        </h2>
        <?php endif; ?>

        <?php if( $active_tab == 'geral' ): ?>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <input type="hidden" name="action" value="gh_save_medico">
                <?php if ($id) : ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
                <?php wp_nonce_field( 'gh_save_medico_nonce', 'gh_security' ); ?>

                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="postbox-container-1" class="postbox-container">
                            <div class="postbox">
                                <h2 class="hndle"><span>Salvar</span></h2>
                                <div class="inside">
                                    <label>Status:</label>
                                    <select name="is_active">
                                        <option value="1" <?php selected( $dados ? $dados->is_active : 1, 1 ); ?>>Ativo</option>
                                        <option value="0" <?php selected( $dados ? $dados->is_active : 1, 0 ); ?>>Inativo</option>
                                    </select>
                                    <div style="margin-top:10px;"><input type="submit" class="button button-primary button-large" value="Salvar"></div>
                                </div>
                            </div>
                            <div class="postbox">
                                <h2 class="hndle"><span>Foto</span></h2>
                                <div class="inside">
                                    <div id="gh_image_preview_container" style="<?php echo ($dados && $dados->foto_url) ? '' : 'display:none;'; ?> margin-bottom: 10px; text-align: center;">
                                        <img id="gh_image_preview" src="<?php echo ($dados && $dados->foto_url) ? esc_url($dados->foto_url) : ''; ?>" style="max-width:100%;">
                                    </div>
                                    <input type="hidden" name="foto_url" id="gh_foto_url" value="<?php echo $dados ? esc_attr($dados->foto_url) : ''; ?>">
                                    <button type="button" class="button" id="gh_upload_image_button">Selecionar</button>
                                    <button type="button" class="button link-delete" id="gh_remove_image_button">X</button>
                                </div>
                            </div>
                            <div class="postbox">
                                <h2 class="hndle"><span>Especialidades</span></h2>
                                <div class="inside" style="max-height:150px;overflow:auto;">
                                    <?php if($todas_esps): foreach($todas_esps as $esp): ?>
                                        <label style="display:block;"><input type="checkbox" name="especialidades[]" value="<?php echo $esp->id; ?>" <?php echo in_array($esp->id, $selected_esps) ? 'checked' : ''; ?>> <?php echo esc_html($esp->nome); ?></label>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                            <div class="postbox">
                                <h2 class="hndle"><span>Unidades</span></h2>
                                <div class="inside" style="max-height:150px;overflow:auto;">
                                    <?php if($todas_unidades): foreach($todas_unidades as $uni): ?>
                                        <label style="display:block;"><input type="checkbox" name="unidades[]" value="<?php echo $uni->id; ?>" <?php echo in_array($uni->id, $selected_unis) ? 'checked' : ''; ?>> <?php echo esc_html($uni->nome); ?></label>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        </div>

                        <div id="post-body-content">
                            <div class="postbox">
                                <div class="inside">
                                    <table class="form-table">
                                        <tr><th>Nome</th><td><input name="nome" type="text" value="<?php echo $dados ? esc_attr($dados->nome) : ''; ?>" class="regular-text" required></td></tr>
                                        <tr><th>CRM</th><td><input name="crm" type="text" value="<?php echo $dados ? esc_attr($dados->crm) : ''; ?>" class="regular-text" required></td></tr>
                                        <tr><th>E-mail</th><td><input name="email" type="email" value="<?php echo $dados ? esc_attr($dados->email) : ''; ?>" class="regular-text"></td></tr>
                                        <tr><th>Telefone</th><td><input name="telefone" type="text" value="<?php echo $dados ? esc_attr($dados->telefone) : ''; ?>" class="regular-text"></td></tr>
                                        <tr><th>CPF</th><td><input name="cpf" type="text" value="<?php echo $dados ? esc_attr($dados->cpf) : ''; ?>" class="regular-text"></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        <?php elseif( $active_tab == 'turnos' && $id && $agenda_model ): ?>
            
            <div id="poststuff">
                <div class="postbox">
                    <h2 class="hndle"><span>Adicionar Turno de Trabalho</span></h2>
                    <div class="inside">
                        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                            <input type="hidden" name="action" value="gh_save_schedule">
                            <input type="hidden" name="medico_id" value="<?php echo $id; ?>">
                            <?php wp_nonce_field( 'gh_save_schedule_nonce', 'gh_schedule_security' ); ?>

                            <div style="display:flex; flex-wrap:wrap; gap:15px; align-items:flex-end;">
                                <div><label>Dia</label><br>
                                    <select name="dia_semana" required>
                                        <option value="1">Segunda</option><option value="2">Terça</option><option value="3">Quarta</option>
                                        <option value="4">Quinta</option><option value="5">Sexta</option><option value="6">Sábado</option><option value="0">Domingo</option>
                                    </select>
                                </div>
                                <div><label>Unidade</label><br>
                                    <select name="unidade_id" required>
                                        <?php foreach($todas_unidades as $u) { if(in_array($u->id, $selected_unis)) echo "<option value='{$u->id}'>{$u->nome}</option>"; } ?>
                                    </select>
                                </div>
                                <div><label>Especialidade</label><br>
                                    <select name="especialidade_id" required>
                                        <?php foreach($todas_esps as $e) { if(in_array($e->id, $selected_esps)) echo "<option value='{$e->id}'>{$e->nome}</option>"; } ?>
                                    </select>
                                </div>
                                <div><label>Início</label><br><input type="time" name="hora_inicio" required></div>
                                <div><label>Fim</label><br><input type="time" name="hora_fim" required></div>
                                <div><button type="submit" class="button button-primary">Adicionar</button></div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card" style="margin-top:0;padding:0;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>Dia</th><th>Unidade</th><th>Horário</th><th>Ação</th></tr></thead>
                        <tbody>
                            <?php 
                            $schedules = $agenda_model->get_schedules_by_medico($id);
                            $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                            if($schedules): foreach($schedules as $s): ?>
                                <tr>
                                    <td><strong><?php echo $dias[$s->dia_semana]; ?></strong></td>
                                    <td><?php echo esc_html($s->unidade_nome); ?></td>
                                    <td><?php echo substr($s->hora_inicio, 0, 5) . ' - ' . substr($s->hora_fim, 0, 5); ?></td>
                                    <td><a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=gh_delete_schedule&id=' . $s->id . '&medico_id=' . $id ), 'delete_schedule_' . $s->id ); ?>" style="color:red;" onclick="return confirm('Remover?');">Excluir</a></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4">Sem horários cadastrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>