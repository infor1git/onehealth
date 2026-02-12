<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
$model = new GH_Servico();
$esp_model = new GH_Especialidade(); 
$base_url = admin_url( 'admin.php?page=one-health-servicos' );

if ( isset( $_GET['message'] ) ) {
    if ( $_GET['message'] == 'created' ) echo '<div class="notice notice-success is-dismissible"><p>Serviço criado!</p></div>';
    if ( $_GET['message'] == 'updated' ) echo '<div class="notice notice-success is-dismissible"><p>Serviço atualizado!</p></div>';
}
?>

<div class="wrap">
    <?php if ( $action == 'list' ) : ?>
        <h1 class="wp-heading-inline">Serviços e Procedimentos</h1>
        <a href="<?php echo $base_url . '&action=new'; ?>" class="page-title-action">Adicionar Novo</a>
        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Valor Ref.</th>
                    <th>Especialidades</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $items = $model->get_all();
                if ( $items ) :
                    foreach ( $items as $item ) : 
                        $esps = $model->get_especialidades_ids( $item->id );
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $item->nome ); ?></strong></td>
                            <td>
                                <?php 
                                    $labels = ['consulta'=>'Consulta', 'exame'=>'Exame', 'procedimento'=>'Procedimento'];
                                    echo isset($labels[$item->tipo]) ? $labels[$item->tipo] : $item->tipo;
                                ?>
                            </td>
                            <td>R$ <?php echo number_format($item->valor, 2, ',', '.'); ?></td>
                            <td><?php echo count($esps); ?> vinculada(s)</td>
                            <td><?php echo $item->is_active ? 'Ativo' : 'Inativo'; ?></td>
                            <td>
                                <a href="<?php echo $base_url . '&action=edit&id=' . $item->id; ?>" class="button button-small">Editar</a>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=gh_delete_servico&id=' . $item->id ), 'delete_servico_' . $item->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('Tem certeza?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; 
                else : ?>
                    <tr><td colspan="6">Nenhum serviço cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ( $action == 'new' || $action == 'edit' ) : 
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $dados = $id ? $model->get( $id ) : null;
        $selected_esps = $id ? $model->get_especialidades_ids($id) : array();
        $todas_esps = $esp_model->get_all();
    ?>
        <h1 class="wp-heading-inline"><?php echo $id ? 'Editar Serviço' : 'Novo Serviço'; ?></h1>
        <a href="<?php echo $base_url; ?>" class="page-title-action">Voltar</a>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <input type="hidden" name="action" value="gh_save_servico">
            <?php if ($id) : ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
            <?php wp_nonce_field( 'gh_save_servico_nonce', 'gh_security' ); ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    
                    <div id="postbox-container-1" class="postbox-container">
                        
                        <div class="postbox">
                            <h2 class="hndle"><span>Publicar</span></h2>
                            <div class="inside">
                                <div class="misc-pub-section">
                                    <label>Status:</label>
                                    <select name="is_active">
                                        <option value="1" <?php selected( $dados ? $dados->is_active : 1, 1 ); ?>>Ativo</option>
                                        <option value="0" <?php selected( $dados ? $dados->is_active : 1, 0 ); ?>>Inativo</option>
                                    </select>
                                </div>
                                <div id="major-publishing-actions">
                                    <div id="publishing-action">
                                        <input type="submit" class="button button-primary button-large" value="Salvar">
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>

                        <div class="postbox">
                            <h2 class="hndle"><span>Vincular a Especialidades</span></h2>
                            <div class="inside">
                                <p class="description">Quais especialidades realizam este serviço?</p>
                                
                                <div style="margin-bottom: 5px;">
                                    <a href="#" id="gh_select_all_esp" style="font-size: 12px;">Marcar todos</a> | 
                                    <a href="#" id="gh_deselect_all_esp" style="font-size: 12px; color: #a00;">Desmarcar todos</a>
                                </div>

                                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                                    <?php if($todas_esps): foreach($todas_esps as $esp): ?>
                                        <label style="display:block; margin-bottom: 5px;">
                                            <input type="checkbox" name="especialidades[]" class="gh-esp-checkbox" value="<?php echo $esp->id; ?>" <?php echo in_array($esp->id, $selected_esps) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($esp->nome); ?>
                                        </label>
                                    <?php endforeach; else: echo "Cadastre especialidades primeiro."; endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="post-body-content">
                        <div class="postbox">
                            <div class="inside">
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><label>Nome do Serviço <span class="required">*</span></label></th>
                                            <td><input name="nome" type="text" value="<?php echo $dados ? esc_attr($dados->nome) : ''; ?>" class="large-text" required></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label>Tipo</label></th>
                                            <td>
                                                <select name="tipo" class="regular-text">
                                                    <option value="consulta" <?php selected( $dados ? $dados->tipo : '', 'consulta' ); ?>>Consulta</option>
                                                    <option value="exame" <?php selected( $dados ? $dados->tipo : '', 'exame' ); ?>>Exame</option>
                                                    <option value="procedimento" <?php selected( $dados ? $dados->tipo : '', 'procedimento' ); ?>>Procedimento</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label>Valor de Referência (R$)</label></th>
                                            <td><input name="valor" type="text" value="<?php echo $dados ? esc_attr($dados->valor) : '0.00'; ?>" class="regular-text" style="width: 150px;"></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label>Instruções de Preparo</label></th>
                                            <td>
                                                <?php 
                                                $content = $dados ? $dados->preparo_html : '';
                                                wp_editor( $content, 'preparo_html', array( 'textarea_name' => 'preparo_html', 'media_buttons' => false, 'textarea_rows' => 8, 'teeny' => true ) ); 
                                                ?>
                                                <p class="description">Texto exibido ao paciente ao agendar exames.</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    <?php endif; ?>
</div>