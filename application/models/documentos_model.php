<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class documentos_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getDocumentos($orderby='nombre ASC')
	{
		$sql = '';
		$res = $this->db->query("SELECT
					id_documento, nombre, url_form, url_print, status, orden
				FROM rastria_documentos
				WHERE status = true
				ORDER BY {$orderby}
				");

		$response = array(
				'documentos'    => array(),
		);
		if($res->num_rows() > 0){
			$response['documentos'] = $res->result();
		}

		return $response;
	}

  /**
   * Obtiene los documentos que se asignaron a la factura cuando se agrego.
   *
   * @return mixed array|boolean
   */
  public function getClienteDocs($idFactura)
  {
    $query = $this->db->query(
      "SELECT fd.id_documento,
              fd.data,
              fd.status,
              rd.nombre,
              rd.url_form,
              rd.url_print,
              rd.status AS status_rastria,
              rd.orden
       FROM rastria_documentos AS rd
       INNER JOIN facturacion_documentos AS fd ON fd.id_documento = rd.id_documento
       WHERE fd.id_factura = {$idFactura} AND rd.status = true
       ORDER BY rd.orden ASC"
    );

    if ($query->num_rows() > 0)
      return $query->result();

    return false;
  }


}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */