<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Documento extends CI_Controller {
	/*
	 * Atributos opcionais para as views
	* public $layout;  define o layout default
	* public $title; define o titulo default da view
	* public $css = array('css1','css2'); define os arquivos css default
	* public $js = array('js1','js2'); define os arquivos javascript default
	* public $images = 'dir_images'; define a diretório default das imagens
	*
	*/
	
	public $layout = 'default';
	public $css = array('style','jquery-ui-1.8.11.custom');
	public $js = array('jquery-1.11.1.min','jquery.dataTables.min','jquery.blockUI','about');
	public $js_custom;
	
	private $area            = "documento";
	private $tituloIndex     = "s";
	private $tituloAdd       = "Novo ";
	private $tituloView      = "Detalhes do ";
	private $tituloUpdate    = "Edita ";

	public function __construct (){
		parent::__construct();
		$this->load->library(array('restrict_page','table','form_validation','session', 'datas'));
		$this->load->helper('url');
		$this->load->model('Documento_model','',TRUE);
		$this->load->model('Campo_model','',TRUE);
		$this->load->model('Grid_model','',TRUE);
		
		$this->modal = $this->load->view('about_modal', '', TRUE);
		session_start();

		if(isset($_SESSION['homepage']) == true and $_SESSION['homepage'] == "index/0"){
			$_SESSION['homepage'] = $this->area."/index";
		}

	}

	public function index($setor_escolhido=NULL, $inicio = 0){
		
		$this->_checa_tabelas();
	
		$_SESSION['homepage'] = current_url();
		
		$this->js[] = 'documento';

		$session_id = $this->session->userdata('session_id');
		$session_cpf = $this->session->userdata('cpf');
		$_SESSION['cpf'] = $session_cpf;
		$session_nome = $this->session->userdata('nome');
		$session_sobrenome = $this->session->userdata('sobrenome');
		$session_nivel = $this->session->userdata('nivel');
		
		$data['titulo']     = mb_convert_case($this->area, MB_CASE_TITLE, "ISO-8859-1").$this->tituloIndex;
		$data['link_add']   = anchor($this->area.'/add/','<span class="glyphicon glyphicon-plus"></span> Novo',array('class'=>'btn btn-primary'));
		$data['form_action'] = site_url($this->area.'/search');
		
		
		$data['campoSearchText'] = '<input type="text" class="form-control" id="search" name="searchText" placeholder="Ex.: teste">';
		
		$data['campoSearchNumber'] = '<input type="text" class="form-control" id="searchNumero" name="searchNumber" placeholder="Ex.: 1">';


		//--- BUSCA ---//	
		$data['keyword_'.$this->area] = '';

		if(isset($_SESSION['keyword_'.$this->area]) == true and $_SESSION['keyword_'.$this->area] != null){
			$data['keyword_'.$this->area] = $_SESSION['keyword_'.$this->area];
			redirect($this->area.'/search/');
		}else{
			$data['keyword_'.$this->area] = 'pesquisa textual';
			$data['link_search_cancel'] = '';
			$data['btn_search_cancel'] = '';
			
// 			$_SESSION['keyword'.$this->area] = null;
// 			unset($_SESSION['keyword'.$this->area]);
		}
		//--- FIM ---//	
			
		
		//--- SETORES ---//		
		$this->load->model('Setor_model','',TRUE);
		
		$session_setor = $this->session->userdata('setor');

		$restricao = $this->Setor_model->get_by_id($session_setor)->row();
		
		if(isset($restricao->restricao) and $restricao->restricao == 'S'){
			
			$data['setores'] = $this->Setor_model->get_by_id($session_setor)->result();
			
		}else{
			
			$data['setores'] = $this->Setor_model->list_all()->result();
			
			$arraySetores['all'] = "TODOS";

		}
		
		foreach ($data['setores'] as $setor){
			$arraySetores[$setor->id] = "$setor->sigla" . " - " . $setor->nome;
		}
		
		$data['setoresDisponiveis']  =  $arraySetores;
			
		$uri_setor = substr($this->uri->segment(3), 1);
			
		$_SESSION['setorSelecionado'] = ($uri_setor) ? $uri_setor : $session_setor;
			
		$data['setorSelecionado'] = $_SESSION['setorSelecionado'];
		
		
		if($data['setorSelecionado'] == 'all'){
			$data['setorCaminho'] = "TODOS";
		}else{
			$data['setorCaminho'] = $this->getCaminho($data['setorSelecionado']);
		}
		//--- FIM ---//
		
		
		$maximo = 10;

		$uri_segment = 4;
		
		$inicio = (!$this->uri->segment($uri_segment, 0)) ? 0 : ($this->uri->segment($uri_segment, 0) - 1) * $maximo;

		if($this->input->post('txt_busca')){
			$documentos = $this->Documento_model->lista_busca($this->session->userdata('cpf'), $this->input->post('txt_busca'))->result();
			$data['link_add'] .= '<div class="alerta1" style="width:80%;"><b> Texto da busca: </b>'.$this->input->post('txt_busca'). ' &nbsp; ' .anchor('documento/index/','cancelar',array('class'=>'delete')) . '</div>';
			
			$contagem = $this->Documento_model->lista_busca($this->session->userdata('cpf'), $this->input->post('txt_busca'))->num_rows();
			
		}else{
			
			$config_listagem = $this->get_config();
			
			$data_inicial = $config_listagem->data_inicial;
			
			$data_final = $config_listagem->data_final;

			if($data['setorSelecionado'] == 0){ // zero significa todos os documentos

				$documentos = $this->Documento_model->lista_todos_documentos($inicio, $maximo, $this->session->userdata('cpf'), $data_inicial, $data_final);
				
				$contagem = $this->Documento_model->conta_todos_documentos($this->session->userdata('cpf'), $data_inicial, $data_final);

			}else{

				$documentos = $this->Documento_model->lista_documentos_por_setor($inicio, $maximo, $data['setorSelecionado'], $this->session->userdata('cpf'), $data_inicial, $data_final);

				$contagem = $this->Documento_model->conta_documentos_por_setor($data['setorSelecionado'], $this->session->userdata('cpf'), $data_inicial, $data_final);
			
			}
		}
		
		//Inicio da Paginacao
		$this->load->library('pagination');
		$config['base_url'] = site_url($this->area.'/index/s'.$data['setorSelecionado'].'/');
		$config['uri_segment']= 4;
		$config['total_rows'] = $contagem;
		$config['per_page'] = $maximo;
		
        $this->pagination->initialize($config);
        $data['pagination'] = $this->pagination->create_links();

		// carregando os dados na tabela
		$this->table->set_empty("&nbsp;");
		$this->table->set_heading('Identificação', 'Assunto', 'Autor', 'Criado em','Ação');

		//Monta a DataTable
        $tmpl = $this->Grid_model->monta_tabela_list();
        $this->_monta_linha($documentos);
        $this->table->set_template($tmpl);
        // Fim da DataTable

        // variaveis para a view
       	$data['table'] = $this->table->generate();
        $data["total_rows"] = $config['total_rows'];
        
        //checa documentos pendentes de recebimento
        $data['workflow'] = $this->Documento_model->check_workflow($this->session->userdata('setor'))->num_rows();
        //fim
        
        
/*
|--------------------------------------------------------------------------
| Alertas
|--------------------------------------------------------------------------
*/
       
        $data['alerta_documento_conteudo'] = '';
        $data['form_action_alerta_update'] = '';
        
        $alerta = $this->Documento_model->check_alerta_by_date($this->session->userdata('id_usuario'),  date("Y-m-d"));
        $data['alerta'] = $alerta;
        if(count($alerta) > 0){

        	$id_documento = $alerta[0]['id_documento'];
        	$id_alerta = $alerta[0]['id_alerta'];
        	
        	$alerta_documento_conteudo = $this->Documento_model->get_historico($id_documento)->result();

        	$data['data_alerta_banco'] = $this->_trata_dataDoBancoParaForm($alerta[0]['data_alerta']);
        	
        	$data['alerta_documento_conteudo'] = $alerta_documento_conteudo[0]->texto;
        	
        	$data['data_alerta'] = $this->_trata_dataDoBancoParaForm($alerta[0]['data_alerta']) . " às " . $this->datas->trataHoraBancoForm($alerta[0]['hora_alerta']);
        	
        	$data['id_documento_alerta'] = $id_documento;
        	
        	$data['motivo_alerta'] = $alerta[0]['motivo_alerta'];
        	
        	$data['form_action_alerta_update']	= site_url($this->area.'/alerta_update/'.$id_alerta);
        	
        }else{
        	$_SESSION['tem_alerta'] = 0;
        }
        
        
        
/*
|--------------------------------------------------------------------------
| Dropdown tipos de documentos
|--------------------------------------------------------------------------
*/        
        
        //--- POPULA O DROPDOWN DE TIPOS ---//
        $this->load->model('Tipo_model','',TRUE);
        $tipos = $this->Tipo_model->list_all_actives()->result();
        $arrayTipos[0] = "TODOS OS DOCUMENTOS";
        if($tipos){
        	foreach ($tipos as $tipo){
        		$arrayTipos[$tipo->id] = $tipo->nome;
        	}
        }else{
        	$arrayTipos[1] = "";
        }
        
        $data['tiposDisponiveis']  =  $arrayTipos;
        
//         $_SESSION['tipoSelecionado'] = $this->uri->segment(6) ? $this->uri->segment(6) : 0;
        	
//    $data['tipoSelecionado'] = $this->input->post('campoTipo') ? $this->input->post('campoTipo') : $_SESSION['tipoSelecionado'];

        $data['tipoSelecionado'] = $this->input->post('campoTipo') ? $this->input->post('campoTipo') : 0;
        
        //--- FIM ---//
        
        
/*
|--------------------------------------------------------------------------
| Configuracao de listagem de documentos
|--------------------------------------------------------------------------
*/

        $config_listagem = self::_config_listagem();
        
        $data['link_periodo_cancel'] = $config_listagem->link_periodo_cancel;
        $data['form_action_config_update'] = $config_listagem->form_action_config_update;
        $data['dataInicial'] = $config_listagem->dataInicial;
        $data['dataFinal'] = $config_listagem->dataFinal;
       	 
/*
|--------------------------------------------------------------------------
| Fim
|--------------------------------------------------------------------------
*/
        
        $data['searchText'] = '';
        $data['searchNumber'] = '';
        
		$this->load->view($this->area.'/'.$this->area.'_list', $data);
		
		$this->audita();
		
	}
	
	
	public function _config_listagem(){
		
		$obj = new stdClass();
		
		$obj->form_action_config_update	= site_url($this->area.'/config_update/');
		
		$config_listagem = $this->get_config();
		
		$obj->link_periodo_cancel = '';
		
		if(isset($config_listagem->data_inicial)){
			$obj->dataInicial = $this->datas->datetimeToBR($config_listagem->data_inicial);
		}else{
			$obj->dataInicial = date('d/m/Y', mktime (0, 0, 0, date("m")  , date("d")-365, date("Y")));
		}
		
		if(isset($config_listagem->data_final)){
			$obj->dataFinal = $this->datas->datetimeToBR($config_listagem->data_final);
		}else{
			$obj->dataFinal = date('d/m/Y');
		}
		
		$obj->link_periodo_cancel = $config_listagem->link_periodo_cancel;

		return $obj;
	}
	
	public function periodo_reset(){
		
		$id_usuario = $this->session->userdata('id_usuario');
		
		$this->Documento_model->periodo_reset($id_usuario);
		
		self::search_cancel();
		
		redirect($this->area . "/index/s" . $_SESSION['setorSelecionado']);
	}
	
	
	public function get_config(){
	
		$id_usuario = $this->session->userdata('id_usuario');
		
		$config = $this->Documento_model->get_config($id_usuario);
		
		if(count($config) == 0){
			$config = new stdClass();
			$config->data_inicial = date('Y-m-d', mktime (0, 0, 0, date("m")  , date("d")-365, date("Y")));
			$config->data_final = date('Y-m-d');
			$config->link_periodo_cancel = '';
		}else{
			
			$config->link_periodo_cancel = '<a class="btn btn-warning" href="'.base_url().'index.php/documento/periodo_reset" role="button"><i class="fa fa-refresh"></i> <strong>Valores padrões</strong></a>';
			
		}
		
		return $config;
	
	}
	
	public function config_update(){
		
		$data_inicial = date('d/m/Y', mktime (0, 0, 0, date("m")  , date("d")-365, date("Y"))) ;
		
		$data_final = date('d/m/Y');
		
		if($this->input->post('campoDataInicial')){
			$data_inicial = $this->input->post('campoDataInicial');
		}
		
		if($this->input->post('campoDataFinal')){
			$data_final = $this->input->post('campoDataFinal');
		}

		$obj['data_inicial'] = $this->datas->dateToUS($data_inicial);
		$obj['data_final'] = $this->datas->dateToUS($data_final);
		$obj['id_usuario'] = $this->session->userdata('id_usuario');

		$this->Documento_model->config_update($obj);
		
		self::search_cancel();
		
		redirect($this->area . "/index/s" . $_SESSION['setorSelecionado']);

// 		print_r($obj);
		
// 		exit();

	}

	//--- CAMPOS PADROES ---//
	public function set_validacao(){
	
		$config =  array(
					
				array(
						'field' => 'campoData',
						'label' => '<strong>data</strong>',
						'rules' => 'trim|callback_valid_date'
				),
					
				array(
						'field' => 'campoRemetente',
						'label' => '<strong>Remetente</strong>',
						'rules' => 'required|greater_than[0]|trim'
				),
					
				array(
						'field' => 'campoTipo',
						'label' => '<strong>Tipo</strong>',
						'rules' => 'required|greater_than[0]|trim'
				),
					
				array(
						'field' => 'campoAssunto',
						'label' => '<strong>Assunto</strong>',
						'rules' => 'required|trim'
				),
					/*
				array(
						'field' => 'campoPara',
						'label' => '<strong>Para</strong>',
						'rules' => 'required|trim'
				),
				*/
	
		);
	
		return $config;
	}
	//--- FIM ---//

	function add(){
		
		$this->_checa_tabelas();

		$this->form_validation->set_error_delimiters('<div class="error_field"> <img class="img_align" src="{TPL_images}/error.png" alt="!" /> ', '</div>');
		
		//--- VARIAVEIS COMUNS ---//
		$data['titulo']         = 'Novo';
		$data['message']        = '';
		$data['form_action']	= site_url($this->area.'/add/');
		$data['acao']          	= "add";
		$data['disabled'] = '';
		
		if(isset($_SESSION['homepage'])){
			$data['link_back'] = $this->Campo_model->make_link($_SESSION['homepage'], 'voltar_doc');
			$data['link_cancelar'] = $this->Campo_model->make_link($_SESSION['homepage'], 'cancelar_doc');
		}else{
			$data['link_back'] = '';
			$data['link_cancelar'] = '';
		}
		
		$data['link_salvar'] = $this->Campo_model->make_link($this->area, 'salvar');
		
		//$data['link_back'] = anchor($_SESSION['homepage'],'<span class="glyphicon glyphicon-arrow-left"></span> Voltar',array('class'=>'btn btn-warning btn-sm'));
		$data['id'] = '';
		$data['sess_expiration'] = $this->get_SessTimeLeft();
		//--- FIM ---//
		
		
		//--- CONSTRUCAO DOS CAMPOS ---//
		$this->load->model('Campo_model','',TRUE);
		$data['campoData']            	= $this->Campo_model->documento('campoData');
		$data['campoData']['value']   	= date("d/m/Y");
		$data['campoSetor']           	= $this->Campo_model->documento('campoSetor');
		$data['campoPara']           	= $this->Campo_model->documento('campoPara');
		$data['campoAssunto']         	= $this->Campo_model->documento('campoAssunto');
		$data['campoReferencia']      	= $this->Campo_model->documento('campoReferencia');
		$data['campoRedacao']         	= $this->Campo_model->documento('campoRedacao');

		$data['campoCarimbo']           = $this->Campo_model->documento('campoCarimbo');
		$data['carimbosDisponiveis'] 	= $this->Campo_model->documento('arrayCarimbos');
		$data['carimboSelecionado']  	= $this->uri->segment(8) ? $this->uri->segment(8) : 'N';
		
		$data['desp_num_processo']      = $this->Campo_model->documento('desp_num_processo');
		$data['desp_interessado']      	= $this->Campo_model->documento('desp_interessado');
		//--- FIM ---//

		
		//--- POPULA O DROPDOWN DE REMENTETES ---//
		$this->load->model('Setor_model','',TRUE);
		
		$session_setor = $this->session->userdata('setor');
		
		$restricao = $this->Setor_model->get_by_id($session_setor)->row();
		
		if(isset($restricao->restricao) and $restricao->restricao == 'S'){
			$remetentes = $this->Contato_model->list_all_actives($session_setor)->result();		
		}else{
			$remetentes = $this->Contato_model->list_all_actives()->result();
		}
		
		$this->load->model('Contato_model','',TRUE);
		//$remetentes = $this->Contato_model->list_all_actives()->result();
		$arrayRemetentes[0] = "SELECIONE UM REMETENTE";
		if($remetentes){
			foreach ($remetentes as $remetente){
				$arrayRemetentes[$remetente->id] = $remetente->nome;
			}
		}else{
			$arrayRemetentes[1] = "";
		}
		$data['remetentesDisponiveis']  =  $arrayRemetentes;

		$_SESSION['remetenteSelecionado'] = $this->uri->segment(4) ? $this->uri->segment(4) : 0;
			
		$data['remetenteSelecionado'] = $this->input->post('campoRemetente') ? $this->input->post('campoRemetente') : $_SESSION['remetenteSelecionado'];
		//--- FIM ---//
		
		
		//--- MOSTRA A SIGLA DO SETOR E COLOCA O SETORID EM UM CAMPO HIDDEN ---//
		if($data['remetenteSelecionado'])
			$setor = $this->Documento_model->get_setor($data['remetenteSelecionado'])->row();
			
		else $setor = null;
		if(isset($setor)){
			if($setor->setorPaiSigla == "NENHUM" or $setor->setorPaiSigla == $setor->sigla){
				$data['campoSetor']['value'] = "$setor->sigla";
			}else{
				$data['campoSetor']['value'] = "$setor->sigla/$setor->setorPaiSigla";
			}
			
			$data['setorId'] = $setor->setorId;
			
			$remetente = $this->Contato_model->get_by_id($data['remetenteSelecionado'])->row();
			$data['campoAssinatura']['value'] = $remetente->assinatura;
			
		}
		else{
			$data['campoSetor']['value'] = ' ';
			$data['setorId'] = ' ';
			$data['campoAssinatura']['value'] = ' ';
		}
		//--- FIM ---//
		
		
		//--- POPULA O DROPDOWN DE TIPOS ---//
		$this->load->model('Tipo_model','',TRUE);
		$tipos = $this->Tipo_model->list_all_actives()->result();
		$arrayTipos[0] = "SELECIONE UM TIPO";
		if($tipos){
			foreach ($tipos as $tipo){
				$arrayTipos[$tipo->id] = $tipo->nome;	
			}
		}else{
			$arrayTipos[1] = "";
		}

		$data['tiposDisponiveis']  =  $arrayTipos;
		
		$_SESSION['tipoSelecionado'] = $this->uri->segment(6) ? $this->uri->segment(6) : 0;
			
		$data['tipoSelecionado'] = $this->input->post('campoTipo') ? $this->input->post('campoTipo') : $_SESSION['tipoSelecionado'];

		//--- FIM ---//
		
		
		
		//--- POPULA O DROPDOWN DE ANEXOS ---//
		$this->load->model('Repositorio_model','',TRUE);
		$anexos = $this->Repositorio_model->list_by_setor($session_setor)->result();
		$arrayAnexos = array();
		if($anexos){
			foreach ($anexos as $anexo_item){

				$array_arquivo = explode('/', $anexo_item->arquivo);
				
				$arquivo = end($array_arquivo);
				
				$array_map_item = explode('.', $arquivo);
				
				$extensao = strtolower(end($array_map_item));
				
				if($extensao != strtolower($arquivo)){
					
					$arrayAnexos[$anexo_item->id] = $anexo_item->nome;
					
				}

			}
		}else{
			$arrayAnexos[1] = "";
		}
		
		$data['anexosDisponiveis']  =  $arrayAnexos;
		
		$data['anexoSelecionado'] = $this->input->post('campoTipo') ? $this->input->post('campoTipo') : '';
		
		//--- FIM ---//
		
		
		//--- Cria a validacao dos campos dinamicos ---//
		$_SESSION['data_original'] = '';
		$validacao = $this->set_validacao();
		
		$campos_dinamicos = '';
		
		if($data['tipoSelecionado'] != null){
			
			$obj_tipo = $this->Tipo_model->get_by_id($data['tipoSelecionado'])->row();
			
			$this->load->model('Coluna_model','',TRUE);
			$campos_especiais = $this->Coluna_model->list_all();

			foreach ($campos_especiais as $key => $nome_campo){

				if(strpos($obj_tipo->$nome_campo, ';') != FALSE){
					$campo = explode(';' , $obj_tipo->$nome_campo);
					
					if(count($campo) == 2){ // se campo tiver apenas 2 partes...
						$campo[2] = ''; // rotulo = ''
					}
					
				}else{
					$campo[0] = $obj_tipo->$nome_campo;
					$campo[1] = '';
					$campo[2] = $nome_campo;
				}
				
				$coluna = $this->Coluna_model->get_by_nome($nome_campo);

				if($campo[0] == 'S'){//caso o campo esteja disponivel para o usuario

					
					if($campo[1] == 'S'){
						$requerido = '|required';
					}else{
						$requerido = '';
					}
						
					$valor = $this->input->post('campo_'.$nome_campo) ? $this->input->post('campo_'.$nome_campo) : '';
					
					if($nome_campo != 'para'){ //pq tem validacao propria via javascript, tem um autocomplete e tals...
						array_push($validacao, array(
							'field' => 'campo_'.$nome_campo,
							'label' => '<strong>'.$campo[2].'</strong>',
							'rules' => 'trim'.$requerido
						));
					}
					
					
					if($coluna['tipo'] == 'blob'){
						$data['input_campo'][$nome_campo] = form_textarea(array(
								'name' 	=> 'campo_'.$nome_campo,
								'id'	=> 'campo_'.$nome_campo,
								'value'	=> $valor,
								'rows'  => '15',
						));
							
					}else{
						
						$data['input_campo'][$nome_campo] = form_input(array(
								'name' 	=> 'campo_'.$nome_campo,
								'id'	=> 'campo_'.$nome_campo,
								'value'	=> $valor,
								'maxlength' => '90',
				                'size' => '71',
								'class' => 'form-control',
						));
						
					}

				}	
	
			}	

		}

		$this->form_validation->set_rules($validacao);
		//--- FIM ---//

		if ($this->form_validation->run() == FALSE) {

				$this->load->view($this->area . "/" . $this->area.'_edit', $data);

		}else{
			
			$obj_do_form = array(
					'dono' =>  $this->session->userdata('nome')." ".$this->session->userdata('sobrenome'),
					'dono_cpf' =>  $this->session->userdata('cpf'),
					'dono' =>  $this->session->userdata('nome')." ".$this->session->userdata('sobrenome'),
					'data_criacao' =>  date("Y-m-d"),
					'data' => $this->datas->dateToUS($this->input->post('campoData')),
					'remetente' => $this->input->post('campoRemetente'),
					'setor' => $this->input->post('setorId'),
					'tipo' => $this->input->post('campoTipo'),
					'assunto' => $this->input->post('campoAssunto'),
					//'referencia' => $this->input->post('campoReferencia'),
					'para' => $this->input->post('campoPara'),
					//'redacao' => $this->input->post('campoRedacao'),
					'carimbo' => $this->input->post('campoCarimbo'),
	
			);

			if($this->input->post('campoAnexo')){
				$anexo = ',';
				foreach ($this->input->post('campoAnexo') as $key => $value){
					$anexo .= $value . ',';
				}
				$obj_do_form['anexos'] = $anexo;
			}

			
			foreach ($campos_especiais as $key => $nome_campo){
				
				if($this->input->post('campo_'.$nome_campo)){
					$obj_do_form[$nome_campo] = $this->input->post('campo_'.$nome_campo);
				}
			}
			
			
			

			//--- ATENCAO! --//
			//--- MAGICA DA CONTAGEM! Esse eh o miolo do sistema! Se quiser que tudo continue funcionando NAO BULA AQUI! VC FOI AVISADO!!! ---//
			$inicio_contagem = $this->Documento_model->get_inicio_contagem($obj_do_form['tipo'], $this->datas->get_year_US($obj_do_form['data']));
			
 			$assinatura = $this->Contato_model->get_by_id($obj_do_form['remetente'])->row();

			$obj_do_form["assinatura"] = $assinatura->assinatura;
			
			$obj_do_form["numero"] =  $this->_set_number($obj_do_form['setor'], $obj_do_form['tipo'], $inicio_contagem, $this->datas->get_year_US($obj_do_form['data']));

			$checa_existencia = $this->Documento_model->get_by_numero($obj_do_form['numero'], $obj_do_form['tipo'], $obj_do_form['setor'], $this->datas->get_year_US($obj_do_form['data']))->row();
			//--- FIM  DA MAGICA---//
			
			// se a checagem retornar um valor diferente de nulo
			if ($checa_existencia != null){
				
				echo  '<br> Já existe um documento com essa numeração. <br>';

			}else{
				
				/*
				echo "<pre>";
				print_r($obj_do_form);
				echo "</pre>";
				exit;
				*/
				
				$id = $this->Documento_model->save($obj_do_form);
				
				if ($id < 1 or $id == null){
				
					echo  '<br> Erro na conexão com o banco. <br>';
				
				}else{
					
					//--- Salva o historico ---//	
					$obj = $this->Documento_model->get_by_id($id)->row();	
					$texto = $this->get_layout($obj);	
					$this->Documento_model->history_save($id, $texto->layout);
					//--- Fim ---//
				
					$this->js_custom = 'var sSecs = 3;
                                function getSecs(){
                                    sSecs--;
                                    if(sSecs<0){ sSecs=59; sMins--; }
                                    $("#clock1").html(sSecs);
                                    setTimeout("getSecs()",1000);
                                    var s =  $("#clock1").html();
                                    if (s == "1"){
                                        window.location.href = "' . site_url('/'.$this->area."/view/".$id) . '";
                                    }
                                }
                                ';
				
					$data['mensagem'] = "<br /> Redirecionando em... ";
					$data['mensagem'] .= '<span id="clock1"> ' . "<script>setTimeout('getSecs()',1000);</script> </span>";
					$data['link1'] = '';
					$data['link2'] = '';
				
					$this->audita();
					$this->load->view('success', $data);
				
				}
				//fim
				
			}
			
		}

	}

	function checa_tramitacao($id){
		
		$this->load->model('Workflow_model','',TRUE);
		$obj = $this->Workflow_model->list_workflow($id)->row();
		
		if($obj){
			redirect($this->area . '/update_negado/'.$id);
		}else{
			return FALSE;
		}	
		
	}
	
	
	function update($id, $disabled = null){		
		//--- VARIAVEIS COMUNS ---//

		$data['titulo']         = "Alteração";
		if($disabled != null){
			$data['titulo']         = "Detalhes do documento";
		}
		$data['disabled'] = ($disabled != null) ? 'disabled' : '';
		$data['message']        = '';
		$data['form_action']	= site_url($this->area.'/update/'.$id);
		$data['acao']          	= "update";

		$data['link_back'] = $this->Campo_model->make_link($_SESSION['homepage'].'#d'.$id, 'voltar_doc');
		$data['link_cancelar'] = $this->Campo_model->make_link($_SESSION['homepage'], 'cancelar_doc');
		$data['link_update'] = $this->Campo_model->make_link($this->area, 'alterar', $id);
		$data['link_update_sm'] = $this->Campo_model->make_link($this->area, 'alterar_doc', $id);
		$data['link_export'] = $this->Campo_model->make_link($this->area, 'exportar_doc', $id);
		$data['link_export_sm'] = $this->Campo_model->make_link($this->area, 'exportar', $id);
		$data['link_salvar'] = $this->Campo_model->make_link($this->area, 'salvar');

		$data['id'] = '';
		$data['sess_expiration'] = $this->get_SessTimeLeft();
		//--- FIM ---//
		
		
		//--- PERMISSAO DE ACESSO AO REGISTRO ---//
		$obj = $this->Documento_model->get_by_id($id)->row();
		
		$permissao = $this->get_permissao($obj->setor, $this->session->userdata('id_usuario'));

		if($obj->dono_cpf != $this->session->userdata('cpf') and $permissao < 2){

			if($this->uri->segment(2) == 'update'){

				redirect($this->area . '/negado/'.$id);
					
			}
			
			$data['link_update'] = '';
			$data['link_update_sm'] = '';
				
		}

		if($obj->cancelado == 'S'){
			redirect($this->area . '/cancelado/'.$id);
		}
		//--- FIM DA PERMISSAO DE ACESSO AO REGISTRO ---//
		
		
		//--- CHECAGEM DE TRAMITACAO PARA EVITAR A ALTERACAO DO DOCUMENTO ---//
		
		$this->checa_tramitacao($id);
			
		//--- FIM ---//
	
		$this->form_validation->set_error_delimiters('<div class="error_field"> <img class="img_align" src="{TPL_images}/error.png" alt="! " /> ', '</div>');
	
		
		//--- CONSTRUCAO DOS CAMPOS ---//
		$this->load->model('Campo_model','',TRUE);
		$data['campoData']            	= $this->Campo_model->documento('campoData');
		$data['campoSetor']           	= $this->Campo_model->documento('campoSetor');
		$data['campoPara']           	= $this->Campo_model->documento('campoPara');
		$data['campoAssunto']         	= $this->Campo_model->documento('campoAssunto');
		$data['campoReferencia']      	= $this->Campo_model->documento('campoReferencia');
		$data['campoRedacao']         	= $this->Campo_model->documento('campoRedacao');
		
		//$data['campoObjetivo']         	= $this->Campo_model->documento('campoObjetivo');
		//$data['campoDocumentacao']      = $this->Campo_model->documento('campoDocumentacao');
		//$data['campoAnalise']         	= $this->Campo_model->documento('campoAnalise');
		//$data['campoConclusao']         = $this->Campo_model->documento('campoConclusao');

		$data['campoCarimbo']           = $this->Campo_model->documento('campoCarimbo');
		$data['carimbosDisponiveis'] 	= $this->Campo_model->documento('arrayCarimbos');
		$data['carimboSelecionado']  	= $this->input->post('campoCarimbo') ? $this->input->post('campoCarimbo') : $obj->carimbo;
		
		//$data['desp_num_processo']      = $this->Campo_model->documento('desp_num_processo');
		//$data['desp_interessado']      	= $this->Campo_model->documento('desp_interessado');
		//--- FIM ---//
		

		//--- POPULA O DROPDOWN DE REMENTETES ---//
		$this->load->model('Contato_model','',TRUE);
		$remetentes = $this->Contato_model->list_all()->result();
		if($remetentes){
			foreach ($remetentes as $remetente){
				//limita os remetentes ao original
				if($remetente->id == $obj->remetente){
					$arrayRemetentes[$remetente->id] = $remetente->nome;
				}
				
			}
		}else{
			$arrayRemetentes[1] = "";
		}
		$data['remetentesDisponiveis']  =  $arrayRemetentes;
	
		$_SESSION['remetenteSelecionado'] = $this->uri->segment(4) ? $this->uri->segment(4) : $obj->remetente;
			
		$data['remetenteSelecionado'] = $this->input->post('campoRemetente') ? $this->input->post('campoRemetente') : $_SESSION['remetenteSelecionado'];
		//--- FIM ---//
	
	
		//--- MOSTRA A SIGLA DO SETOR E COLOCA O SETORID EM UM CAMPO HIDDEN ---//
		if($data['remetenteSelecionado']){
			$setor = $this->Documento_model->get_setor($data['remetenteSelecionado'])->row();
			$remetente = $this->Contato_model->get_by_id($data['remetenteSelecionado'])->row();
		}else{
			$setor = $this->Documento_model->get_setor($obj->remetente)->row();
			$remetente = $this->Contato_model->get_by_id($obj->remetente)->row();
		}
		if(isset($setor)){
			if($setor->setorPaiSigla == "NENHUM" or $setor->setorPaiSigla == $setor->sigla){
				$data['campoSetor']['value'] = "$setor->sigla";
			}else{
				$data['campoSetor']['value'] = "$setor->sigla/$setor->setorPaiSigla";
			}
			
			$data['campoAssinatura']['value'] = $remetente->assinatura;
	
			$data['setorId'] = $setor->setorId;
		}
		else{
			$data['campoSetor']['value'] = "$setor->sigla/$setor->setorPaiSigla";
			$data['setorId'] = $obj->setor;
			
			$data['campoAssinatura']['value'] = $remetente->assinatura;
		}
		//--- FIM ---//
	
		
		//--- POPULA O DROPDOWN DE TIPOS ---//
		$this->load->model('Tipo_model','',TRUE);
		$tipos = $this->Tipo_model->list_all()->result();
		$arrayTipos[0] = "SELECIONE";
		if($tipos){
			foreach ($tipos as $tipo){
				$arrayTipos[$tipo->id] = $tipo->nome;
			}
		}else{
			$arrayTipos[1] = "";
		}
		$data['tiposDisponiveis']  =  $arrayTipos;
	
		$_SESSION['tipoSelecionado'] = $this->uri->segment(6) ? $this->uri->segment(6) : $obj->tipo;
			
		$data['tipoSelecionado'] = $this->input->post('campoTipo') ? $this->input->post('campoTipo') : $_SESSION['tipoSelecionado'];
		
		$num_of_tipos = sizeof($data['tiposDisponiveis']);
		for($i=0 ; $i<=$num_of_tipos ; $i++)
		if($i != $data['tipoSelecionado'])
		unset($data['tiposDisponiveis'][$i]);
		//--- FIM ---//
		
		
		//--- POPULA O DROPDOWN DE ANEXOS ---//
		$this->load->model('Repositorio_model','',TRUE);
		$session_setor = $this->session->userdata('setor');
		$anexos = $this->Repositorio_model->list_by_setor($session_setor)->result();
		$arrayAnexos = array();
		if($anexos){
			foreach ($anexos as $anexo_item){
		
				$array_arquivo = explode('/', $anexo_item->arquivo);
		
				$arquivo = end($array_arquivo);
		
				$array_map_item = explode('.', $arquivo);
		
				$extensao = strtolower(end($array_map_item));
		
				if($extensao != strtolower($arquivo)){
						
					$arrayAnexos[$anexo_item->id] = $anexo_item->nome;
						
				}
		
			}
		}else{
			$arrayAnexos[1] = "";
		}
		
		$data['anexosDisponiveis']  =  $arrayAnexos;
		
		$obj->anexos = substr($obj->anexos, 1, -1);

		$obj->anexos = explode(',',$obj->anexos);
		
		$data['anexoSelecionado'] = $this->input->post('campoTipo') ? $this->input->post('campoTipo') : $obj->anexos;
		
		//--- FIM ---//
		
		
		$data['campoData']['value']          = $this->_trata_dataDoBancoParaForm($obj->data);
		$data['campoAssunto']['value']       = $obj->assunto;
		$data['campoReferencia']['value']    = $obj->referencia;
		$data['campoPara']['value']       	 = $obj->para;
		$data['campoRedacao']['value']       = $obj->redacao;
		
		//$data['campoObjetivo']['value']      = $obj->objetivo;
		//$data['campoDocumentacao']['value']  = $obj->documentacao;
		//$data['campoAnalise']['value']       = $obj->analise;
		//$data['campoConclusao']['value']     = $obj->conclusao;
		
		/*
		if($obj->tipo == 3 or $obj->tipo == 5){
			$tmp = $this->Documento_model->get_despacho_head($id);
			$data['despacho_head'] = $tmp[0];
			$data['desp_num_processo']['value']  = $data['despacho_head']['num_processo'];
			$data['desp_interessado']['value']   = $data['despacho_head']['interessado'];
			$tmp = NULL;
		}
		*/
			
		//--- o tipo de validacao ($tipo_validacao) varia de acordo com o tipo de documento selecionado ($data['tipoSelecionado']) ---//
		//$tipo_validacao = $this->set_tipo_validacao($data['tipoSelecionado']);
		//--- fim --///
		
		//--- Validacao dos campos dinamicos ---//
		$_SESSION['data_original'] = $data['campoData']['value'];
		$validacao = $this->set_validacao();
		
		$campos_dinamicos = '';
		
		if($data['tipoSelecionado'] != null){
				
			$obj_tipo = $this->Tipo_model->get_by_id($data['tipoSelecionado'])->row();
				
			$this->load->model('Coluna_model','',TRUE);
			$campos_especiais = $this->Coluna_model->list_all();
		
			foreach ($campos_especiais as $key => $nome_campo){
		
				if(strpos($obj_tipo->$nome_campo, ';') != FALSE){
					$campo = explode(';' , $obj_tipo->$nome_campo);
					
					if(isset($campo[2]) and $campo[2] == ''){ // se o rotulo estiver em branco
						$campo[2] = $nome_campo; // rotulo = ao nome do campo
					}
					
				}else{
					
					$campo[0] = $obj_tipo->$nome_campo;
					//$campo[1] = 'N'; // disponibilidade
					$campo[2] = $nome_campo; // rotulo
					
				}
		
				$coluna = $this->Coluna_model->get_by_nome($nome_campo);
				
				//print_r($campo);
				
				if($campo[0] == 'S'){ // caso disponivel for igual a sim
					
					if($campo[1] == 'S'){
						$requerido = '|required';
					}else{
						$requerido = '';
					}
		
					$valor = $this->input->post('campo_'.$nome_campo) ? $this->input->post('campo_'.$nome_campo) : $obj->$nome_campo;
					
					if($nome_campo != 'para'){ //pq tem validacao propria via javascript, tem um autocomplete e tals...
					array_push($validacao, array(
							'field' => 'campo_'.$nome_campo,
							'label' => '<strong>'.$campo[2].'</strong>',
							'rules' => 'trim' . $requerido
							));
					}

					if($coluna['tipo'] == 'blob'){
						$data['input_campo'][$nome_campo] = form_textarea(array(
								'name' 	=> 'campo_'.$nome_campo,
								'id'	=> 'campo_'.$nome_campo,
								'value'	=> $valor,
								'rows'  => '15',
						));
							
					}else{
					
						$data['input_campo'][$nome_campo] = form_input(array(
								'name' 	=> 'campo_'.$nome_campo,
								'id'	=> 'campo_'.$nome_campo,
								'value'	=> $valor,
								'maxlength' => '90',
								'size' => '71',
								'class' => 'form-control',
						));
					
					}	
		
				}
		
			}
		
		}
		
		$this->form_validation->set_rules($validacao);
		//-- Fim da validacao dos campos dinamicos ---//
		
		if ($this->form_validation->run() == FALSE) {
			
			$this->load->view($this->area . "/" . $this->area.'_edit', $data);
		
		}else{
				
			$obj_do_form = array();
			
			foreach ($campos_especiais as $key => $nome_campo){
				/*
				if($this->input->post('campo_'.$nome_campo)){
					$obj_do_form[$nome_campo] = $this->input->post('campo_'.$nome_campo);
				}
				*/
				
				$obj_do_form[$nome_campo] = $this->input->post('campo_'.$nome_campo);
			}
			
			$obj_do_form_complemento = array(
					'dono' =>  $this->session->userdata('nome')." ".$this->session->userdata('sobrenome'),
					'dono_cpf' =>  $this->session->userdata('cpf'),
					'dono' =>  $this->session->userdata('nome')." ".$this->session->userdata('sobrenome'),
					'data_criacao' =>  date("Y-m-d"),
					'data' => $this->datas->dateToUS($this->input->post('campoData')),
					'remetente' => $this->input->post('campoRemetente'),
					'setor' => $this->input->post('setorId'),
					'tipo' => $this->input->post('campoTipo'),
					'assunto' => $this->input->post('campoAssunto'),
					//'referencia' => $this->input->post('campoReferencia'),
					'para' => $this->input->post('campoPara'),
					'carimbo' => $this->input->post('campoCarimbo'),				
			);
			
			if($this->input->post('campoAnexo')){
				$anexo = ',';
				foreach ($this->input->post('campoAnexo') as $key => $value){
					$anexo .= $value . ',';
				}
				$obj_do_form_complemento['anexos'] = $anexo;
			}else{
				$obj_do_form_complemento['anexos'] = null;
			}
			
			$obj_do_form = array_merge($obj_do_form, $obj_do_form_complemento);
			
			
			//--- ATENCAO! --//
			//--- MAGICA DA CONTAGEM! Esse eh o miolo do sistema! Se quiser que tudo continue funcionando NAO BULA AQUI! VC FOI AVISADO!!! ---//
			$inicio_contagem = $this->Documento_model->get_inicio_contagem($obj_do_form['tipo'], $this->datas->get_year_US($obj_do_form['data']));
		
			$obj_do_form["numero"] =  $this->_set_number($obj_do_form['setor'], $obj_do_form['tipo'], $inicio_contagem, $this->datas->get_year_US($obj_do_form['data']));
				
			$checa_existencia = $this->Documento_model->get_by_numero($obj_do_form['numero'], $obj_do_form['tipo'], $obj_do_form['setor'], $this->datas->get_year_US($obj_do_form['data']))->row();
			//--- FIM ---//
	
			
			// se a checagem retornar um valor diferente de nulo
			if ($checa_existencia != null){
	
				echo  '<br> Já existe um documento com essa numeração. <br>';
				
				echo $this->db->last_query();
	
			}else{
				
				/*
				echo "<pre>";
				print_r($obj_do_form);
				echo "</pre>";
				exit;
				*/
				
				
				if ($this->Documento_model->update($id,$obj_do_form) === FALSE){
						
					echo  '<br> Erro na atualização. <br>';
				
				}else{
					
					//--- Salva o historico ---//
					
					$obj = $this->Documento_model->get_by_id($id)->row();
					
					$texto = $this->get_layout($obj);
					
					//$texto = $this->get_layout($obj);					
					$this->Documento_model->history_save($id, $texto->layout);
					
					//exit;
					//--- Fim ---//
				
					$this->js_custom = 'var sSecs = 3;
                                function getSecs(){
                                    sSecs--;
                                    if(sSecs<0){ sSecs=59; sMins--; }
                                    $("#clock1").html(sSecs);
                                    setTimeout("getSecs()",1000);
                                    var s =  $("#clock1").html();
                                    if (s == "1"){
                                        window.location.href = "' . site_url('/'.$this->area."/view/".$id) . '";
                                    }
                                }
                                ';
						
					$data['mensagem'] = "<br /> Redirecionando em... ";
					$data['mensagem'] .= '<span id="clock1"> ' . "<script>setTimeout('getSecs()',1000);</script> </span>";
					$data['link1'] = '';
					$data['link2'] = '';
						
					$this->audita();
					$this->load->view('success', $data);

				}
	
			}
				
		}
	
	}
	
	function alerta_add($id_documento){
		
		$obj["id_documento"] = $id_documento;
		$obj["id_usuario_alerta"] = $this->session->userdata('id_usuario');
		$obj["data_alerta"] = $this->datas->dateToUS($this->input->post('campoDataAlerta'));
		
// 		$hora_alerta = $this->input->post('campoHoraAlerta');
		
// 		$minuto_alerta = $this->input->post('campoMinutoAlerta');
				
// 		$obj["hora_alerta"] = $hora_alerta . $minuto_alerta;
		
		$obj["motivo_alerta"] = $this->input->post('campoMotivoAlerta');
		
		
		if($this->input->post('campoHoraMinutoAlerta')){
		
			$hora = $this->input->post('campoHoraMinutoAlerta');
				
			$hora = $this->datas->trataHoraFormBanco($hora);
		
			$obj["hora_alerta"] = $hora;
		}
		
// 		print_r($obj);
// 		exit;
		
		$this->Documento_model->alerta_add($obj);
		
		redirect('documento/view/'.$id_documento);
		
	}
	
	function alerta_update($id_alerta){
	
		
		if($this->input->post('campoDataAlerta')){
			$obj["data_alerta"] = $this->datas->dateToUS($this->input->post('campoDataAlerta'));
		}
		
		if($this->input->post('campoHoraAlerta')){
			
			$hora = $this->input->post('campoHoraAlerta');
			
			$hora = $this->datas->trataHoraFormBanco($hora);
			
			$obj["hora_alerta"] = $hora;
		}
		
		
// 		echo $this->input->post('campoHoraMinutoAlerta');
		
		if($this->input->post('campoHoraMinutoAlerta')){
				
			$hora = $this->input->post('campoHoraMinutoAlerta');
			
			
				
			$hora = $this->datas->trataHoraFormBanco($hora);
				
			$obj["hora_alerta"] = $hora;
		}

		if($this->input->post('campoMotivoAlerta')){
			$obj["motivo_alerta"] = $this->input->post('campoMotivoAlerta');
		}
		
		if($this->input->post('campoConclusaoAlerta')){
			$obj["conclusao_alerta"] = $this->input->post('campoConclusaoAlerta');
		}
		
// 		print_r($obj);
		
// 		exit;
		
		if($this->input->post('campoDataAlerta') or $this->input->post('campoConclusaoAlerta')){

			$this->Documento_model->alerta_update($id_alerta,$obj);
		}
		
		redirect('documento');
		
	
	}
	
	function view($id){
		
		//--- VARIAVEIS COMUNS ---//	
		$data['titulo']         = "Visualização";
		$data['message']        = '';
		$data['acao']          	= "update";
		
		$data['link_back'] = '';
		$data['link_cancelar'] = '';
		
		if(isset($_SESSION['homepage'])){
			$data['link_back'] = $this->Campo_model->make_link($_SESSION['homepage'].'#d'.$id, 'history_back');
			$data['link_cancelar'] = $this->Campo_model->make_link($_SESSION['homepage'], 'cancelar_doc');
		}

		$data['link_update'] = $this->Campo_model->make_link($this->area, 'alterar', $id);
		$data['link_update_sm'] = $this->Campo_model->make_link($this->area, 'alterar_doc', $id);
		$data['link_export'] = $this->Campo_model->make_link($this->area, 'exportar_doc', $id);
		$data['link_export_sm'] = $this->Campo_model->make_link($this->area, 'exportar', $id);
		$data['link_history'] = $this->Campo_model->make_link($this->area, 'history', $id);
		$data['link_workflow'] = $this->Campo_model->make_link($this->area, 'workflow', $id);	
		
		$data['form_action_alerta']	= site_url($this->area.'/alerta_add/'.$id);
		//--- FIM ---//

		$data['objeto'] = $this->Documento_model->get_by_id($id)->row();
		
// 		echo "<pre>";
// 		print_r($data['objeto']);
// 		echo "</pre>";
		
		//--- Carimbos ---//
		$data['carimbo_pagina'] = '<a href="'.site_url($this->area.'/carimbo_pagina_on/'.$id).'">De página</a>';
		
		$data['carimbo_via'] = '<a href="'.site_url($this->area.'/carimbo_via_on/'.$id).'">De 2ª Via</a>';
		
		$data['carimbo_urgente'] = '<a href="'.site_url($this->area.'/carimbo_urgente_on/'.$id).'">De urgente</a>';
		
		$data['carimbo_confidencial'] = '<a href="'.site_url($this->area.'/carimbo_confidencial_on/'.$id).'">De confidencial</a>';

		if($data['objeto']->carimbo == 'S'){
			$data['link_stamp'] = $this->Campo_model->make_link($this->area, 'stamp_out', $id);
			$data['carimbo_pagina'] = '<a href="'.site_url($this->area.'/carimbo_pagina_off/'.$id).'">De página <i class="fa fa-check"></i></a>';
		}
		
		if($data['objeto']->carimbo_via == 'S'){
			$data['carimbo_via'] = '<a href="'.site_url($this->area.'/carimbo_via_off/'.$id).'">De 2ª Via <i class="fa fa-check"></i></a>';
		}
		
		if($data['objeto']->carimbo_urgente == 'S'){
			$data['carimbo_urgente'] = '<a href="'.site_url($this->area.'/carimbo_urgente_off/'.$id).'">De urgente <i class="fa fa-check"></i></a>';
		}
		
		if($data['objeto']->carimbo_confidencial == 'S'){
			$data['carimbo_confidencial'] = '<a href="'.site_url($this->area.'/carimbo_confidencial_off/'.$id).'">De confidencial <i class="fa fa-check"></i></a>';
		}
		//--- Fim ---//
		
		

		//--- Alerta ---//
		
		$data['campoDataAlerta'] = '';
		$check_alerta_by_doc = $this->Documento_model->check_alerta_by_doc($this->session->userdata('id_usuario'),  $id);
		
		$data['mostraFormAddAlerta'] = true;
		
		if(count($check_alerta_by_doc) > 0){
			
			//echo $check_alerta_by_doc[0]['conclusao_alerta'];
			//echo date('H:i');
			
// 			echo "<pre>";
// 			print_r($check_alerta_by_doc[0]);
// 			echo "</pre>";
			
			if($check_alerta_by_doc[0]['conclusao_alerta'] == ''){
				

				if($check_alerta_by_doc[0]['data_alerta'] < date('Y-m-d')){
					
					$data['mostraFormAddAlerta'] = true;
					
				}else{
					
					$data['campoDataAlerta'] = $this->_trata_dataDoBancoParaForm($check_alerta_by_doc[0]['data_alerta']);
					
					$data['campoHoraAlerta'] = $this->datas->trataHoraBancoForm($check_alerta_by_doc[0]['hora_alerta']);
					
					$data['mostraFormAddAlerta'] = false;
				}

				
			}else{
				$data['campoDataAlerta'] = '';
			}
			
			
// 			if($check_alerta_by_doc[0]['data_alerta'] < date('Y-m-d') and $check_alerta_by_doc[0]['hora_alerta'] < date('Hi') and $check_alerta_by_doc[0]['conclusao_alerta'] == ''){
// 				$data['mostraFormAddAlerta'] = false;
// 			}
			
// 			if($check_alerta_by_doc[0]['data_alerta'] >= date('Y-m-d') and $check_alerta_by_doc[0]['conclusao_alerta'] != ''){
// 				$data['mostraFormAddAlerta'] = false;
// 			}
			
			
			
			
		}
		
		$data['campoHoraMinutoAlerta'] = $this->Campo_model->alerta('campoHoraMinutoAlerta');
		
// 		$data['campoHoraAlerta'] = $this->Campo_model->alerta('campoHoraAlerta');
// 		$data['HorasDisponiveis'] = $this->Campo_model->alerta('arrayHoras');
// 		$data['HoraSelecionada'] = '0';
		
// 		$data['campoMinutoAlerta'] = $this->Campo_model->alerta('campoMinutoAlerta');
// 		$data['MinutosDisponiveis'] = $this->Campo_model->alerta('arrayMinutos');
// 		$data['MinutoSelecionado'] = '0';
		
		

		
// 		if(isset($data['objeto']->data_alerta)){
// 			$data['campoDataAlerta'] = $this->_trata_dataDoBancoParaForm($data['objeto']->data_alerta);
// 		}

		
		//verifica a permissao de acesso ao documento e retira alguns botoes
		$permissao = $this->get_permissao($data['objeto']->setor, $this->session->userdata('id_usuario'));
		
		$data['carimbos'] = 'yes';
		if($data['objeto']->dono_cpf != $this->session->userdata('cpf') and $permissao < 2){
				
			$data['link_update_sm'] = '';
			$data['carimbos'] = 'no';
			$data['link_history'] = '';
		}
		// fim
		
		
		
		// Definindo o cabecalho e o rodape do documento
		$this->load->model('Tipo_model','',TRUE);
		$timbre = $this->Tipo_model->get_by_id($data['objeto']->tipoID)->row();
		
		if($timbre->cabecalho == null or $timbre->cabecalho == ''){
			$data['cabecalho'] = '<img src="../../../images/header_'.$_SESSION['orgao_documento'].'.png" style="width:100%"/>';
		}else{
			$data['cabecalho'] = str_replace("../../../", "../../../", $timbre->cabecalho);
		}
		
		
		
		
		if($timbre->rodape == null or $timbre->rodape == ''){
			$data['rodape'] = $_SESSION['rodape_documento'];
		}else{
			$data['rodape'] = $timbre->rodape;
		}
		//--- FIM ---//
		
		$data['objeto'] = $this->get_layout($data['objeto']);
		
		//--- Aplica o Highlight no texto pesquisado---//
		if(isset($_SESSION['keyword_'.$this->area]) == true and $_SESSION['keyword_'.$this->area] != null and strpos($_SESSION['homepage'], 'search') == true){
			
			$palavra_destacada = $_SESSION['keyword_'.$this->area];
			
			$jogodavelha = stripos($_SESSION['keyword_'.$this->area], "#");
			
			if ($jogodavelha !== false) {
			
				$palavra_destacada = str_replace("#", "", $palavra_destacada);
			
			}

			$data['objeto']->layout = $this->highlight($data['objeto']->layout, $palavra_destacada);
			
			$data['link_back'] = $this->Campo_model->make_link($_SESSION['homepage'].'#d'.$id, 'voltar_doc');
			
		}
		//--- FIM ---//
		
		
		$data['documento_identificacao'] = $data['objeto']->tipoSigla . " Nº " . $data['objeto']->numero . "/" . $data['objeto']->ano . " - " . $this->getCaminho($data['objeto']->setor) ;
				
		$this->load->view($this->area.'/documento_view', $data);
		
		$this->audita();
		
	}
	
	
	function get_anexo($id){
		
		$this->load->model('Repositorio_model', '', TRUE);
		
		$obj = $this->Repositorio_model->get_by_id($id)->row();
		
		if($obj){
			return $obj;
		}else{
			return false;
		}
		
		
		
	}
	
	
	function get_layout($objeto){
		
		$data['objeto'] = $objeto;
		
// 		echo "<pre>";
// 		print_r($objeto);
// 		echo "</pre>";
		
		// trata os dados vindos do banco
		$data['objeto']->tipoNome = mb_convert_case($data['objeto']->tipoNome, MB_CASE_TITLE, "UTF-8");
		$date = new DateTime($data['objeto']->data);
		$data['objeto']->ano = $date->format('Y');
		$data['objeto']->data = $this->_trata_data($data['objeto']->data);
		//$data['caminho_remetente'] = $this->getCaminho($data['objeto']->setor);
		
		$data['objeto']->remetNome          = $this->_trata_contato($data['objeto']->remetNome);
		$data['objeto']->remetCargoNome      = mb_convert_case($data['objeto']->remetCargoNome, MB_CASE_TITLE, "UTF-8");
		$data['objeto']->remetSetorArtigo    ="d".mb_convert_case($data['objeto']->remetSetorArtigo, MB_CASE_LOWER, "UTF-8");
		//--- FIM --//
		
		// Efetua a substicuicao das tags personaizadas pelo conteudo dos respectivos campos
		$data['objeto']->layout = str_replace('<p', '<div', $data['objeto']->layout);
		$data['objeto']->layout = str_replace('p>', 'div>', $data['objeto']->layout);
		
		$data['objeto']->layout = str_replace('[tipo_doc]', $data['objeto']->tipoNome, $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[numero]', $data['objeto']->numero, $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[ano_doc]', $data['objeto']->ano, $data['objeto']->layout);
		
		$data['caminho_remetente'] = $this->getCaminho($data['objeto']->setor);
		$data['objeto']->layout = str_replace('[setor_doc]', $data['caminho_remetente'], $data['objeto']->layout);
		
		$data['objeto']->layout = str_replace('[data]', $data['objeto']->data, $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[destinatario]', $data['objeto']->para, $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[assunto]', $data['objeto']->assunto, $data['objeto']->layout);
		
		
		if($data['objeto']->referencia and $data['objeto']->referencia != null){
			$data['objeto']->layout = str_replace('[referencia]', '<strong>Referência: </strong>'.$data['objeto']->referencia, $data['objeto']->layout);
		}else{
			$data['objeto']->layout = str_replace('[referencia]', '', $data['objeto']->layout);
		}
			
		$data['objeto']->layout = str_replace('[referencia]', $data['objeto']->referencia, $data['objeto']->layout);
		
		$data['objeto']->layout = str_replace('[redacao]', $data['objeto']->redacao, $data['objeto']->layout);
		
		
		if(!$data['objeto']->assinatura){
			$data['objeto']->assinatura = $data['objeto']->remetNome . '<br>'.$data['objeto']->remetCargoNome.' '.$data['objeto']->remetSetorArtigo.' '.$data['objeto']->remetSetorSigla.'';
		}
		
		
		/* 
		 * Eh comum que o remetente tenha a assinatura modificada ao longo do tempo, principlamente quando ele eh promovido
		 * As linhas abaixo servem para trazer a assinatura anterior
		*/
		
		$doc_assinatura = $this->Documento_model->get_assinatura($objeto->id)->row();
		if(isset($doc_assinatura->assinatura)){
			$data['objeto']->assinatura = $doc_assinatura->assinatura;
		}
		
		$data['objeto']->assinatura = '<div style="line-height: 125%;">'.$data['objeto']->assinatura.'</div>';
		$data['objeto']->layout = str_replace('[remetente_assinatura]', $data['objeto']->assinatura, $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[remetente_nome]', mb_convert_case($data['objeto']->remetNome, MB_CASE_UPPER, "UTF-8"), $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[remetente_cargo]', mb_convert_case($data['objeto']->remetCargoNome . ' ' . $data['objeto']->remetSetorArtigo.' '.$data['objeto']->remetSetorSigla, MB_CASE_UPPER, "UTF-8"), $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[remetente_setor_artigo]', $data['objeto']->remetSetorArtigo, $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[remetente_setor_sigla]', $data['objeto']->remetSetorSigla, $data['objeto']->layout);
		
		
		//--- Anexos ---//

		if($data['objeto']->anexos and $data['objeto']->anexos != null){
		$array_anexos = explode(',',$data['objeto']->anexos);
		$array_anexos = array_slice($array_anexos, 1, -1); // remove o primeiro e o ultimo elemento
		$anexos = null;

		foreach ($array_anexos as $key => $value){

			if($this->get_anexo($value) != false){
				
				$caminho = base_url().'./'.$this->get_anexo($value)->arquivo;
			
				if($this->uri->segment(2) == 'view'){
					$anexos .= '<a href="'.$caminho.'" target="_blank">'.$this->get_anexo($value)->nome.'</a>, ';
				}else{
					$anexos .= $this->get_anexo($value)->nome.', ';
				}
			
			}

		}

		$anexos = substr($anexos, 0, -2);
			$data['objeto']->layout = str_replace('[anexos]', '<strong>Anexos: </strong>'.$anexos, $data['objeto']->layout);
		}else{
			$data['objeto']->layout = str_replace('[anexos]', '', $data['objeto']->layout);
		}
		
		//--- Fim ---//
		
		
		
		
		//--- FIM ---//
		
		
		// --- Parecer Tecnico ---//
		/*
		 $data['objeto']->layout = str_replace('[objetivo]', $data['objeto']->objetivo, $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[documentacao]', $data['objeto']->documentacao, $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[analise]', $data['objeto']->analise, $data['objeto']->layout);
		$data['objeto']->layout = str_replace('[conclusao]', $data['objeto']->conclusao, $data['objeto']->layout);
		*/
		
		//--- CAMPOS DINAMICOS ---//
		
		$this->load->model('Coluna_model','',TRUE);
		$campos_especiais = $this->Coluna_model->list_all();
		
				foreach ($campos_especiais as $key => $nome_campo){
		
				$data['objeto']->layout = str_replace('['.$nome_campo.']', $data['objeto']->$nome_campo, $data['objeto']->layout);
		
		}
		
		//--- FIM DOS CAMPOS DINAMICOS ---//
		
		return $data['objeto'];
		
	}

	function export($id){
		
		// carrega as variaveis padroes
		$data['titulo']         = $this->tituloView.$this->area;
		$data['message']        = '';
		$data['link_back']      = anchor('documento/index/'.$_SESSION['homepage'],'<span class="glyphicon glyphicon-arrow-left"></span> Voltar',array('class'=>'btn btn-warning btn-sm'));

		// popula o array com os dados do objeto alimentado pela consulta
		$data['objeto'] = $this->Documento_model->get_by_id($id)->row();
		if(!$data['objeto']) die('Documento não encontrado!<br>É tudo o que sabemos.<br><br>CTIC/AESP<br><a href="'.site_url('documento').'">&lt;- &nbsp;Voltar para a lista de documentos</a>');
		
		$data['objeto']->data_despacho = $data['objeto']->data;
	
		// Definindo o cabecalho e o rodape do documento
		$this->load->model('Tipo_model','',TRUE);
		$timbre = $this->Tipo_model->get_by_id($data['objeto']->tipoID)->row();
		
		if($timbre->cabecalho == null or $timbre->cabecalho == ''){
			$data['cabecalho'] = '<img src="./images/header_'.$_SESSION['orgao_documento'].'.png"/>';
		}else{
			$data['cabecalho'] = str_replace("../../../", "./", $timbre->cabecalho);
		}
		
		if($timbre->rodape == null or $timbre->rodape == ''){
			$data['rodape'] = $_SESSION['rodape_documento'];
		}else{
			$data['rodape'] = $timbre->rodape;
		}
		//--- FIM ---//
		
		$data['objeto'] = $this->get_layout($data['objeto']);
		
		$data['documento_identificacao'] = $data['objeto']->tipoSigla . " Nº " . $data['objeto']->numero . "/" . $data['objeto']->ano . " - " . $this->getCaminho($data['objeto']->setor) ;
			
		$this->load->view($this->area.'/pdf', $data);

		$this->audita();
			
	}
	
	/*
	function export_rtf($id){
		// carrega as variaveis padroes
		$data['titulo']         = $this->tituloView.$this->area;
		$data['message']        = '';
		$data['link_back']      = anchor('documento/index/'.$_SESSION['homepage'],'<span class="glyphicon glyphicon-arrow-left"></span> Voltar',array('class'=>'btn btn-warning btn-sm'));
	
		// popula o array com os dados do objeto alimentado pela consulta
		$data['objeto'] = $this->Documento_model->get_by_id($id)->row();
		if(!$data['objeto']) die('Documento não encontrado!<br>É tudo o que sabemos.<br><br>CTIC/AESP<br><a href="'.site_url('documento').'">&lt;- &nbsp;Voltar para a lista de documentos</a>');
		if($data['objeto']->tipoID == 3 or $data['objeto']->tipoID == 5){
			$tmp = $this->Documento_model->get_despacho_head($id);
			$data['despacho_head'] = $tmp[0];
			$tmp = NULL;
		}
		$data['objeto']->data_despacho = $data['objeto']->data;
	
		// trata os dados vindos do banco
		$data['objeto']->tipoNome = mb_convert_case($data['objeto']->tipoNome, MB_CASE_TITLE, "UTF-8");
		$date = new DateTime($data['objeto']->data);
		$data['objeto']->ano = $date->format('Y');
		$data['objeto']->data = $this->_trata_data($data['objeto']->data);
	
	
		$data['objeto']->remetNome          = $this->_trata_contato($data['objeto']->remetNome);
		$data['objeto']->remetCargoNome      = mb_convert_case($data['objeto']->remetCargoNome, MB_CASE_TITLE, "UTF-8");
		$data['objeto']->remetSetorArtigo    ="d".mb_convert_case($data['objeto']->remetSetorArtigo, MB_CASE_LOWER, "UTF-8");
	
		$this->audita();
	
		if($data['objeto']->tipoID == 4 or $data['objeto']->tipoID == 6 or $data['objeto']->tipoID == 7 or $data['objeto']->tipoID == 8){ // 4 = PARECER TECNICO, 7 = ATO ADMINISTRATIVO, 8 = NOTA DE INSTRUCAO, 9 = NOTA DE ELOGIO
			$this->load->view($this->area.'/documento_pdf_ato_adm', $data);
		}else{
			$this->load->view($this->area.'/documento_pdf', $data);
		}
	
	}
	*/

	
	function history($id){
		
		$this->js[] = 'historico';
		
		$data['titulo']     = 'Histórico do Documento';
		$data['link_back'] = $this->Campo_model->make_link('', 'history_back');
			
		// load datas
		$objetos = $this->Documento_model->get_historico($id)->result();
		
		/*
		echo "<pre>";
		print_r($objetos);
		echo "</pre>";
		*/
		
		// carregando os dados na tabela
		$this->load->library('table');
		$this->table->set_empty("&nbsp;");
		$this->table->set_heading('Item', 'Data', 'Ações');
		$data['dialogos'] = '';
		foreach ($objetos as $objeto){
			
			//$pos = strpos(htmlspecialchars_decode($objeto->texto), ' ', 300);
			//$texto = substr(htmlspecialchars_decode($objeto->texto),0,$pos);
			
			$texto = $this->_encurta_texto(htmlspecialchars_decode($objeto->texto), 400);
			
			$this->table->add_row($objeto->id_historico, $this->datas->datetimeToBR($objeto->data), 
					'<div class="btn-group">
						<a href="#dialog_'.$objeto->id_historico.'" name="modal" class="btn btn-default btn-sm"><i class="cus-zoom"></i> Visualizar texto completo</a>
					</div>'
			);
			
			$data['dialogos'] .= '<div id="dialog_'.$objeto->id_historico.'" class="window">
									<a href="#" class="close">Fechar [X]</a><br />
									<div class="modal_historico_texto">
									'. htmlspecialchars_decode($objeto->texto).'
									</div>
								</div>';
			
		}
		
		//Monta a DataTable
		$tmpl = $this->Grid_model->monta_tabela_list();
		$this->table->set_template($tmpl);
		// Fim da DataTable
		
		$data['table'] = $this->table->generate();
		
		$this->load->view($this->area.'/documento_historico', $data);
		
	
	}

	/*
	function workflow_wait(){
		$_SESSION['workflow_wait'] = "wait";
		redirect('documento/index/');
	}
	*/
	
	/*
	function stamp($id){
		$obj["carimbo"] = "S";
		$this->Documento_model->update($id,$obj);
		redirect('documento/view/'.$id);
	}
	
	function stamp_out($id){
		$obj["carimbo"] = "N";
		$this->Documento_model->update($id,$obj);
		redirect('documento/view/'.$id);
	}
	*/
	
	
	function carimbo_pagina_on($id){
		$obj["carimbo"] = "S";
		$this->Documento_model->update($id,$obj);
		redirect('documento/view/'.$id);
	}
	
	function carimbo_pagina_off($id){
		$obj["carimbo"] = "N";
		$this->Documento_model->update($id,$obj);
		redirect('documento/view/'.$id);
	}
	
	function carimbo_via_on($id){
		$obj["carimbo_via"] = "S";
		$this->Documento_model->update($id,$obj);
		redirect('documento/view/'.$id);
	}
	
	function carimbo_via_off($id){
		$obj["carimbo_via"] = "N";
		$this->Documento_model->update($id,$obj);
		redirect('documento/view/'.$id);
	}
	
	
	function carimbo_urgente_on($id){
		$obj["carimbo_urgente"] = "S";
		$this->Documento_model->update($id,$obj);
		redirect('documento/view/'.$id);
	}
	
	function carimbo_urgente_off($id){
		$obj["carimbo_urgente"] = "N";
		$this->Documento_model->update($id,$obj);
		redirect('documento/view/'.$id);
	}
	
	
	function carimbo_confidencial_on($id){
		$obj["carimbo_confidencial"] = "S";
		$this->Documento_model->update($id,$obj);
		redirect('documento/view/'.$id);
	}
	
	function carimbo_confidencial_off($id){
		$obj["carimbo_confidencial"] = "N";
		$this->Documento_model->update($id,$obj);
		redirect('documento/view/'.$id);
	}
	
	/*
	function lock($id){
		$obj["cadeado"] = "S";
		$this->Documento_model->update($id,$obj);
		redirect('documento/index/');
	}

	function unlock($id){ 
		$obj["cadeado"] = "N";
		$this->Documento_model->update($id,$obj);
		redirect('documento/index/');
	}
	*/

	function hide($id){ 
		$obj["oculto"] = "S";
		$this->Documento_model->update($id,$obj);
		$this->audita();
		redirect($_SESSION['homepage'].'#d'.$id);

	}

	function show($id){
		$obj["oculto"] = "N";
		$this->Documento_model->update($id,$obj);
		$this->audita();
		redirect($_SESSION['homepage'].'#d'.$id);
	}

	function cancela($id){
		$obj["oculto"] = "S";
		$obj["cancelado"] = "S";
		$this->Documento_model->update($id,$obj);
		$this->audita();
		redirect($_SESSION['homepage'].'#d'.$id);
	}
	
	function cancelado($id){
		//--- VARIAVEIS COMUNS ---//
		$data['titulo']         = "Documento cancelado";
		$data['message']        = '';
		$data['link_back'] 		= anchor($_SESSION['homepage'].'#d'.$id,'<span class="glyphicon glyphicon-arrow-left"></span> Voltar',array('class'=>'btn btn-warning btn-sm'));
		$data['bt_ok']    		= $_SESSION['homepage'].'#d'.$id;
		//--- FIM ---//
		
		$this->load->view($this->area . "/" . $this->area.'_cancelado', $data);
		$this->audita();
	}
	
	public function search($page = 1) {
		
		$_SESSION['homepage'] = current_url();
		
		$this->js[] = 'documento';
		
		$data['titulo'] = 'Documentos';
	
		$data['link_add']   = anchor($this->area.'/add/','<span class="glyphicon glyphicon-plus"></span> Novo',array('class'=>'btn btn-primary'));
		
		$data['form_action'] = site_url($this->area.'/search');
		
		$data['campoSearchText'] = '<input type="text" class="form-control" id="search" name="searchText" placeholder="Ex.: teste">';
		
		$data['campoSearchNumber'] = '<input type="text" class="form-control" id="searchNumero" name="searchNumber" placeholder="Ex.: 1">';
		
		$btn_search_cancel = '<a href="'.base_url().'index.php/documento/search_cancel" class="btn btn-warning"><i class="fa fa-times"></i> Limpar</a>';
		
		$data['btn_search_cancel'] = $btn_search_cancel;
		
	
		$this->load->library(array('pagination', 'table'));
	
		$data['keyword_'.$this->area] = '';

		if($this->input->post('searchText') != null or $this->input->post('searchNumber') != null or $this->input->post('campoTipo') != null){
			
			$obj = array(
						'campoDataInicial' 	=>  $this->datas->dateToUS($this->input->post('campoDataInicial')),
						'campoDataFinal' 	=> $this->datas->dateToUS($this->input->post('campoDataFinal')),
						'campoTipo'			=>  $this->input->post('campoTipo') ,
						'setorPesquisa' 	=> $this->input->post('setorPesquisa'),
						'searchText' 		=> $this->input->post('searchText'),
						'searchNumber' 		=> $this->input->post('searchNumber')
			);

			$_SESSION['objPesquisa'] = $obj;
				
		}else{
			
			$obj = $_SESSION['objPesquisa'];

		}

// 		print_r($obj);

		$busca = null;
		
		if($_SESSION['objPesquisa']['searchText'] != null){

			$busca = $_SESSION['objPesquisa']['searchText'];

			$data['campoSearchText'] = '<div class="input-group has-warning">';
			$data['campoSearchText'] .= '<input type="text" class="form-control" id="search" name="searchText" placeholder="Ex.: teste" value='.$busca.' style="background-color: #fcf8e3;">';
			$data['campoSearchText'] .= '<span class="input-group-btn">';
			$data['campoSearchText'] .= $btn_search_cancel;
			$data['campoSearchText'] .= '</span></div>';
											    
			$data['campoSearchNumber'] = '<input type="text" class="form-control" id="searchNumero" name="searchNumber" placeholder="Ex.: 1" disabled>';

		}
		
		if($_SESSION['objPesquisa']['searchNumber'] != null){
			
			$busca = $_SESSION['objPesquisa']['searchNumber'];
			
			$data['campoSearchNumber'] = '<div class="input-group has-warning">';
			$data['campoSearchNumber'] .= '<input type="text" class="form-control" id="searchNumero" name="searchNumber" placeholder="Ex.: 1" value='.$busca.' style="background-color: #fcf8e3;">';
			$data['campoSearchNumber'] .= '<span class="input-group-btn">';
			$data['campoSearchNumber'] .= $btn_search_cancel;
			$data['campoSearchNumber'] .= '</span></div>';
			
			$data['campoSearchText'] = '<input type="text" class="form-control" id="search" name="searchText" placeholder="Ex.: teste" disabled>';

		}
		
		$link_search_cancel_tipo = '';
		
		if($_SESSION['objPesquisa']['searchText'] == null and $_SESSION['objPesquisa']['searchNumber'] == null and $_SESSION['objPesquisa']['campoTipo'] != null){
			
			$nome_tipo = "TODOS";
			
			if($_SESSION['objPesquisa']['campoTipo'] != 0){
				
				$nome_tipo = $this->getTipo($_SESSION['objPesquisa']['campoTipo']);
				
				$nome_tipo = $nome_tipo[0]->nome;
					
			}

			$valor_busca = "Tipo: ".$nome_tipo;
			
			$link_search_cancel_tipo = '<div class="input-group">';
			$link_search_cancel_tipo .= '<input type="text" class="form-control" value="'.$valor_busca.'" disabled style="background-color: #fcf8e3;"><span class="input-group-btn"><a href="'.base_url().'index.php/documento/search_cancel" class="btn btn-danger"><i class="fa fa-times"></i> Limpar</a></span>';
			$link_search_cancel_tipo .= '</div>';
		
		}
		
		$data['searchText'] = isset($_SESSION['objPesquisa']['searchText']) ? $_SESSION['objPesquisa']['searchText'] : '';
		$data['searchNumber'] = isset($_SESSION['objPesquisa']['searchNumber']) ? $_SESSION['objPesquisa']['searchNumber'] : '';
		
		$data['dataInicial'] = $this->datas->dateToBr($_SESSION['objPesquisa']['campoDataInicial']);
		$data['dataFinal'] = $this->datas->dateToBr($_SESSION['objPesquisa']['campoDataFinal']);

		
		if(isset($_SESSION['keyword_'.$this->area]) == true and $_SESSION['keyword_'.$this->area] != null and $busca == null){
			
			$keyword = $_SESSION['keyword_'.$this->area];
			
		}else{
			
			//$keyword = ($busca == null or $busca == "pesquisa textual") ? redirect($this->area.'/index/') : $busca; // $keyword recebe $busca do formulátio
			
			$keyword = ($busca == "pesquisa textual") ? redirect($this->area.'/index/') : $busca; // $keyword recebe $busca do formulátio
			
			
			if($_SESSION['objPesquisa']['searchText'] != null){
					
				$keyword = $busca;

			}
			
			if($_SESSION['objPesquisa']['searchNumber'] != null){
			
				$keyword = "#".$busca;
			
			}
			 
			$_SESSION['keyword_'.$this->area] = $keyword;
		}
		
		
		$data['keyword_'.$this->area] = $keyword;
		
		if($link_search_cancel_tipo == ''){
			
			$data['link_search_cancel'] = '<div class="input-group">';
			$data['link_search_cancel'] .= '<input type="text" class="form-control" value="'.$keyword.'" disabled style="background-color: #fcf8e3;"><span class="input-group-btn"><a href="'.base_url().'index.php/documento/search_cancel" class="btn btn-danger"><i class="fa fa-times"></i> Limpar pesquisa</a></span>';
			$data['link_search_cancel'] .= '</div>';
		
		}else{
			
			$data['link_search_cancel'] = $link_search_cancel_tipo;
			
		}
		
		
		$this->audita($keyword);
	
		$maximo = 10;
	
		$uri_segment = 3;
	
		$inicio = ($this->uri->segment($uri_segment)) ? ($this->uri->segment($uri_segment, 0) - 1) * $maximo : 0;
	
		
		//--- Definindo o universo de documentos a serem pesquisados. ---//
		//--- Se o setor for restiro, ira listar apenas os domentos do setor mais os documentos criados pelo o usuário para outros setore. ---//
		$session_setor = $this->session->userdata('setor');
		$this->load->model('Setor_model','',TRUE);
		$restricao = $this->Setor_model->get_by_id($session_setor)->row()->restricao;
		
		$keyword = htmlentities($keyword, ENT_COMPAT, "UTF-8");
		
		$config_listagem = $this->get_config();
			
		$data_inicial = $config_listagem->data_inicial;
			
		$data_final = $config_listagem->data_final;

		
		//--- SETORES ---//
		$this->load->model('Setor_model','',TRUE);
		
		$session_setor = $this->session->userdata('setor');
		
		$restricao = $this->Setor_model->get_by_id($session_setor)->row();
		
		if(isset($restricao->restricao) and $restricao->restricao == 'S'){
				
			$data['setores'] = $this->Setor_model->get_by_id($session_setor)->result();
				
		}else{
				
			$data['setores'] = $this->Setor_model->list_all()->result();
				
			$arraySetores['all'] = "TODOS";
		
		}
		
		foreach ($data['setores']as $setor){
			$arraySetores[$setor->id] = "$setor->sigla" . " - " . $setor->nome;
		}
		$data['setoresDisponiveis']  =  $arraySetores;
			
		//$_SESSION['setorSelecionado'] = $_SESSION['objPesquisa']['setorPesquisa'];
		
		$data['setorSelecionado'] = $_SESSION['objPesquisa']['setorPesquisa'];
		

		if($data['setorSelecionado'] == 'all'){
			$data['setorCaminho'] = "TODOS";
		}else{
			$data['setorCaminho'] = $this->getCaminho($data['setorSelecionado']);
		}

		//--- FIM ---//
		

		//--- POPULA O DROPDOWN DE TIPOS ---//
		$this->load->model('Tipo_model','',TRUE);
		$tipos = $this->Tipo_model->list_all_actives()->result();
		$arrayTipos[0] = "TODOS OS DOCUMENTOS";
		if($tipos){
			foreach ($tipos as $tipo){
				$arrayTipos[$tipo->id] = $tipo->nome;
			}
		}else{
			$arrayTipos[1] = "";
		}
		
		$data['tiposDisponiveis']  =  $arrayTipos;
		
		$data['tipoSelecionado'] = $_SESSION['objPesquisa']['campoTipo'];
		
		$this->load->model('Coluna_model','',TRUE);
		$colunas = $this->Coluna_model->list_all();
		
		

		$rows = $this->Documento_model->listAllSearchPag($colunas, $keyword, $maximo, $inicio, $this->session->userdata('cpf'), $_SESSION['objPesquisa']['setorPesquisa'], $_SESSION['objPesquisa']['campoDataInicial'], $_SESSION['objPesquisa']['campoDataFinal'], $_SESSION['objPesquisa']['campoTipo']);
			
		$config['total_rows'] = $this->Documento_model->count_all_search($colunas, $keyword, $this->session->userdata('cpf'), $_SESSION['objPesquisa']['setorPesquisa'], $_SESSION['objPesquisa']['campoDataInicial'], $_SESSION['objPesquisa']['campoDataFinal'], $_SESSION['objPesquisa']['campoTipo']);
		
		
		$keyword = html_entity_decode($keyword, ENT_COMPAT, "UTF-8");
		//--- Fim da restricao do universo de pesquisa ---//
		
	
		$config['base_url'] = site_url($this->area.'/search');
		$config['uri_segment'] = $uri_segment;
		$config['per_page'] = $maximo;
	
		$this->pagination->initialize($config);
		$data['pagination'] = $this->pagination->create_links();
	
		// carregando os dados na tabela
		$this->table->set_empty("&nbsp;");
		$this->table->set_heading('Identificação', 'Assunto', 'Autor', 'Data','Ação');
	
		//Monta a DataTable
		$tmpl = $this->Grid_model->monta_tabela_list();
		$this->_monta_linha($rows);
		$this->table->set_template($tmpl);
		// Fim da DataTable
	
		// variaveis para a view
		$data['table'] = $this->table->generate();
		$data["total_rows"] = $config['total_rows'];
		$data['pagination'] = $this->pagination->create_links();
	
		//checa documentos pendentes de recebimento
		$data['workflow'] = $this->Documento_model->check_workflow($this->session->userdata('setor'))->num_rows();
		//fim
		
		
		
		//--- CHECA ALERTAS ---//
		 
		$data['alerta_documento_conteudo'] = '';
		$alerta = $this->Documento_model->check_alerta_by_date($this->session->userdata('id_usuario'),  date("Y-m-d"));
		$data['alerta'] = $alerta;
		if(count($alerta) > 0){
		
			$id_documento = $alerta[0]['id_documento'];
			$id_alerta = $alerta[0]['id_alerta'];
			 
			$alerta_documento_conteudo = $this->Documento_model->get_historico($id_documento)->result();
		
			$data['data_alerta_banco'] = $this->_trata_dataDoBancoParaForm($alerta[0]['data_alerta']);
			 
			$data['alerta_documento_conteudo'] = $alerta_documento_conteudo[0]->texto;
			 
			$data['data_alerta'] = $this->_trata_dataDoBancoParaForm($alerta[0]['data_alerta']) . " às " . $this->datas->trataHoraBancoForm($alerta[0]['hora_alerta']);
			 
			$data['id_documento_alerta'] = $id_documento;
			 
			$data['motivo_alerta'] = $alerta[0]['motivo_alerta'];
			 
			$data['form_action_alerta_update']	= site_url($this->area.'/alerta_update/'.$id_alerta);
			 
		}else{
			$_SESSION['tem_alerta'] = 0;
		}
		//--- FIM DOS ALERTAS ---//
		
		
/*
|--------------------------------------------------------------------------
| Configuracao de listagem de documentos
|--------------------------------------------------------------------------
*/
		
		$config_listagem = self::_config_listagem();
		
		$data['link_periodo_cancel'] = $config_listagem->link_periodo_cancel;
		$data['form_action_config_update'] = $config_listagem->form_action_config_update;
// 		$data['dataInicial'] = $config_listagem->dataInicial;
// 		$data['dataFinal'] = $config_listagem->dataFinal;
		
		// load view
		$this->audita();
		$this->load->view($this->area.'/'.$this->area.'_list', $data);
	
	}
	
	function negado($id){
		//--- VARIAVEIS COMUNS ---//
		$data['titulo']         = "Permissão negada";
		$data['message']        = 'Você não tem permissão para editar este arquivo';
		
		$data['link_back'] = $this->Campo_model->make_link($_SESSION['homepage'].'#d'.$id, 'voltar_doc');
		
		$data['bt_ok']    		= $_SESSION['homepage'].'#d'.$id;
		//--- FIM ---//
	
		$this->load->view($this->area . "/" . $this->area.'_negado', $data);
		$this->audita();
		$_SESSION['homepage'] = null;
	}
	
	function update_negado($id){

		$data['titulo'] = 'Alteração negada';
    	$data['message'] = 'Este documento foi tramitado. <br> Para alterá-lo é necessário o cancelamento da tramitação.';
    	$data['message'] .= '<p><a href="javascript: window.history.go(-1)" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-arrow-left"></span> Voltar</a></p>';
    	$this->load->view('erro', $data);
	    	
		$this->audita();
		$_SESSION['homepage'] = null;
	}

	function loadDestinatario(){		
		$this->layout = 'json';
		$keyword = $this->input->post('term');
		$data['response'] = 'false'; //Set default response
		$query = $this->Documento_model->lista_autocomplete($keyword); //Search DB

		if($query->num_rows() > 0){
			$data['response'] = 'true'; //Set response
			$data['message'] = array(); //Create array
			foreach($query->result() as $row){
				$data['message'][] = array('label'=> $row->para, 'value'=> $row->para); //Add a row to array
			}
		}
		echo json_encode($data);
	}

	function valid_date($str){

		if(!preg_match('^(0[1-9]|1[0-9]|2[0-9]|3[01])/(0[1-9]|1[012])/([0-9]{4})$^', $str)){
			
			$this->form_validation->set_message('valid_date', 'A data deve ter o formato: dd/mm/aaaa. Valores para dia: 01 a 31. Valores para mês: 01 a 12.');
			
			return false;
			
		}else{
			
			$array_data_postada = explode('/', $str);
				
			$numero_data_postada = $array_data_postada[2].$array_data_postada[1].$array_data_postada[0];
			
			$ano_postado = $array_data_postada[2];
				
			$array_hoje = explode('/', date('d/m/Y'));
			
			$ano_atual = $array_hoje[2];
			
			if($ano_postado < $ano_atual){
				$this->form_validation->set_message('valid_date', 'O ano informado ('.$ano_postado.') era menor que o ano atual! Corrigimos para a data de hoje');
				return false;
			}
			
			if(isset($_SESSION['data_original']) and $_SESSION['data_original'] != ''){
				//para alteracoes de documentos do ano anterior
				$array_original = explode('/', $_SESSION['data_original']);
					
				$ano_original = $array_original[2];
				
				if($ano_postado > $ano_original){
					$this->form_validation->set_message('valid_date', 'O ano informado ('.$ano_postado.') é superior ao ano de criação do documento ('.$ano_original.') ! Você está tentando alterar a data de um documento de um ano anterior ao atual! Operação proibida!');
					return false;
				}
			}
		
			return true;
		}
	
	}

	function _trata_data($str){

		//trata a data
		$ano = substr($str,0,4);
		$mes = substr($str,5,2);
		$dia = substr($str,8,2);

		switch ($mes) {
			case "01":
				$mes = "janeiro";
				break;
			case "02":
				$mes = "fevereiro";
				break;
			case "03":
				$mes = "março";
				break;
			case "04":
				$mes = "abril";
				break;
			case "05":
				$mes = "maio";
				break;
			case "06":
				$mes = "junho";
				break;
			case "07":
				$mes = "julho";
				break;
			case "08":
				$mes = "agosto";
				break;
			case "09":
				$mes = "setembro";
				break;
			case "10":
				$mes = "outubro";
				break;
			case "11":
				$mes = "novembro";
				break;
			case "12":
				$mes = "dezembro";
				break;
		}
		return "$dia de $mes de $ano";
	}

	function _trata_data_update($str){
		$ano = substr($str,0,4);
		$mes = substr($str,5,2);
		$dia = substr($str,8,2);
		return "$dia-$mes-$ano";
	}

	function _trata_dataDoBancoParaForm($str){
		$ano = substr($str,0,4);
		$mes = substr($str,5,2);
		$dia = substr($str,8,2);
		return "$dia/$mes/$ano";
	}

	function _trata_dataDoFormParaBanco($str){
		$dia = substr($str,0,2);
		$mes = substr($str,3,2);
		$ano = substr($str,6,4);
		return "$ano-$mes-$dia";
	}

	function _trata_contato($str){
		$str = mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
		$str = str_replace('Da ', 'da ', $str);
		$str = str_replace('De ', 'de ', $str);
		$str = str_replace('Do ', 'do ', $str);
		return $str;
	}

	function _set_number($setor, $tipo, $inicio_contagem, $ano){

		$ultimoAno = $this->Documento_model->get_ano($tipo)->ultimoAno; // ultimo ano de um tipo de documento produzido
		
		$proximoNumero = $this->Documento_model->proximo_numero($setor,$tipo,$ano)->row()->proximoNumero;

		if($proximoNumero < $inicio_contagem){
			$number = $inicio_contagem;
		}else{
			$number = $proximoNumero;
		}

		return $number;
		
	}

	function _monta_assunto($assunto, $maximo = 75){
		
		$texto = null;
		
		if(strlen($assunto)>$maximo){
			
			$texto = substr($assunto, 0, $maximo) . '...';
			
			$ultimo_espaco = strripos($texto, ' ');
			
			$texto = substr($assunto, 0, $ultimo_espaco);
			
			$texto = mb_convert_case($texto, MB_CASE_UPPER, "UTF-8");
			
			$texto = '<a href="#" class="text-info" data-toggle="popover" id="btnPopoverSetor" data-container="body" data-trigger="hover" data-placement="top" data-html="true" data-content="'.$assunto.'"><strong>'.$texto.'...</strong></a>';
			
		}else{
			
			$texto = mb_convert_case($assunto, MB_CASE_UPPER, "UTF-8");

		}

		return $texto;
	}
	
	function _encurta_texto($assunto, $maximo = 200){
	
		$texto = null;
	
		if(strlen($assunto)>$maximo){
			$texto = substr($assunto, 0, $maximo) . "...";
			$ultimo_espaco = strripos($texto, " ");
			$texto = substr($assunto, 0, $ultimo_espaco) . "...";
		}else{
			$texto = $assunto;
		}
	
		return $texto;
	}
	
	
	public function get_permissao($setor, $usuario){
	
		$this->load->model('Setor_model', '', TRUE);
	
		$permissao = $this->Setor_model->get_permissao($setor, $usuario)->row();
	
		if($permissao){
			return $permissao->permissao;
		}else{
			return 1;
		}
	
	}

	function _monta_linha($documentos){
		
		$linha = null;

		foreach ($documentos as $documento){

			$tipoNome = $this->Documento_model->get_tipo($documento->tipo)->row();

			$obj = $this->Documento_model->get_by_id($documento->id)->row();
			 
			if($documento->oculto == "N" or $documento->cadeado == null){
				//$link_hide = anchor('#doc_'.$documento->id,'');
				$link_hide = anchor('documento/hide/'.$documento->id.'#d'.$documento->id,'<i class="cus-world"></i> Público', array('class'=>'btn btn-default btn-sm', 
																																	'data-container'=>'body',
																																	'data-toggle'=>'popover', 
																																	'data-trigger'=>'hover',
																																	'data-placement'=>'top',
																																	'data-html'=>'true',
																																	'title'=>"Este documento é <strong>público</strong> <i class='cus-world'></i>",
																																	'data-content'=>'Outras pessoas podem encontrar este documento e ver seu conteúdo. Clique para mudar.',
																																	)).' ';
				$ocultado = "";
			}else{
				$ocultado = "&nbsp;*";
				$link_hide = anchor('documento/show/'.$documento->id.'#d'.$documento->id,'<i class="cus-lock"></i> Privado', array('class'=>'btn btn-default btn-sm', 
																																	'data-container'=>'body',
																																	'data-toggle'=>'popover', 
																																	'data-trigger'=>'hover',
																																	'data-placement'=>'top', 
																																	'data-html'=>'true',
																																	'title'=>"Documento <strong>privado</strong> <i class='cus-lock'></i>",
																																	'data-content'=>'Pessoas de outros setores <strong>não</strong> podem encontrar este documento e apenas você pode ver seu conteúdo. Clique para mudar.',
																																	)).' ';
			}
			
			$setorRemetente = $this->getCaminho($obj->setor);
			
			
		//--- ACOES ---//
			$permissao = $this->get_permissao($obj->setor, $this->session->userdata('id_usuario'));
			
			$acoes 	= 	null;
			//$acoes .= 	$permissao;
			$acoes .= '<div class="btn-group">';
			$acoes .= 	anchor('documento/view/'.$documento->id,'<i class="cus-zoom"></i> Visualizar', array('class'=>'btn btn-default btn-sm')).' ';
			
			if($documento->cancelado == "N" or $documento->cancelado == null){
				
				$acoes .=	anchor('documento/export/'.$documento->id,'<i class="fa fa-file-pdf-o fa-lg" style="color: #d9534f;"></i> Exportar',array('target'=>'_blank', 'class'=>'btn btn-default btn-sm')).' ';
				
				if($documento->dono_cpf == $this->session->userdata('cpf') or $permissao >= 2){
					$acoes .=	anchor('documento/update/'.$documento->id,'<i class="cus-pencil"></i> Alterar', array('class'=>'btn btn-default btn-sm')).' ';
				}
				
				if($documento->dono_cpf == $this->session->userdata('cpf') or $permissao == 3){
					$acoes .=	$link_hide;
					$acoes .=	anchor('documento/cancela/'.$documento->id,'<i class="cus-cancel"></i> Cancelar',array('onclick' => "return confirm('Deseja REALMENTE cancelar esse registro?')", 'class'=>'btn btn-default btn-sm')).' ';
				}
				
			}else{
				
				$acoes .= anchor($_SESSION['homepage'].'#d'.$documento->id,'<i class="cus-cancel"></i> Cancelado', array('class'=>'btn btn-default btn-sm', 'disabled'=>'disabled'));
				
			}
			$acoes .= '</div>';
		//--- FIM ---//

			
			$documento->assunto = $this->_monta_assunto($documento->assunto);
			
			if(isset($_SESSION['keyword_'.$this->area])){
			
				$documento->assunto = $this->highlight($documento->assunto, $_SESSION['keyword_'.$this->area]);
				
				$obj->dono = $this->highlight($obj->dono, $_SESSION['keyword_'.$this->area]);
				
				$numero_procurado = str_replace("#", "", $_SESSION['keyword_'.$this->area]);
				
				$documento->numero = $this->highlight($documento->numero, $numero_procurado);
			}

			$linha = $this->table->add_row(
					'<a name="d'.$documento->id.'" id="d'.$documento->id.'"></a>' .
					"$tipoNome->abreviacao Nº $documento->numero <br> $setorRemetente",
					'<small>'.$documento->assunto.'</small>',
					'<small>'.$obj->dono.'</small>',
					$this->_trata_dataDoBancoParaForm($documento->data_criacao),
					$acoes
			);

		}

		return $linha;

	}
	
	function highlight($text, $words) {
		/*
		preg_match_all('~\w+~', $words, $m);
		if(!$m)
			return $text;
		$re = '~\\b(' . implode('|', $m[0]) . ')\\b~i';
		return preg_replace($re, "<span style='background-color:#FFFF00'>$0</span>", $text);
		*/
		
		$words = htmlentities($words, ENT_COMPAT, "UTF-8");
		
		//return str_ireplace($words, "<span style='background-color:#FFFF00'>$words</span>", $text);
		
		return preg_replace("/($words)/i", '<span style="background-color:#FFFF00">$0</span>', $text);


	}


    public function search_cancel() { 
        
        $_SESSION['keyword_'.$this->area] = null;
        unset($_SESSION['keyword_'.$this->area]);
        
        
        $_SESSION['objPesquisa'] = null;
        unset($_SESSION['objPesquisa']);

        $this->audita();
        redirect('documento/index/');

    }
    
    public function getTipo ($idTipo){

    	$this->load->model('Tipo_model', '', TRUE);
    	$tipo = $this->Tipo_model->get_by_id($idTipo)->result();
    		
    	return $tipo;
    }
    
    public function getSetor ($id_setor){
    
    	$this->load->model('Setor_model', '', TRUE);
		$setor =  $this->Setor_model->get_by_id($id_setor)->row();
    
    	return $setor;
    }
    
    public function getUsuario ($id_usuario){
    
    	$this->load->model('Usuario_model', '', TRUE);
    	$usuario =  $this->Usuario_model->get_by_id($id_usuario)->row();
    
    	return $usuario;
    }
    
    public function getCaminho ($id_setor){
    
    	$this->load->model('Setor_model', '', TRUE);
    	$setor =  $this->Setor_model->get_by_id($id_setor)->row();
    	
    	if($setor->setorPaiSigla and $setor->setorPaiSigla != "NENHUM" and $setor->setorPaiSigla != $setor->orgaoSigla and $setor->sigla != $setor->setorPaiSigla){
    		
    		$caminho =  $setor->sigla ."/" . $setor->setorPaiSigla ."/" . $setor->orgaoSigla;
    	
    	}else{
    		
    		if($setor->sigla != $setor->orgaoSigla){
    			$caminho =  $setor->sigla ."/" . $setor->orgaoSigla;
    		}else{
    			$caminho =  $setor->sigla;
    		}
    			
    	}
    	
    	return $caminho;
    }
    
    public function audita ($informacao_adicional = null){
    	
    	$this->load->model('Auditoria_model','',TRUE);
    	
    	$complemento = null;
    	if($informacao_adicional){
    		$complemento = "?".$informacao_adicional;
    	}
    
   		$obj_audit = array(
				'usuario' => $this->session->userdata('id_usuario'),
				'usuario_nome' => $this->session->userdata('nome'),
				'data' => date("Y/m/d H:i:s"),
				'url' => current_url().$complemento,
		);
		
		if(isset($_SESSION['current_url']) == true and $_SESSION['current_url'] != current_url()){

			$_SESSION['current_url'] = current_url();
			$this->Auditoria_model->save($obj_audit);			
			
		}else{
			
			$_SESSION['current_url'] = current_url();
			
		}

    }
    
    /*
    function set_tipo_validacao($tipoSelecionado){
    	
    	$tipo_validacao = $this->area."/add"; // valor default
    	
    	switch ($tipoSelecionado) {
    		
    		case 1: 
    			$tipo_validacao = $this->area."/add"; // Comuicacao Interna
    		break;
    		
    		case 2: 
    			$tipo_validacao = $this->area."/add"; // Oficio
    		break;
    		
    		case 3: 
    			$tipo_validacao = $this->area."/add"; // Despacho
    		break;
    		
    		case 4: 
    			$tipo_validacao = $this->area."/add_parecer_tecnico"; // Parecer Tecnico
    		break;
    		
    		case 5: 
    			$tipo_validacao = $this->area."/add"; // Parecer Juridico
    		break;
    		
    		case 6: 
    			$tipo_validacao = $this->area."/add_sem_para"; // Ato Administrativo
    		break;
    		
    		case 7: 
    			$tipo_validacao = $this->area."/add_sem_para"; // Nota de Instrucao
    		break;
    		
    		case 8: 
    			$tipo_validacao = $this->area."/add_sem_para"; // Nota de Elogio
    		break;
    		
    		case 9: 
    			$tipo_validacao = $this->area."/add_sem_para"; // Despacho da CEPAD (Comissao Especial Permanente de Acompanhamento Disciplinar das Galaxias)
    		break;
    		
    	}
  
    	return $tipo_validacao;
    	
    }
    */

    
    //--- METODO QUE CHECA SE AS TABELAS DO SISTEMA ESTAO POPULADAS ---//
    function _checa_tabelas(){
    	
    		$data['message'] = '';
    		
			$this->load->model('Tipo_model','',TRUE);
			if($this->Tipo_model->count_all() == 0){
				$data['message'] .= 'Nenhum tipo de documento cadastrado. Cadastre um.<br>';
			}
			
			if($this->Tipo_model->list_all_actives()->result() == null){
				$data['message'] .= 'Nenhum tipo de documento publicado. Pelo menos um deve ser publicado.<br>';
			}
			
			$this->load->model('Orgao_model','',TRUE);
			if($this->Orgao_model->count_all() == 0){
				$data['message'] .= 'Nenhum órgão cadastrado. Cadastre um.<br>';
			}
			
			$this->load->model('Setor_model','',TRUE);
			if($this->Setor_model->count_all() == 0){
				$data['message'] .= 'Nenhum setor cadastrado. Cadastre um.<br>';
			}
			
			$this->load->model('Cargo_model','',TRUE);
			if($this->Cargo_model->count_all() == 0){
				$data['message'] .= 'Nenhum cargo cadastrado. Cadastre um.<br>';
			}
			
			$this->load->model('Contato_model','',TRUE);
			if($this->Contato_model->count_all() == 0){
				$data['message'] .= 'Nenhum remetente cadastrado. Cadastre um.<br>';
			}
			
			$_SESSION['message'] = $data['message'];
			
			if($data['message'] != ''){
				redirect('documento/erro_tabelas/');
			}
    }
    //--- FIM ---//
    
    
    function erro_tabelas(){
    	 
    	$data['titulo'] = 'Erro';
		
        $data['message'] = $_SESSION['message'];
        
		$data['link_back'] = '';

		$this->load->view('erro', $data);
    		
    }
    
    function get_SessTimeLeft(){
    	

    	$SessTimeLeft    = 0;
    	$SessExpTime     = $this->config->config["sess_expiration"];
    	$CurrTime        = time();
    	 
    	$SQL = 'SELECT last_activity
				FROM ci_sessions
				WHERE session_id = '." '".$this->session->userdata('session_id')."' ";
    	//print "$SQL";
    	$query = $this->db->query($SQL);
    	$arrLastActivity = $query->result_array();
    	//print "LastActivity: ".$arrLastActivity[0]["last_activity"]."\r\n";
    	//print "CurrentTime: ".$CurrTime."\r\n";
    	//print "ExpTime: ".$SessExpTime."\r\n";
    	$SessTimeLeft = ($SessExpTime - ($CurrTime - $arrLastActivity[0]["last_activity"]));
    	
    	return $SessTimeLeft;
    }
    
    
}
?>
