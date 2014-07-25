<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class productos_traspasos extends MY_Controller {

    /**
     * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
     * @var unknown_type
     */
    private $excepcion_privilegio = array(
        'productos_traspasos/ajax_get_productos/',
        'productos_traspasos/ajax_verifica_producto/',
    );

    public function _remap($method){

        $this->load->model("usuarios_model");
        if($this->usuarios_model->checkSession()){
            $this->usuarios_model->excepcion_privilegio = $this->excepcion_privilegio;
            $this->info_empleado                         = $this->usuarios_model->get_usuario_info($this->session->userdata('id_usuario'), true);

            if($this->usuarios_model->tienePrivilegioDe('', get_class($this).'/'.$method.'/')){
                $this->{$method}();
            }else
                redirect(base_url('panel/home?msg=1'));
        }else
            redirect(base_url('panel/home'));
    }

    public function index()
    {
        $this->carabiner->js(array(
            array('libs/jquery.numeric.js'),
            array('general/msgbox.js'),
            // array('general/supermodal.js'),
            array('panel/almacen/traspasos/traspasos.js'),
        ));

        $params['info_empleado'] = $this->info_empleado['info']; //info empleado
        $params['seo'] = array(
            'titulo' => 'Administración de Productos'
        );

        $this->load->model('productos_traspasos_model');
        $this->load->model('empresas_model');
        $this->load->model('inventario_model');

        $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

        $this->productos_traspasos_model->setEmpresaId($params['empresa']->id_empresa);

        $filtroProducto = isset($_GET['filtro_producto']) ? $_GET['filtro_producto'] : '';

        $params['data'] = $this->productos_traspasos_model->productosDeLaEmpresa($filtroProducto);

        $params['html_productos'] = $this->load->view('panel/almacen/productos/traspasos/productos_empresa', $params, true);

        if (isset($_GET['msg']))
        {
            $params['frm_errors'] = $this->showMsgs($_GET['msg']);
        }

        $this->load->view('panel/header', $params);
        $this->load->view('panel/general/menu', $params);
        $this->load->view('panel/almacen/productos/traspasos/traspasos', $params);
        $this->load->view('panel/footer');
    }

    public function ajax_get_productos()
    {
        $this->load->model('productos_traspasos_model');

        $params = array();

        $this->productos_traspasos_model->setEmpresaId($_GET['empresa_id']);
        $filtroProducto = isset($_GET['filtro_producto']) ? $_GET['filtro_producto'] : '';
        $params['data'] = $this->productos_traspasos_model->productosDeLaEmpresa($filtroProducto);

        $html = $this->load->view('panel/almacen/productos/traspasos/productos_empresa', $params, true);

        echo json_encode(array('response' => array('ico' => 'success'), 'data' => $html));
    }

    public function ajax_verifica_producto()
    {
        $this->load->model('productos_traspasos_model');

        $response = $this->productos_traspasos_model->empresaTieneProducto($_GET['empresa_id'], $_GET['producto']);

        echo json_encode($response);
    }

    public function agregar()
    {
        $this->load->model('productos_traspasos_model');

        $this->productos_traspasos_model->agregarTraspaso($_POST);

        echo json_encode(array('passes' => true));
    }

      private function showMsgs($tipo, $msg='', $title='Facturacion!')
      {
        switch($tipo)
        {
            case 1:
                $txt = 'El campo ID es requerido.';
                $icono = 'error';
                break;
            case 2: //Cuendo se valida con form_validation
                $txt = $msg;
                $icono = 'error';
                break;
            case 3:
                $txt = 'El traspaso se realizo correctamente.';
                $icono = 'success';
                break;
        }

        return array(
            'title' => $title,
            'msg' => $txt,
            'ico' => $icono
        );
    }
}