<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class etiquetas_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function etiqueta1_pdf($data)
  {
    $empresa = $this->empresas_model->getInfoEmpresa($data['did_empresa'], true);
    $logo = $this->imgGrayScale($empresa['info']->logo);

    $pdf = new FPDF('P', 'mm', [50, 25]);
    // $generator = new Picqer\Barcode\BarcodeGeneratorPNG();

    $data['caja'] = str_pad($data['caja'], 6, "0", STR_PAD_LEFT);
    $pdf->AddPage('L', [50, 25]);
    $pdf->SetFont('Arial','B', 18);
    $pdf->Text(21, 15, "C-{$data['caja']}");
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Image($logo, 1, 5, 18);

    for ($rollo=0; $rollo < intval($data['rollos']); $rollo++) {
      $pdf->AddPage('L', [50, 25]);
      $pdf->Image($logo, 1, 5, 18);

      $rollo1 = str_pad($rollo+1, 6, "0", STR_PAD_LEFT);

      $pdf->SetFont('Arial', 'B', 12);
      $pdf->Text(25, 9, "C-{$data['caja']}");
      $pdf->Line(22, 13, 47, 13);
      $pdf->Line(22, 13.2, 47, 13.2);
      $pdf->Line(22, 13.4, 47, 13.4);
      $pdf->SetFont('Arial','B', 12);
      $pdf->Text(25, 19, "R-{$rollo1}");

    }

    // for ($i=intval($data['txtCvar']); $i < $consecutivo; $i++) {
    //   unlink(RPATH."/panel/default/images/tmp/barcode{$i}.png");
    //   unlink(RPATH."/panel/default/images/tmp/barcode2{$i}.png");
    // }

    $pdf->Output('etiquetas.pdf', 'I');
  }

  private function imgGrayScale($logo) {
    $partes_ruta = pathinfo($logo);
    $im = null;

    if ($partes_ruta['extension'] == 'png') {
      $im = imagecreatefrompng($logo);
    } elseif ($partes_ruta['extension'] == 'jpg' || $partes_ruta['extension'] == 'jpeg') {
      $im = imagecreatefromjpeg($logo);
    }

    $path = $logo;
    if ($im && imagefilter($im, IMG_FILTER_GRAYSCALE)) {
      if ($partes_ruta['extension'] == 'png') {
        imagepng($im, "application/media/temp/{$partes_ruta['basename']}");
      } elseif ($partes_ruta['extension'] == 'jpg' || $partes_ruta['extension'] == 'jpeg') {
        imagejpeg($im, "application/media/temp/{$partes_ruta['basename']}");
      }
      $path = "application/media/temp/{$partes_ruta['basename']}";
    }

    return $path;
  }

	/**
	 * Obtiene el listado de etiquetas para usar ajax
	 * @param term. termino escrito en la caja de texto
	 */
	public function ajaxEtiquetas(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

		$res = $this->db->query("
				SELECT id_etiqueta, nombre, kilos, status
				FROM etiquetas
				WHERE status = 't' ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_etiqueta,
						'label' => $itm->nombre,
						'value' => $itm->nombre,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */