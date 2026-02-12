<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
$model = new GH_Convenio();
$base_url = admin_url( 'admin.php?page=one-health-convenios' );

// Feedback
if ( isset( $_GET['message'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Operação realizada com sucesso!</p></div>';
?>

<div class="wrap">
    <?php if ( $action == 'list' ) : ?>
        <h1 class="wp-heading-inline">Convênios e Planos</h1>
        <a href="<?php echo $base_url . '&action=new'; ?>" class="page-title-action">Adicionar Novo</a>
        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;">Logo</th>
                    <th>Nome</th>
                    <th>Planos</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $items = $model->get_all();
                if ( $items ) :
                    foreach ( $items as $item ) : 
                        $planos = $model->get_planos($item->id);
                        ?>
                        <tr>
                            <td>
                                <?php if($item->logo_url): ?>
                                    <img src="<?php echo esc_url($item->logo_url); ?>" style="height: 30px; width: auto;">
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html( $item->nome ); ?></strong></td>
                            <td>
                                <?php 
                                    if($planos) {
                                        $nomes = wp_list_pluck($planos, 'nome');
                                        echo implode(', ', $nomes);
                                    } else {
                                        echo '<span class="description">Sem planos</span>';
                                    }
                                ?>
                            </td>
                            <td><?php echo $item->is_active ? 'Ativo' : 'Inativo'; ?></td>
                            <td>
                                <a href="<?php echo $base_url . '&action=edit&id=' . $item->id; ?>" class="button button-small">Editar/Planos</a>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=gh_delete_convenio&id=' . $item->id ), 'delete_convenio_' . $item->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('Tem certeza? Isso apaga todos os planos do convênio.');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; 
                else : ?>
                    <tr><td colspan="5">Nenhum convênio cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ( $action == 'new' || $action == 'edit' ) : 
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $dados = $id ? $model->get( $id ) : null;
    ?>
        <h1 class="wp-heading-inline"><?php echo $id ? 'Editar Convênio' : 'Novo Convênio'; ?></h1>
        <a href="<?php echo $base_url; ?>" class="page-title-action">Voltar</a>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <input type="hidden" name="action" value="gh_save_convenio">
            <?php if ($id) : ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
            <?php wp_nonce_field( 'gh_save_convenio_nonce', 'gh_security' ); ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="postbox">
                            <h2 class="hndle"><span>Publicar / Salvar</span></h2>
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
                                        <input type="submit" class="button button-primary button-large" value="Salvar Convênio">
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>

                        <div class="postbox">
                            <h2 class="hndle"><span>Logomarca</span></h2>
                            <div class="inside">
                                <div id="gh_logo_preview_container" style="<?php echo ($dados && $dados->logo_url) ? '' : 'display:none;'; ?> margin-bottom: 10px; text-align: center;">
                                    <img id="gh_logo_preview" src="<?php echo ($dados && $dados->logo_url) ? esc_url($dados->logo_url) : ''; ?>" style="max-width:100%; height:auto; border-radius: 4px; border: 1px solid #ddd; padding: 2px;">
                                </div>
                                
                                <input type="hidden" name="logo_url" id="gh_logo_url" value="<?php echo $dados ? esc_attr($dados->logo_url) : ''; ?>">
                                
                                <div style="display:flex; gap: 5px; justify-content: center;">
                                    <button type="button" class="button" id="gh_upload_logo_button">Selecionar Logo</button>
                                    <button type="button" class="button link-delete" id="gh_remove_logo_button">Remover</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="post-body-content">
                        
                        <div class="postbox">
                            <h2 class="hndle"><span>Dados do Convênio</span></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row"><label>Nome do Convênio</label></th>
                                            <td><input name="nome" type="text" value="<?php echo $dados ? esc_attr($dados->nome) : ''; ?>" class="regular-text" required placeholder="Ex: Unimed, Bradesco Saúde..."></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Regras</th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" name="exige_guia" value="1" <?php checked( $dados ? $dados->exige_guia : 0, 1 ); ?>>
                                                    Exige upload de guia/requisição no agendamento?
                                                </label>
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

        <?php if ( $id ) : ?>
            <div id="poststuff" style="padding-top:0;">
                <div id="post-body" class="metabox-holder columns-2">
                     <div id="postbox-container-1" class="postbox-container"></div>

                     <div id="post-body-content">
                        <div class="postbox">
                            <h2 class="hndle"><span>Planos Vinculados a este Convênio</span></h2>
                            <div class="inside">
                                <table class="wp-list-table widefat fixed striped" style="border:none; box-shadow:none;">
                                    <thead>
                                        <tr>
                                            <th>Nome do Plano</th>
                                            <th style="width: 100px;">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $planos = $model->get_planos($id);
                                        if($planos): foreach($planos as $plano): ?>
                                            <tr>
                                                <td><?php echo esc_html($plano->nome); ?></td>
                                                <td>
                                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=gh_delete_plano&id=' . $plano->id . '&convenio_id=' . $id ), 'delete_plano_' . $plano->id ); ?>" style="color:red;" onclick="return confirm('Apagar este plano?');">Excluir</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="2">Nenhum plano cadastrado ainda.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                
                                <div style="background: #f6f7f7; padding: 15px; margin-top: 15px; border: 1px solid #c3c4c7; border-radius: 4px;">
                                    <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:flex; gap:10px; align-items:center;">
                                        <input type="hidden" name="action" value="gh_save_plano">
                                        <input type="hidden" name="convenio_id" value="<?php echo $id; ?>">
                                        <?php wp_nonce_field( 'gh_save_plano_nonce', 'gh_plano_security' ); ?>
                                        
                                        <label><b>Adicionar Plano:</b></label>
                                        <input type="text" name="nome_plano" placeholder="Nome do plano (Ex: Enfermaria)" required class="regular-text">
                                        <button type="submit" class="button button-secondary">Adicionar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                     </div>
                </div>
            </div>
        <?php else: ?>
            <div class="notice notice-info inline" style="margin-top: 20px;"><p>Salve o convênio primeiro para poder adicionar planos.</p></div>
        <?php endif; ?>

    <?php endif; ?>
</div>