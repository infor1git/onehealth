<?php
// Evita acesso direto
if ( ! defined( 'ABSPATH' ) ) exit;

$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
$unidade_model = new GH_Unidade();
$base_url = admin_url( 'admin.php?page=one-health-unidades' );

// Lógica de Feedback (Sucesso/Erro)
if ( isset( $_GET['message'] ) ) {
    if ( $_GET['message'] == 'created' ) echo '<div class="notice notice-success is-dismissible"><p>Unidade cadastrada com sucesso!</p></div>';
    if ( $_GET['message'] == 'updated' ) echo '<div class="notice notice-success is-dismissible"><p>Unidade atualizada com sucesso!</p></div>';
    if ( $_GET['message'] == 'deleted' ) echo '<div class="notice notice-success is-dismissible"><p>Unidade excluída.</p></div>';
    if ( $_GET['error'] ) echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $_GET['error'] ) . '</p></div>';
}
?>

<div class="wrap">
    
    <?php if ( $action == 'list' ) : ?>
        <h1 class="wp-heading-inline">Unidades de Atendimento</h1>
        <a href="<?php echo $base_url . '&action=new'; ?>" class="page-title-action">Adicionar Nova</a>
        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Endereço</th>
                    <th>Cidade/UF</th>
                    <th>Status</th>
                    <th style="width: 150px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $unidades = $unidade_model->get_all();
                if ( $unidades ) :
                    foreach ( $unidades as $unidade ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $unidade->nome ); ?></strong></td>
                            <td><?php echo esc_html( $unidade->logradouro . ', ' . $unidade->numero ); ?></td>
                            <td><?php echo esc_html( $unidade->cidade . '/' . $unidade->estado ); ?></td>
                            <td>
                                <?php echo $unidade->is_active ? '<span class="badge-active" style="color:green;">Ativo</span>' : '<span style="color:red;">Inativo</span>'; ?>
                            </td>
                            <td>
                                <a href="<?php echo $base_url . '&action=edit&id=' . $unidade->id; ?>" class="button button-small">Editar</a>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=gh_delete_unidade&id=' . $unidade->id ), 'delete_unidade_' . $unidade->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('Tem certeza?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; 
                else : ?>
                    <tr><td colspan="5">Nenhuma unidade cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ( $action == 'new' || $action == 'edit' ) : 
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $dados = $id ? $unidade_model->get( $id ) : null;
    ?>
        <h1 class="wp-heading-inline"><?php echo $id ? 'Editar Unidade' : 'Nova Unidade'; ?></h1>
        <a href="<?php echo $base_url; ?>" class="page-title-action">Voltar</a>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <input type="hidden" name="action" value="gh_save_unidade">
            <?php if ($id) : ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
            <?php wp_nonce_field( 'gh_save_unidade_nonce', 'gh_security' ); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="gh_nome">Nome da Unidade <span class="required">*</span></label></th>
                        <td><input name="nome" type="text" id="gh_nome" value="<?php echo $dados ? esc_attr($dados->nome) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gh_cep">CEP</label></th>
                        <td>
                            <input name="cep" type="text" id="gh_cep" value="<?php echo $dados ? esc_attr($dados->cep) : ''; ?>" class="regular-text" style="max-width: 150px;" maxlength="9">
                            <p class="description">Digite o CEP para buscar o endereço automaticamente.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gh_logradouro">Logradouro</label></th>
                        <td><input name="logradouro" type="text" id="gh_logradouro" value="<?php echo $dados ? esc_attr($dados->logradouro) : ''; ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gh_numero">Número</label></th>
                        <td><input name="numero" type="text" id="gh_numero" value="<?php echo $dados ? esc_attr($dados->numero) : ''; ?>" class="small-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gh_complemento">Complemento</label></th>
                        <td><input name="complemento" type="text" id="gh_complemento" value="<?php echo $dados ? esc_attr($dados->complemento) : ''; ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gh_bairro">Bairro</label></th>
                        <td><input name="bairro" type="text" id="gh_bairro" value="<?php echo $dados ? esc_attr($dados->bairro) : ''; ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gh_cidade">Cidade / UF</label></th>
                        <td>
                            <input name="cidade" type="text" id="gh_cidade" value="<?php echo $dados ? esc_attr($dados->cidade) : ''; ?>" class="regular-text">
                            <input name="estado" type="text" id="gh_estado" value="<?php echo $dados ? esc_attr($dados->estado) : ''; ?>" class="small-text" maxlength="2">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gh_mapa_url">Link do Google Maps</label></th>
                        <td><input name="mapa_url" type="url" id="gh_mapa_url" value="<?php echo $dados ? esc_attr($dados->mapa_url) : ''; ?>" class="large-text code"></td>
                    </tr>
                    <tr>
                        <th scope="row">Status</th>
                        <td>
                            <label for="gh_ativo">
                                <input name="is_active" type="checkbox" id="gh_ativo" value="1" <?php checked( $dados ? $dados->is_active : 1, 1 ); ?>>
                                Ativo para agendamentos
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $id ? 'Salvar Alterações' : 'Cadastrar Unidade'; ?>">
            </p>
        </form>
    <?php endif; ?>
</div>