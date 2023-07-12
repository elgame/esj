<?php
class inventario_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}

	/*-------------------------------------------
	 * --------- Reportes de compras ------------
	 -------------------------------------------*/

	/**
	 * Reporte existencias por unidad
	 *
	 * @return
	 */
	public function getCProveedorData()
  {
		$sql = '';
	   $idsproveedores = '';

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

		if($this->input->get('fid_producto') != ''){
			$sql .= " AND cp.id_producto = ".$this->input->get('fid_producto');
		}
		$this->load->model('empresas_model');
		$client_default = $this->empresas_model->getDefaultEmpresa();
		$_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
		$_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
     	$idsproveedores .= " AND id_empresa = '".$this->input->get('did_empresa')."'";
    }

    	$sel_proveedores = false;
	    if(is_array($this->input->get('ids_proveedores')))
	    {
	    	$idsproveedores .= " AND id_proveedor IN(".implode(',', $this->input->get('ids_proveedores')).")";
	    	$sel_proveedores = true;
	    }

	    $proveedores = $this->db->query("SELECT id_proveedor, nombre_fiscal FROM proveedores WHERE 1 = 1 ".$idsproveedores);
	    $response = array();
	    foreach ($proveedores->result() as $key => $proveedor)
	    {
	    	$productos = $this->db->query("SELECT p.id_producto, p.nombre, pu.abreviatura, cp.cantidad, cp.importe, cp.impuestos, cp.total
					FROM
						productos AS p INNER JOIN (
							SELECT cp.id_producto, SUM(cp.cantidad) AS cantidad, SUM(cp.importe) AS importe, (SUM(cp.iva) - SUM(cp.retencion_iva)) AS impuestos, SUM(cp.total) AS total
							FROM compras AS c
								INNER JOIN compras_facturas AS cf ON c.id_compra = cf.id_compra
								INNER JOIN compras_productos AS cp ON cf.id_orden = cp.id_orden
							WHERE cp.id_producto IS NOT NULL AND c.id_proveedor = {$proveedor->id_proveedor} {$sql} AND
								Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
							GROUP BY cp.id_producto
						) AS cp ON p.id_producto = cp.id_producto
	    				INNER JOIN productos_unidades AS pu ON p.id_unidad = pu.id_unidad
					ORDER BY p.nombre ASC");
	    	if ($productos->num_rows() > 0 || $sel_proveedores)
	    	{
		    	$proveedor->productos = $productos->result();
		    	$response[] = $proveedor;
	    	}
	    }

		return $response;
	}
	/**
	 * Reporte existencias por unidad pdf
	 */
	public function getCProveedorPdf(){
		$res = $this->getCProveedorData();

	    $this->load->model('empresas_model');
	    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');

	    if ($empresa['info']->logo !== '')
	      $pdf->logo = $empresa['info']->logo;

	  $pdf->titulo1 = $empresa['info']->nombre_fiscal;
		$pdf->titulo2 = 'Reporte de Compras por Proveedor';
		$pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'R', 'C', 'R', 'R', 'R');
		$widths = array(65, 30, 20, 30, 30, 30);
		$header = array('Nombre (Producto, Servicio)', 'Cantidad', 'Unidad', 'Neto', 'Impuestos', 'Total');

		$familia = '';
		$total_cantidad = $total_importe = $total_impuestos = $total_total = 0;
		foreach($res as $key => $item){
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}

			$pdf->SetFont('Arial','B',10);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths(array(150));
			$pdf->Row(array($item->nombre_fiscal), false, false);

			$pdf->SetFont('Arial','',8);
			$proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
			foreach ($item->productos as $key => $producto)
			{
				$datos = array($producto->nombre,
					MyString::formatoNumero($producto->cantidad, 2, '', false),
					$producto->abreviatura,
					MyString::formatoNumero($producto->importe, 2, '', false),
					MyString::formatoNumero($producto->impuestos, 2, '', false),
					MyString::formatoNumero(($producto->total), 2, '', false),
					);
				$pdf->SetXY(6, $pdf->GetY()-2);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($datos, false, false);

				$proveedor_cantidad  += $producto->cantidad;
				$proveedor_importe   += $producto->importe;
				$proveedor_impuestos += $producto->impuestos;
				$proveedor_total     += $producto->total;
			}

			$datos = array('Total Proveedor',
				MyString::formatoNumero($proveedor_cantidad, 2, '', false),
				'',
				MyString::formatoNumero($proveedor_importe, 2, '', false),
				MyString::formatoNumero($proveedor_impuestos, 2, '', false),
				MyString::formatoNumero(($proveedor_total), 2, '', false),
				);
			$pdf->SetXY(6, $pdf->GetY());
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);

			$total_cantidad  += $proveedor_cantidad;
			$total_importe   += $proveedor_importe;
			$total_impuestos += $proveedor_impuestos;
			$total_total     += $proveedor_total;
		}

		$datos = array('Total General',
			MyString::formatoNumero($total_cantidad, 2, '', false),
			'',
			MyString::formatoNumero($total_importe, 2, '', false),
			MyString::formatoNumero($total_impuestos, 2, '', false),
			MyString::formatoNumero(($total_total), 2, '', false),
			);
		$pdf->SetXY(6, $pdf->GetY());
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->Row($datos, false);

		$pdf->Output('compras_proveedor.pdf', 'I');
	}

  public function getCProveedorXls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=compras_x_proveedor.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getCProveedorData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de Compras por Proveedor';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
      $html .= '<tr style="font-weight:bold">
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Nombre (Producto, Servicio)</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Neto</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Impuestos</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
      </tr>';
    $familia = '';
    $total_cantidad = $total_importe = $total_impuestos = $total_total = 0;
    foreach($res as $key => $item){
      $html .= '<tr>
          <td colspan="6" style="font-weight:bold">'.$item->nombre_fiscal.'</td>
        </tr>';

      $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
      foreach ($item->productos as $key => $producto)
      {
        $html .= '<tr>
            <td style="width:400px;border:1px solid #000;">'.$producto->nombre.'</td>
            <td style="width:150px;border:1px solid #000;">'.$producto->abreviatura.'</td>
            <td style="width:150px;border:1px solid #000;">'.$producto->cantidad.'</td>
            <td style="width:150px;border:1px solid #000;">'.$producto->importe.'</td>
            <td style="width:150px;border:1px solid #000;">'.$producto->impuestos.'</td>
            <td style="width:150px;border:1px solid #000;">'.$producto->total.'</td>
          </tr>';

        $proveedor_cantidad  += $producto->cantidad;
        $proveedor_importe   += $producto->importe;
        $proveedor_impuestos += $producto->impuestos;
        $proveedor_total     += $producto->total;
      }

      $html .= '
          <tr style="font-weight:bold">
            <td colspan="2">Total Proveedor</td>
            <td style="border:1px solid #000;">'.$proveedor_cantidad.'</td>
            <td style="border:1px solid #000;">'.$proveedor_importe.'</td>
            <td style="border:1px solid #000;">'.$proveedor_impuestos.'</td>
            <td style="border:1px solid #000;">'.$proveedor_total.'</td>
          </tr>
          <tr>
            <td colspan="6"></td>
          </tr>';
      $total_cantidad  += $proveedor_cantidad;
      $total_importe   += $proveedor_importe;
      $total_impuestos += $proveedor_impuestos;
      $total_total     += $proveedor_total;

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="2">Total General</td>
          <td style="border:1px solid #000;">'.$total_cantidad.'</td>
          <td style="border:1px solid #000;">'.$total_importe.'</td>
          <td style="border:1px solid #000;">'.$total_impuestos.'</td>
          <td style="border:1px solid #000;">'.$total_total.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

  /**
   * Reporte existencias por unidad
   *
   * @return
   */
  public function getSProveedorData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    if($this->input->get('fid_producto') != ''){
      $sql .= " AND cp.id_producto = ".$this->input->get('fid_producto');
    }
    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if(is_array($this->input->get('ids_proveedores')))
    {
      $sql .= " AND c.id_proveedor IN(".implode(',', $this->input->get('ids_proveedores')).")";
    }

    $productos = $this->db->query("
      SELECT c.id_compra, (c.serie||c.folio) AS folio_compra, co.folio AS folio_orden,
        p.id_proveedor, p.nombre_fiscal AS proveedor, pr.nombre AS producto, cp.observaciones,
        cp.cantidad, cp.importe, (cp.iva - cp.retencion_iva) AS impuestos, cp.total, pu.nombre AS unidad
      FROM compras c
        INNER JOIN compras_productos cp ON c.id_compra = cp.id_compra
        INNER JOIN productos pr ON pr.id_producto = cp.id_producto
        INNER JOIN productos_unidades pu ON pu.id_unidad = pr.id_unidad
        INNER JOIN compras_ordenes co ON co.id_orden = cp.id_orden
        INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
      WHERE c.status <> 'ca' AND co.tipo_orden = 'd' {$sql}
        AND Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
      ORDER BY p.nombre_fiscal ASC, cp.id_orden ASC, cp.num_row ASC");

    $response = $productos->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getSProveedorPdf(){
    $res = $this->getSProveedorData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte de Servicios por Proveedor';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'C', 'R', 'R', 'R');
    $widths = array(95, 20, 20, 25, 20, 25);
    $header = array('Servicio', 'Cantidad', 'Unidad', 'Neto', 'Impuestos', 'Total');

    $proveedor = '';
    $total_cantidad = $total_importe = $total_impuestos = $total_total = 0;
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $item){
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      if ($proveedor != $item->id_proveedor) {

        if ($key > 0) {
          $datos = array('Total Proveedor',
            MyString::formatoNumero($proveedor_cantidad, 2, '', false),
            '',
            MyString::formatoNumero($proveedor_importe, 2, '', false),
            MyString::formatoNumero($proveedor_impuestos, 2, '', false),
            MyString::formatoNumero(($proveedor_total), 2, '', false),
          );
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, false, 'B');
        }

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths(array(150));
        $pdf->Row(array($item->proveedor), false, false);

        $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
        $proveedor = $item->id_proveedor;
      }

      $pdf->SetFont('Arial','',8);
      $datos = array($item->producto."({$item->observaciones})",
        MyString::formatoNumero($item->cantidad, 2, '', false),
        $item->unidad,
        MyString::formatoNumero($item->importe, 2, '', false),
        MyString::formatoNumero($item->impuestos, 2, '', false),
        MyString::formatoNumero(($item->total), 2, '', false),
      );
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, 'B');

      $proveedor_cantidad  += $item->cantidad;
      $proveedor_importe   += $item->importe;
      $proveedor_impuestos += $item->impuestos;
      $proveedor_total     += $item->total;

      $total_cantidad  += $item->cantidad;
      $total_importe   += $item->importe;
      $total_impuestos += $item->impuestos;
      $total_total     += $item->total;
    }

    $pdf->SetFont('Arial','B',8);
    $datos = array('Total Proveedor',
      MyString::formatoNumero($proveedor_cantidad, 2, '', false),
      '',
      MyString::formatoNumero($proveedor_importe, 2, '', false),
      MyString::formatoNumero($proveedor_impuestos, 2, '', false),
      MyString::formatoNumero(($proveedor_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row($datos, false, 'B');

    $datos = array('Total General',
      MyString::formatoNumero($total_cantidad, 2, '', false),
      '',
      MyString::formatoNumero($total_importe, 2, '', false),
      MyString::formatoNumero($total_impuestos, 2, '', false),
      MyString::formatoNumero(($total_total), 2, '', false),
      );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row($datos, false, 'B');

    $pdf->Output('servicios_proveedor.pdf', 'I');
  }

  public function getSProveedorXls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=compras_x_proveedor.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getCProveedorData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de Compras por Proveedor';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
      $html .= '<tr style="font-weight:bold">
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Nombre (Producto, Servicio)</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Neto</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Impuestos</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
      </tr>';
    $familia = '';
    $total_cantidad = $total_importe = $total_impuestos = $total_total = 0;
    foreach($res as $key => $item){
      $html .= '<tr>
          <td colspan="6" style="font-weight:bold">'.$item->nombre_fiscal.'</td>
        </tr>';

      $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
      foreach ($item->productos as $key => $producto)
      {
        $html .= '<tr>
            <td style="width:400px;border:1px solid #000;">'.$producto->nombre.'</td>
            <td style="width:150px;border:1px solid #000;">'.$producto->abreviatura.'</td>
            <td style="width:150px;border:1px solid #000;">'.$producto->cantidad.'</td>
            <td style="width:150px;border:1px solid #000;">'.$producto->importe.'</td>
            <td style="width:150px;border:1px solid #000;">'.$producto->impuestos.'</td>
            <td style="width:150px;border:1px solid #000;">'.$producto->total.'</td>
          </tr>';

        $proveedor_cantidad  += $producto->cantidad;
        $proveedor_importe   += $producto->importe;
        $proveedor_impuestos += $producto->impuestos;
        $proveedor_total     += $producto->total;
      }

      $html .= '
          <tr style="font-weight:bold">
            <td colspan="2">Total Proveedor</td>
            <td style="border:1px solid #000;">'.$proveedor_cantidad.'</td>
            <td style="border:1px solid #000;">'.$proveedor_importe.'</td>
            <td style="border:1px solid #000;">'.$proveedor_impuestos.'</td>
            <td style="border:1px solid #000;">'.$proveedor_total.'</td>
          </tr>
          <tr>
            <td colspan="6"></td>
          </tr>';
      $total_cantidad  += $proveedor_cantidad;
      $total_importe   += $proveedor_importe;
      $total_impuestos += $proveedor_impuestos;
      $total_total     += $proveedor_total;

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="2">Total General</td>
          <td style="border:1px solid #000;">'.$total_cantidad.'</td>
          <td style="border:1px solid #000;">'.$total_importe.'</td>
          <td style="border:1px solid #000;">'.$total_impuestos.'</td>
          <td style="border:1px solid #000;">'.$total_total.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

	/**
	 * Reporte existencias por unidad
	 *
	 * @return
	 */
	public function getCProductosData()
  {
		$sql = '';
	  $idsproveedores = '';

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

		$this->load->model('empresas_model');
		$client_default = $this->empresas_model->getDefaultEmpresa();
		$_GET['did_empresa'] = (isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : $client_default->id_empresa);
		$_GET['dempresa']    = (isset($_GET['dempresa']{0}) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
	    if($this->input->get('did_empresa') != ''){
	      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
	      $idsproveedores = " WHERE p.id_empresa = '".$this->input->get('did_empresa')."'";
	    }

	    if(is_array($this->input->get('ids_productos')))
	    	$idsproveedores .= " AND p.id_producto IN(".implode(',', $this->input->get('ids_productos')).")";

      if(is_array($this->input->get('familias'))){
        $idsproveedores .= " AND p.id_familia IN(".implode(',', $this->input->get('familias')).")";
      }

      if ($this->input->get('dcon_mov') == 'si') {
        $idsproveedores .= " AND COALESCE(cp.total, 0) > 0";
      }

      $sql_area = '';
      // if ($this->input->get('areaId') > 0) {
      //   $sql_area .= " WHERE id_area = ".$this->input->get('areaId');
      // }

      $sql_rancho = '';
      // if(is_array($this->input->get('ranchoId'))){
      //   $sql_rancho .= " WHERE id_rancho IN (".implode(',', $this->input->get('ranchoId')).")";
      // }

	    $response = array();
    	$productos = $this->db->query("SELECT p.id_producto, p.nombre, pu.abreviatura, COALESCE(cp.cantidad, 0) AS cantidad,
            COALESCE(cp.importe, 0) AS importe, COALESCE(cp.impuestos, 0) AS impuestos, COALESCE(cp.total, 0) AS total,
            pf.nombre AS familia
        FROM productos AS p
          INNER JOIN productos_unidades AS pu ON p.id_unidad = pu.id_unidad
          INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
          LEFT JOIN (
            SELECT cp.id_producto, SUM(cp.cantidad) AS cantidad, SUM(cp.importe) AS importe,
              (SUM(cp.iva) - SUM(cp.retencion_iva)) AS impuestos, SUM(cp.total) AS total
            FROM compras AS c
              -- INNER JOIN compras_facturas AS cf ON c.id_compra = cf.id_compra
              INNER JOIN compras_productos AS cp ON c.id_compra = cp.id_compra
              -- INNER JOIN (
              --   SELECT id_orden, array_agg(id_area) AS id_areas
              --   FROM public.compras_ordenes_areas
              --   {$sql_area}
              --   GROUP BY id_orden
              -- ) AS coa ON coa.id_orden = cp.id_orden
              -- INNER JOIN (
              --   SELECT id_orden, array_agg(id_rancho) AS id_ranchos
              --   FROM public.compras_ordenes_rancho
              --   {$sql_rancho}
              --   GROUP BY id_orden
              -- ) AS cor ON cor.id_orden = cp.id_orden
            WHERE c.status <> 'ca' AND cp.id_producto IS NOT NULL {$sql} AND
              Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
            GROUP BY cp.id_producto
          ) AS cp ON p.id_producto = cp.id_producto
          {$idsproveedores}
        ORDER BY pf.nombre ASC, p.nombre ASC");
    	$response = $productos->result();

		return $response;
	}
	/**
	 * Reporte existencias por unidad pdf
	 */
	public function getCProductosPdf(){
		$res = $this->getCProductosData();

    $this->load->model('empresas_model');
    $this->load->model('areas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->heightHeader = 25;

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
		$pdf->titulo2 = 'Reporte de Compras por Producto';
		$pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    // if ($this->input->get('areaId') > 0) {
    //   $darea = $this->areas_model->getAreaInfo($this->input->get('areaId'), true);
    //   $pdf->titulo3 .= "Cultivo / Actividad / Producto: {$darea['info']->nombre} \n";
    // }
    // if (is_array($this->input->get('ranchoId')) && count($this->input->get('ranchoId')) > 0) {
    //   $pdf->titulo3 .= "Areas / Ranchos / Lineas: ".implode(',', $this->input->get('ranchoText'))." \n";
    // }

		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'R', 'C', 'R', 'R', 'R');
		$widths = array(65, 30, 20, 30, 30, 30);
		$header = array('Nombre (Producto, Servicio)', 'Cantidad', 'Unidad', 'Neto', 'Impuestos', 'Total');

		$familia = '';
		$proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
		foreach($res as $key => $producto){
      if ($familia != $producto->familia) {
        if ($key==0 || $pdf->GetY() >= $pdf->limiteY) {
          $pdf->AddPage();
        }

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(200,200,200);
        $pdf->SetX(6);
        $pdf->SetAligns(['L']);
        $pdf->SetWidths([205]);
        $pdf->Row([$producto->familia], true);
        $pdf->SetY($pdf->GetY()+2);
        $familia = $producto->familia;
      }

			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        if ($key > 0)
				  $pdf->AddPage();

				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
				$pdf->SetY($pdf->GetY()+2);
			}

			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',8);
			$datos = array($producto->nombre,
				MyString::formatoNumero($producto->cantidad, 2, '', false),
				$producto->abreviatura,
				MyString::formatoNumero($producto->importe, 2, '', false),
				MyString::formatoNumero($producto->impuestos, 2, '', false),
				MyString::formatoNumero(($producto->total), 2, '', false),
				);
			$pdf->SetXY(6, $pdf->GetY()-2);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->SetMyLinks(array( base_url('panel/inventario/cproducto_pdf?id_producto='.$producto->id_producto.'&'.MyString::getVarsLink(array('fproductor', 'ids_productos', 'familias'))) ));
			$pdf->Row($datos, false, false);

			$proveedor_cantidad  += $producto->cantidad;
			$proveedor_importe   += $producto->importe;
			$proveedor_impuestos += $producto->impuestos;
			$proveedor_total     += $producto->total;

		}
		$datos = array('Total General',
			MyString::formatoNumero($proveedor_cantidad, 2, '', false),
			'',
			MyString::formatoNumero($proveedor_importe, 2, '', false),
			MyString::formatoNumero($proveedor_impuestos, 2, '', false),
			MyString::formatoNumero(($proveedor_total), 2, '', false),
			);
		$pdf->SetXY(6, $pdf->GetY());
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->SetMyLinks(array());
		$pdf->Row($datos, false);

		$pdf->Output('compras_proveedor.pdf', 'I');
	}

  public function getCProductosXls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=compras_x_producto.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getCProductosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de Compras por Producto';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    // if ($this->input->get('areaId') > 0) {
    //   $titulo3 .= "Cultivo / Actividad / Producto: {$this->input->get('area')} \n";
    // }
    // if (is_array($this->input->get('ranchoId')) && count($this->input->get('ranchoId')) > 0) {
    //   $titulo3 .= "Areas / Ranchos / Lineas: ".implode(',', $this->input->get('ranchoText'))." \n";
    // }


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
      $html .= '<tr style="font-weight:bold">
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Nombre (Producto, Servicio)</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Neto</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Impuestos</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
      </tr>';
    $familia = '';
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $producto) {
      $html .= '<tr>
          <td style="width:400px;border:1px solid #000;">'.$producto->nombre.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->cantidad.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->abreviatura.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->importe.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->impuestos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->total.'</td>
        </tr>';

      $proveedor_cantidad  += $producto->cantidad;
      $proveedor_importe   += $producto->importe;
      $proveedor_impuestos += $producto->impuestos;
      $proveedor_total     += $producto->total;

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="2">TOTALES</td>
          <td style="border:1px solid #000;">'.$proveedor_cantidad.'</td>
          <td style="border:1px solid #000;">'.$proveedor_importe.'</td>
          <td style="border:1px solid #000;">'.$proveedor_impuestos.'</td>
          <td style="border:1px solid #000;">'.$proveedor_total.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

  /**
   * Reporte existencias por unidad
   * @return
   */
  public function getCUnProductosData()
  {
    $sql_suc = $sql_com = $sql = '';
    $idsproveedores = $idsproveedores2 = '' ;

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']{0}) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
      $idsproveedores = " WHERE p.id_empresa = '".$this->input->get('did_empresa')."'";

      if ($this->input->get('did_empresa') == 3) { // gomez gudiño
        $sql_com = " AND Date(cp.fecha_aceptacion) > '2015-04-30'";
        // $sql_sal .= " AND Date(sa.fecha_registro) > '2015-04-30'";
      }
    }

    if (intval($this->input->get('sucursalId')) > 0) {
      $sql_suc .= " AND co.id_sucursal = ".$this->input->get('sucursalId');
    }

      // if($this->input->get('fid_producto') != '')
      // {
      //   $idsproveedores .= " AND p.id_producto = ".$this->input->get('fid_producto');
      //   $idsproveedores2 .= " AND cp.id_producto = ".$this->input->get('fid_producto');
      // }else
      // {
      //   $idsproveedores .= " AND p.id_producto = 0";
      //   $idsproveedores2 .= " AND cp.id_producto = 0";
      // }

      $response = array();
      if (is_array($this->input->get('ids_productos')))
      {
        foreach ($this->input->get('ids_productos') as $key => $product)
        {
          $productos = $this->db->query("SELECT p.id_producto, cp.serie, cp.folio, cp.fecha, cp.precio_unitario, p.nombre,
                pu.abreviatura, COALESCE(cp.cantidad, 0) AS cantidad, COALESCE(cp.importe, 0) AS importe,
                COALESCE(cp.impuestos, 0) AS impuestos, COALESCE(cp.total, 0) AS total, cp.proveedor
            FROM
              productos AS p
              LEFT JOIN (
                SELECT cp.id_producto, c.serie, c.folio, Date(c.fecha) AS fecha, cp.cantidad, cp.importe,
                  (cp.iva - cp.retencion_iva) AS impuestos, cp.total, cp.precio_unitario, pr.nombre_fiscal AS proveedor
                FROM compras AS c
                  INNER JOIN compras_facturas AS cf ON c.id_compra = cf.id_compra
                  INNER JOIN (
                    SELECT cp.*
                    FROM compras_ordenes co
                      INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
                    WHERE cp.id_producto = {$product} {$sql_suc}
                  ) AS cp ON cf.id_orden = cp.id_orden
                  INNER JOIN proveedores AS pr ON pr.id_proveedor = c.id_proveedor
                WHERE cp.id_producto IS NOT NULL {$idsproveedores2} AND cp.id_producto = {$product} {$sql} AND
                  Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
                  AND c.status <> 'ca'
              ) AS cp ON p.id_producto = cp.id_producto
              INNER JOIN productos_unidades AS pu ON p.id_unidad = pu.id_unidad
              {$idsproveedores} AND p.id_producto = {$product}
            ORDER BY cp.fecha DESC, cp.folio ASC");
          $response[] = $productos->result();
        }
      }
      // $productos = $this->db->query("SELECT p.id_producto, cp.serie, cp.folio, cp.fecha, cp.precio_unitario, p.nombre,
      //       pu.abreviatura, COALESCE(cp.cantidad, 0) AS cantidad, COALESCE(cp.importe, 0) AS importe,
      //       COALESCE(cp.impuestos, 0) AS impuestos, COALESCE(cp.total, 0) AS total, cp.proveedor
      //   FROM
      //     productos AS p LEFT JOIN (
      //       SELECT cp.id_producto, c.serie, c.folio, Date(c.fecha) AS fecha, cp.cantidad, cp.importe,
      //         (cp.iva - cp.retencion_iva) AS impuestos, cp.total, cp.precio_unitario, pr.nombre_fiscal AS proveedor
      //       FROM compras AS c
      //         INNER JOIN compras_facturas AS cf ON c.id_compra = cf.id_compra
      //         INNER JOIN compras_productos AS cp ON cf.id_orden = cp.id_orden
      //         INNER JOIN proveedores AS pr ON pr.id_proveedor = c.id_proveedor
      //       WHERE cp.id_producto IS NOT NULL {$idsproveedores2} {$sql} AND
      //         Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
      //     ) AS cp ON p.id_producto = cp.id_producto
      //       INNER JOIN productos_unidades AS pu ON p.id_unidad = pu.id_unidad
      //     {$idsproveedores}
      //   ORDER BY cp.fecha DESC, cp.folio ASC");
      // $response = $productos->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getCUnProductosPdf(){
    $res = $this->getCUnProductosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    if ($this->input->get('sucursalId') > 0) {
      $sucursal = $this->empresas_model->infoSucursal($this->input->get('sucursalId'));
    }

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de seguimientos x Producto';
    // $pdf->titulo3 = (isset($res[0]->nombre)?'PRODUCTO: '.$res[0]->nombre:'')."\n";
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= isset($sucursal)? $sucursal->nombre_fiscal: '';
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R');
    $widths = array(18, 25, 65, 20, 20, 28, 30);
    $header = array('Fecha', 'Folio', 'Proveedor', 'Concepto', 'P. Unitario', 'Impuestos', 'Total');

    $familia = '';
    $total_general = 0;
    foreach($res as $key22 => $productos)
    {
      if(count($productos) > 0)
      {

        $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
        if($pdf->GetY() >= $pdf->limiteY || $key22==0)
        {
          $pdf->AddPage();

        }
          $datos = array(
            (isset($productos[0]->nombre)? $productos[0]->nombre: ''),
          );
          $pdf->SetFont('Arial','B',8);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L'));
          $pdf->SetWidths(array(200));
          $pdf->Row($datos, false, false);
        foreach($productos as $key => $producto)
        {
          if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
            if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

            $pdf->SetFont('Arial','B',8);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFillColor(160,160,160);
            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, true);
            $pdf->SetY($pdf->GetY()+2);
          }

          $pdf->SetTextColor(0,0,0);
          $pdf->SetFont('Arial','',8);
          $datos = array(MyString::fechaAT($producto->fecha),
            $producto->serie.' '.$producto->folio,
            $producto->proveedor,
            $producto->cantidad.' '.$producto->abreviatura,
            MyString::formatoNumero($producto->precio_unitario, 2, '', false),
            MyString::formatoNumero($producto->impuestos, 2, '', false),
            MyString::formatoNumero(($producto->total), 2, '', false),
            );
          $pdf->SetXY(6, $pdf->GetY()-2);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, false, false);

          $proveedor_cantidad  += $producto->cantidad;
          $proveedor_importe   += $producto->importe;
          $proveedor_impuestos += $producto->impuestos;
          $proveedor_total     += $producto->total;
          $total_general += $producto->total;

        }
        $pdf->SetFont('Arial','B',8);
        $datos = array('Total',
          MyString::formatoNumero(($proveedor_total), 2, '', false),
          );
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'R'));
        $pdf->SetWidths(array(170, 36));
        $pdf->Row($datos, false, false);
      }
    }
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total General',
      MyString::formatoNumero(($total_general), 2, '', false),
      );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(170, 36));
    $pdf->Row($datos, false, false);

    $pdf->Output('compras_proveedor.pdf', 'I');
  }
  public function getCUnProductosXls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=seguimiento_producto.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getCUnProductosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    if ($this->input->get('sucursalId') > 0) {
      $sucursal = $this->empresas_model->infoSucursal($this->input->get('sucursalId'));
    }

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de seguimientos x Producto';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $titulo3 .= isset($sucursal)? $sucursal->nombre_fiscal: '';


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="8" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="8" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="8" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="8"></td>
        </tr>';
      $html .= '<tr style="font-weight:bold">
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Proveedor</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">P. Unitario</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Impuestos</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
      </tr>';
    $familia = '';
    $total_general = 0;
    foreach($res as $key22 => $productos)
    {
      if(count($productos) > 0)
      {

        $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
        $html .= '<tr>
            <td colspan="6" style="font-weight:bold">'.(isset($productos[0]->nombre)? $productos[0]->nombre: '').'</td>
          </tr>';
        foreach($productos as $key => $producto)
        {
          $html .= '<tr>
              <td style="width:400px;border:1px solid #000;">'.$producto->fecha.'</td>
              <td style="width:150px;border:1px solid #000;">'.$producto->serie.' '.$producto->folio.'</td>
              <td style="width:150px;border:1px solid #000;">'.$producto->proveedor.'</td>
              <td style="width:150px;border:1px solid #000;">'.$producto->cantidad.'</td>
              <td style="width:150px;border:1px solid #000;">'.$producto->abreviatura.'</td>
              <td style="width:150px;border:1px solid #000;">'.$producto->precio_unitario.'</td>
              <td style="width:150px;border:1px solid #000;">'.$producto->impuestos.'</td>
              <td style="width:150px;border:1px solid #000;">'.$producto->total.'</td>
            </tr>';

          $proveedor_cantidad  += $producto->cantidad;
          $proveedor_importe   += $producto->importe;
          $proveedor_impuestos += $producto->impuestos;
          $proveedor_total     += $producto->total;
          $total_general += $producto->total;

        }
        $html .= '
          <tr style="font-weight:bold">
            <td colspan="7">Total</td>
            <td style="border:1px solid #000;">'.$proveedor_total.'</td>
          </tr>
          <tr>
            <td colspan="8"></td>
          </tr>';
      }
    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="7">Total General</td>
          <td style="border:1px solid #000;">'.$total_general.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

	/**
	 * Reporte existencias por unidad
	 *
	 * @return
	 */
	public function getCProductoData()
  {
		$sql = '';

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

		$this->load->model('empresas_model');
		$client_default = $this->empresas_model->getDefaultEmpresa();
		$_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
		$_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    $sql_area = '';
    // if ($this->input->get('areaId') > 0) {
    //   $sql_area .= " WHERE id_area = ".$this->input->get('areaId');
    // }

    $sql_rancho = '';
    // if (is_array($this->input->get('ranchoId')) && count($this->input->get('ranchoId')) > 0) {
    //   $sql_rancho .= " WHERE id_rancho IN (".implode(',', $this->input->get('ranchoId')).")";
    // }

    $idsproveedores = $this->input->get('id_producto');

    $response = array();
  	$productos = $this->db->query("SELECT p.id_producto, p.codigo, p.nombre, pu.abreviatura, COALESCE(Sum(cp.cantidad), 0) AS cantidad,
				COALESCE(Sum(cp.importe), 0) AS importe, COALESCE(Sum(cp.impuestos), 0) AS impuestos, COALESCE(Sum(cp.total), 0) AS total,
				cp.fecha, cp.serie, cp.folio, cp.fechao, cp.folioo, cp.id_compra, cp.id_orden
			FROM
				productos AS p LEFT JOIN (
					SELECT cp.id_producto, c.id_compra, Date(c.fecha) AS fecha, c.serie, c.folio, co.id_orden, Date(co.fecha_autorizacion) AS fechao, co.folio AS folioo,
						cp.cantidad, cp.importe, (cp.iva - cp.retencion_iva) AS impuestos, cp.total
					FROM compras AS c
						-- INNER JOIN compras_facturas AS cf ON c.id_compra = cf.id_compra
						INNER JOIN compras_productos AS cp ON c.id_compra = cp.id_compra
						INNER JOIN compras_ordenes AS co ON co.id_orden = cp.id_orden
            -- INNER JOIN (
            --     SELECT id_orden, array_agg(id_area) AS id_areas
            --     FROM public.compras_ordenes_areas
            --     {$sql_area}
            --     GROUP BY id_orden
            --   ) AS coa ON coa.id_orden = cp.id_orden
            --   INNER JOIN (
            --     SELECT id_orden, array_agg(id_rancho) AS id_ranchos
            --     FROM public.compras_ordenes_rancho
            --     {$sql_rancho}
            --     GROUP BY id_orden
            --   ) AS cor ON cor.id_orden = cp.id_orden
					WHERE c.status <> 'ca' AND cp.id_producto = {$idsproveedores} {$sql} AND
						Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
				) AS cp ON p.id_producto = cp.id_producto
				INNER JOIN productos_unidades AS pu ON p.id_unidad = pu.id_unidad
			WHERE p.id_producto = {$idsproveedores}
			GROUP BY p.id_producto, pu.abreviatura, cp.fecha, cp.serie, cp.folio, cp.fechao, cp.folioo, cp.id_compra, cp.id_orden
			ORDER BY p.nombre, folio ASC");

    	$response = $productos->result();

		return $response;
	}
	/**
	 * Reporte existencias por unidad pdf
	 */
	public function getCProductoPdf(){
		$res = $this->getCProductoData();

		$this->load->model('empresas_model');
		$empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		if ($empresa['info']->logo !== '')
		  $pdf->logo = $empresa['info']->logo;

		$pdf->titulo1 = $empresa['info']->nombre_fiscal;
		$pdf->titulo2 = 'Reporte de Compras por Producto';
		$pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    if ($this->input->get('areaId') > 0) {
      $pdf->titulo3 .= "Cultivo / Actividad / Producto: {$this->input->get('area')} \n";
    }
    if (is_array($this->input->get('ranchoId')) && count($this->input->get('ranchoId')) > 0) {
      $pdf->titulo3 .= "Areas / Ranchos / Lineas: ".implode(',', $this->input->get('ranchoText'))." \n";
    }

		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'C', 'R', 'L', 'R', 'R');
		$widths = array(25, 20, 25, 65, 35, 35);
		$header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Ordenado', 'Comprado');

		// var_dump($res);

		$familia = '';
		$proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
		foreach($res as $key => $producto){
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				$pdf->SetFont('Arial','B',8);
				if($key == 0){
					$pdf->SetX(6);
					$pdf->SetAligns(array('L', 'L'));
					$pdf->SetWidths(array(30, 100));
					$pdf->Row(array('Producto: ', $producto->codigo), false, false);
					$pdf->SetXY(6, $pdf->GetY()-2);
					$pdf->Row(array('Nombre: ', $producto->nombre), false, false);
				}

				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
				$pdf->SetY($pdf->GetY()+2);
			}

			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(6, $pdf->GetY()-2);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->SetMyLinks(array());
			$pdf->Row(array(MyString::fechaAT($producto->fechao),
				'',
				MyString::formatoNumero($producto->folioo, 0, '', false),
				'Orden de Compra',
				MyString::formatoNumero($producto->cantidad, 2, '', false),
				MyString::formatoNumero(0, 2, '', false),
				), false, false);

			$pdf->SetXY(6, $pdf->GetY()-2);
			$pdf->SetMyLinks(array('','','', base_url('panel/inventario/cseguimiento_pdf?id_compra='.$producto->id_compra.
							'&id_orden='.$producto->id_orden.'&'.MyString::getVarsLink(array('id_orden', 'id_compra'))) ));
			$pdf->Row(array(MyString::fechaAT($producto->fecha),
				$producto->serie,
				MyString::formatoNumero($producto->folio, 0, '', false),
				'Compra',
				MyString::formatoNumero(0, 2, '', false),
				MyString::formatoNumero($producto->cantidad, 2, '', false),
				), false, false);

			$proveedor_cantidad  += $producto->cantidad;

		}
		$datos = array('Total General',
			'', '', '',
			MyString::formatoNumero($proveedor_cantidad, 2, '', false),
			MyString::formatoNumero(($proveedor_cantidad), 2, '', false),
			);
		$pdf->SetXY(6, $pdf->GetY());
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->SetMyLinks(array());
		$pdf->Row($datos, false);

		$pdf->Output('compras_proveedor.pdf', 'I');
	}

	/**
	 * Reporte existencias por unidad
	 *
	 * @return
	 */
	public function getCSeguimientoData()
  {
		$sql = '';

		// //Filtros para buscar
		// $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		// $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		// $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

		// $this->load->model('empresas_model');
		// $client_default = $this->empresas_model->getDefaultEmpresa();
		// $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
		// $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
	 //    if($this->input->get('did_empresa') != ''){
	 //      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
	 //    }

	 //    $idsproveedores = $this->input->get('id_producto');

	    $this->load->model('compras_ordenes_model');
	    $this->load->model('compras_model');
	    $compra = $this->compras_model->getInfoCompra($_GET['id_compra'], true);
	    $orden = $this->compras_ordenes_model->info($_GET['id_orden'], true);
	    $response['orden'] = $orden['info'][0];
	    $response['compra'] = $compra['info'];

		return $response;
	}
	/**
	 * Reporte existencias por unidad pdf
	 */
	public function getCSeguimientoPdf(){
		$res = $this->getCSeguimientoData();

		$this->load->model('empresas_model');
		$empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');

		if ($empresa['info']->logo !== '')
		  $pdf->logo = $empresa['info']->logo;

		$pdf->titulo1 = $empresa['info']->nombre_fiscal;
		$pdf->titulo2 = 'Reporte Seguimiento de Operaciones de Compra';
		$pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);
		$pdf->AddPage();

		$aligns = array('L', 'L', 'R', 'L', 'L', 'L');
		$widths = array(25, 20, 25, 35, 70, 30);
		$header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Proveedor', 'Recepción');
		$pdf->SetFont('Arial','B',8);
		$pdf->SetX(6);
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->Row($header, false, false);
		$pdf->Line(6, $pdf->GetY(), 200, $pdf->GetY());

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',8);
		$pdf->SetXY(6, $pdf->GetY());
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->Row(array(
			MyString::fechaATexto(substr($res['compra']->fecha, 0, 10), '/c'),
			$res['compra']->serie,
			$res['compra']->folio,
			'Compra',
			$res['orden']->proveedor,
			MyString::fechaATexto(substr($res['orden']->fecha_aceptacion, 0, 10), '/c'),
			), false, false);

		$aligns = array('L', 'L', 'R', 'R', 'R');
		$widths = array(25, 65, 35, 35, 35);
		$header = array('Codigo', 'Nombre', 'Cantidad', 'Surtido', 'Total');
		$pdf->SetFont('Arial','B',8);
		$pdf->SetX(6);
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->Row($header, false, false);
		$pdf->Line(6, $pdf->GetY(), 200, $pdf->GetY());

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',8);

		$cantidad = $surtido = $total = 0;
		foreach ($res['orden']->productos as $key => $value)
		{
			if($_GET['id_producto'] == $value->id_producto){
				$pdf->SetXY(6, $pdf->GetY()-1);
				$pdf->Row(array(
					$value->codigo,
					$value->producto,
					MyString::formatoNumero($value->cantidad, 2, '', false),
					MyString::formatoNumero($value->cantidad, 2, '', false),
					MyString::formatoNumero($value->importe+$value->iva, 2, '', false),
					), false, false);
				$cantidad += $value->cantidad;
				$surtido += $value->cantidad;
				$total += $value->importe+$value->iva;
			}
		}

		$pdf->SetX(96);
		$pdf->SetAligns(array('R', 'R', 'R'));
		$pdf->SetWidths(array(35, 35, 35));

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',8);
		$pdf->Row(array(
					MyString::formatoNumero($cantidad, 2, '', false),
					MyString::formatoNumero($cantidad, 2, '', false),
					MyString::formatoNumero($total, 2, '', false),
					), false);
		$pdf->Text($pdf->GetX(), $pdf->GetY(), "Orde de Compra {$res['orden']->folio}");

		$pdf->Output('compras_proveedor.pdf', 'I');
	}




  /**
   * Reporte existencias por unidad
   *
   * @return
   */
  public function getCProductosOrdenData()
  {
    $sql = '';
    $idsproveedores = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];
    $tipoFecha = $this->input->get('tipo_fecha')? $this->input->get('tipo_fecha'): 'co.fecha_aceptacion';

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']{0}) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      if($this->input->get('did_empresa') != ''){
        $sql .= " AND co.id_empresa = '".$this->input->get('did_empresa')."'";
        $idsproveedores = " WHERE p.id_empresa = '".$this->input->get('did_empresa')."'";
      }

      if(is_array($this->input->get('ids_productos')))
        $idsproveedores .= " AND p.id_producto IN(".implode(',', $this->input->get('ids_productos')).")";

      if(is_array($this->input->get('familias'))){
        $idsproveedores .= " AND p.id_familia IN(".implode(',', $this->input->get('familias')).")";
      }

      if ($this->input->get('dcon_mov') == 'si') {
        $idsproveedores .= " AND COALESCE(cp.total, 0) > 0";
      }

      $sql_area = '';
      if ($this->input->get('areaId') > 0) {
        $sql_area = " INNER JOIN (
                SELECT id_orden, array_agg(id_area) AS id_areas
                FROM public.compras_ordenes_areas
                WHERE id_area = ".$this->input->get('areaId')."
                GROUP BY id_orden
              ) AS coa ON coa.id_orden = co.id_orden";
      }

      $sql_rancho = '';
      if(is_array($this->input->get('ranchoId'))){
        $sql_rancho .= " INNER JOIN (
                SELECT id_orden, array_agg(id_rancho) AS id_ranchos
                FROM public.compras_ordenes_rancho
                WHERE id_rancho IN (".implode(',', $this->input->get('ranchoId')).")
                GROUP BY id_orden
              ) AS cor ON cor.id_orden = co.id_orden";
      }

      $response = array();
      $productos = $this->db->query("SELECT p.id_producto, p.nombre, pu.abreviatura,
            (COALESCE(cp.cantidad, 0) - COALESCE(pnc.cantidad, 0)) AS cantidad,
            (COALESCE(cp.importe, 0) - COALESCE(pnc.importe, 0)) AS importe,
            (COALESCE(cp.impuestos, 0) - COALESCE(pnc.impuestos, 0)) AS impuestos,
            (COALESCE(cp.total, 0) - COALESCE(pnc.total, 0)) AS total,
            pf.nombre AS familia, cp.codigo_area, cp.centros_costos
        FROM productos AS p
          INNER JOIN productos_unidades AS pu ON p.id_unidad = pu.id_unidad
          INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
          LEFT JOIN (
            SELECT cp.id_producto, SUM(cp.cantidad) AS cantidad, SUM(cp.importe) AS importe,
              SUM(cp.impuestos) AS impuestos, SUM(cp.total) AS total,
              String_agg(Distinct(cc.codigo_area), ' | ') AS codigo_area,
              String_agg(Distinct(coc.centros_costos), ' | ') AS centros_costos
            FROM compras_ordenes AS co
              -- INNER JOIN compras_productos AS cp ON co.id_orden = cp.id_orden
              INNER JOIN (
                SELECT pc.id_producto, pc.id_compra, pc.id_orden,
                  (Sum(pc.cantidad)) AS cantidad,
                  (Sum(pc.importe)) AS importe,
                  (Sum(pc.impuestos)) AS impuestos,
                  (Sum(pc.total)) AS total
                FROM (
                    SELECT cp.id_compra, cp.id_producto, cp.id_orden, cp.cantidad, cp.importe, (cp.iva - cp.retencion_iva) AS impuestos, cp.total
                    FROM compras_ordenes c
                      INNER JOIN compras_productos AS cp ON c.id_orden = cp.id_orden
                    WHERE c.status <> 'ca' AND cp.id_producto IS NOT NULL
                  ) AS pc
                GROUP BY pc.id_producto, pc.id_compra, pc.id_orden
              ) AS cp ON co.id_orden = cp.id_orden
              INNER JOIN compras c ON c.id_compra = cp.id_compra
              {$sql_area}
              {$sql_rancho}
              LEFT JOIN (
                SELECT cp.id_producto, String_agg(Distinct(cc.nombre), ' | ') AS codigo_area
                FROM compras_productos cp
                  INNER JOIN otros.cat_codigos cc ON cc.id_cat_codigos = cp.id_cat_codigos
                GROUP BY cp.id_producto
              ) cc ON cc.id_producto = cp.id_producto
              LEFT JOIN (
                SELECT cp.id_producto, String_agg(Distinct(ccc.nombre), ' | ') AS centros_costos
                FROM otros.centro_costo ccc
                  INNER JOIN compras_ordenes_centro_costo coc ON ccc.id_centro_costo = coc.id_centro_costo
                  INNER JOIN compras_ordenes co ON co.id_orden = coc.id_orden
                  INNER JOIN compras_productos AS cp ON co.id_orden = cp.id_orden
                GROUP BY cp.id_producto
              ) coc ON coc.id_producto = cp.id_producto
            WHERE co.status = 'f' AND cp.id_producto IS NOT NULL {$sql} AND
              Date({$tipoFecha}) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
            GROUP BY cp.id_producto
          ) AS cp ON p.id_producto = cp.id_producto
          LEFT JOIN (
            SELECT ncp.id_producto, Sum(ncp.cantidad) AS cantidad, Sum(ncp.importe) AS importe,
              Sum(ncp.iva - ncp.retencion_iva) AS impuestos, Sum(ncp.total) AS total
            FROM compras c
              INNER JOIN compras_notas_credito_productos ncp ON c.id_compra = ncp.id_compra
            WHERE c.tipo = 'nc' AND c.status <> 'ca' AND c.id_empresa = '{$_GET['did_empresa']}' AND
              Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
            GROUP BY ncp.id_producto
          ) AS pnc ON p.id_producto = pnc.id_producto
        {$idsproveedores}
        ORDER BY pf.nombre ASC, p.nombre ASC");
      $response = $productos->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getCProductosOrdenPdf(){
    $res = $this->getCProductosOrdenData();

    $this->load->model('empresas_model');
    $this->load->model('areas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->heightHeader = 25;

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte de Compras por Producto de Ordenes';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    if ($this->input->get('areaId') > 0) {
      $darea = $this->areas_model->getAreaInfo($this->input->get('areaId'), true);
      $pdf->titulo3 .= "Cultivo / Actividad / Producto: {$darea['info']->nombre} \n";
    }
    if (is_array($this->input->get('ranchoId')) && count($this->input->get('ranchoId')) > 0) {
      $pdf->titulo3 .= "Areas / Ranchos / Lineas: ".implode(',', $this->input->get('ranchoText'))." \n";
    }

    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'C', 'R', 'R', 'R', 'L', 'L');
    $widths = array(65, 30, 20, 30, 30, 30);
    $header = array('Nombre (Producto, Servicio)', 'Cantidad', 'Unidad', 'Neto', 'Impuestos', 'Total');

    $familia = '';
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $producto){
      if ($familia != $producto->familia) {
        if ($key==0 || $pdf->GetY() >= $pdf->limiteY) {
          $pdf->AddPage();
        }

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(200,200,200);
        $pdf->SetX(6);
        $pdf->SetAligns(['L']);
        $pdf->SetWidths([205]);
        $pdf->Row([$producto->familia], true);
        $pdf->SetY($pdf->GetY()+2);
        $familia = $producto->familia;
      }

      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        if ($key > 0)
          $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $datos = array($producto->nombre,
        MyString::formatoNumero($producto->cantidad, 2, '', false),
        $producto->abreviatura,
        MyString::formatoNumero($producto->importe, 2, '', false),
        MyString::formatoNumero($producto->impuestos, 2, '', false),
        MyString::formatoNumero(($producto->total), 2, '', false),
        // $producto->centros_costos,
        // $producto->codigo_area,
        );
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->SetMyLinks(array('', base_url('panel/inventario/cproductoOrden_pdf?id_producto='.$producto->id_producto.'&'.MyString::getVarsLink(array('fproductor', 'ids_productos', 'familias'))) ));
      $pdf->Row($datos, false, false);

      $proveedor_cantidad  += $producto->cantidad;
      $proveedor_importe   += $producto->importe;
      $proveedor_impuestos += $producto->impuestos;
      $proveedor_total     += $producto->total;

    }
    $datos = array('Total General',
      MyString::formatoNumero($proveedor_cantidad, 2, '', false),
      '',
      MyString::formatoNumero($proveedor_importe, 2, '', false),
      MyString::formatoNumero($proveedor_impuestos, 2, '', false),
      MyString::formatoNumero(($proveedor_total), 2, '', false),
      );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetMyLinks(array());
    $pdf->Row($datos, false);

    $pdf->Output('compras_proveedor.pdf', 'I');
  }

  public function getCProductosOrdenXls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=compras_x_producto.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getCProductosOrdenData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de Compras por Producto de Ordenes';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    if ($this->input->get('areaId') > 0) {
      $titulo3 .= "Cultivo / Actividad / Producto: {$this->input->get('area')} \n";
    }
    if (is_array($this->input->get('ranchoId')) && count($this->input->get('ranchoId')) > 0) {
      $titulo3 .= "Areas / Ranchos / Lineas: ".implode(',', $this->input->get('ranchoText'))." \n";
    }


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
      $html .= '<tr style="font-weight:bold">
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Nombre (Producto, Servicio)</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Neto</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Impuestos</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">C. Costo</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">C. Area</td>
      </tr>';
    $familia = '';
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $producto) {
      $html .= '<tr>
          <td style="width:400px;border:1px solid #000;">'.$producto->nombre.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->cantidad.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->abreviatura.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->importe.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->impuestos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->total.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->centros_costos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->codigo_area.'</td>
        </tr>';

      $proveedor_cantidad  += $producto->cantidad;
      $proveedor_importe   += $producto->importe;
      $proveedor_impuestos += $producto->impuestos;
      $proveedor_total     += $producto->total;

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="2">TOTALES</td>
          <td style="border:1px solid #000;">'.$proveedor_cantidad.'</td>
          <td style="border:1px solid #000;">'.$proveedor_importe.'</td>
          <td style="border:1px solid #000;">'.$proveedor_impuestos.'</td>
          <td style="border:1px solid #000;">'.$proveedor_total.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

  /**
   * Reporte existencias por unidad
   *
   * @return
   */
  public function getCProductoOrdenData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];
    $tipoFecha = $this->input->get('tipo_fecha')? $this->input->get('tipo_fecha'): 'co.fecha_aceptacion';

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    $sql_area = '';
    if ($this->input->get('areaId') > 0) {
      $sql_area .= " WHERE id_area = ".$this->input->get('areaId');
    }

    $sql_rancho = '';
    if (is_array($this->input->get('ranchoId')) && count($this->input->get('ranchoId')) > 0) {
      $sql_rancho .= " WHERE id_rancho IN (".implode(',', $this->input->get('ranchoId')).")";
    }

    $idsproveedores = $this->input->get('id_producto');

    $response = array();
    $productos = $this->db->query("SELECT p.id_producto, p.codigo, p.nombre, pu.abreviatura, COALESCE(Sum(cp.cantidad), 0) AS cantidad,
        COALESCE(Sum(cp.importe), 0) AS importe, COALESCE(Sum(cp.impuestos), 0) AS impuestos, COALESCE(Sum(cp.total), 0) AS total,
        cp.fecha, cp.serie, cp.folio, cp.fechao, cp.folioo, cp.id_compra, cp.id_orden
      FROM productos AS p
        LEFT JOIN (
          SELECT cp.id_producto, c.id_compra, Date(c.fecha) AS fecha, c.serie, c.folio, co.id_orden,
            Date(co.fecha_aceptacion) AS fechao, co.folio AS folioo,
            cp.cantidad, cp.importe, cp.impuestos, cp.total
          FROM compras AS c
            -- INNER JOIN compras_productos AS cp ON c.id_compra = cp.id_compra
            INNER JOIN (
              SELECT pc.id_producto, pc.id_compra, pc.id_orden,
                Sum(pc.cantidad) AS cantidad,
                Sum(pc.importe) AS importe,
                Sum(pc.impuestos) AS impuestos,
                Sum(pc.total) AS total
              FROM (
                  SELECT c.id_compra, cp.id_producto, cp.id_orden, cp.cantidad, cp.importe, (cp.iva - cp.retencion_iva) AS impuestos, cp.total
                  FROM compras c
                    INNER JOIN compras_productos AS cp ON c.id_compra = cp.id_compra
                  WHERE c.tipo = 'c' AND c.status <> 'ca' AND cp.id_producto IS NOT NULL
                ) AS pc
                LEFT JOIN (
                  SELECT c.id_nc AS id_compra, ncp.id_producto, ncp.cantidad, ncp.importe, (ncp.iva - ncp.retencion_iva) AS impuestos, ncp.total
                  FROM compras c
                    INNER JOIN compras_notas_credito_productos ncp ON c.id_compra = ncp.id_compra
                  WHERE c.tipo = 'nc' AND c.status <> 'ca'
                ) AS pnc ON (pc.id_compra = pnc.id_compra AND pc.id_producto = pnc.id_producto)
              GROUP BY pc.id_producto, pc.id_compra, pc.id_orden
            ) AS cp ON c.id_compra = cp.id_compra
            INNER JOIN compras_ordenes AS co ON cp.id_orden = co.id_orden
            LEFT JOIN (
              SELECT id_orden, array_agg(id_area) AS id_areas
              FROM public.compras_ordenes_areas
              {$sql_area}
              GROUP BY id_orden
            ) AS coa ON coa.id_orden = co.id_orden
            LEFT JOIN (
              SELECT id_orden, array_agg(id_rancho) AS id_ranchos
              FROM public.compras_ordenes_rancho
              {$sql_rancho}
              GROUP BY id_orden
            ) AS cor ON cor.id_orden = co.id_orden
          WHERE c.status <> 'ca' AND c.tipo = 'c' AND cp.id_producto = {$idsproveedores} {$sql} AND
            Date({$tipoFecha}) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'

          UNION ALL

          SELECT ncp.id_producto, c.id_nc AS id_compra, Date(c.fecha) AS fecha, c.serie, c.folio,
            null AS id_orden, null AS fechao, null AS folioo,
            (ncp.cantidad*-1) AS cantidad, (ncp.importe*-1) AS importe, (ncp.iva - ncp.retencion_iva)*-1 AS impuestos,
            (ncp.total*-1) AS total
          FROM compras c
            INNER JOIN compras_notas_credito_productos ncp ON c.id_compra = ncp.id_compra
          WHERE c.tipo = 'nc' AND c.status <> 'ca' AND ncp.id_producto = {$idsproveedores} AND
            c.id_empresa = '{$_GET['did_empresa']}' AND
            Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
        ) AS cp ON p.id_producto = cp.id_producto
        INNER JOIN productos_unidades AS pu ON p.id_unidad = pu.id_unidad
      WHERE p.id_producto = {$idsproveedores}
      GROUP BY p.id_producto, pu.abreviatura, cp.fecha, cp.serie, cp.folio, cp.fechao, cp.folioo, cp.id_compra, cp.id_orden
      ORDER BY p.nombre, folio ASC");

    $response = $productos->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getCProductoOrdenPdf(){
    $res = $this->getCProductoOrdenData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte de Compras por Producto';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    if ($this->input->get('areaId') > 0) {
      $pdf->titulo3 .= "Cultivo / Actividad / Producto: {$this->input->get('area')} \n";
    }
    if (is_array($this->input->get('ranchoId')) && count($this->input->get('ranchoId')) > 0) {
      $pdf->titulo3 .= "Areas / Ranchos / Lineas: ".implode(',', $this->input->get('ranchoText'))." \n";
    }

    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'C', 'R', 'L', 'R', 'R');
    $widths = array(25, 20, 25, 65, 35, 35);
    $header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Ordenado', 'Comprado');

    // var_dump($res);

    $familia = '';
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $producto){
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        if($key == 0){
          $pdf->SetX(6);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(30, 100));
          $pdf->Row(array('Producto: ', $producto->codigo), false, false);
          $pdf->SetXY(6, $pdf->GetY()-2);
          $pdf->Row(array('Nombre: ', $producto->nombre), false, false);
        }

        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->SetMyLinks(array());
      $pdf->Row(array(MyString::fechaAT($producto->fechao),
        '',
        MyString::formatoNumero($producto->folioo, 0, '', false),
        'Orden de Compra',
        MyString::formatoNumero($producto->cantidad, 2, '', false),
        MyString::formatoNumero(0, 2, '', false),
        ), false, false);

      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetMyLinks(array('','','', base_url('panel/inventario/cseguimiento_pdf?id_compra='.$producto->id_compra.
              '&id_orden='.$producto->id_orden.'&'.MyString::getVarsLink(array('id_orden', 'id_compra'))) ));
      $pdf->Row(array(MyString::fechaAT($producto->fecha),
        $producto->serie,
        MyString::formatoNumero($producto->folio, 0, '', false),
        'Compra',
        MyString::formatoNumero(0, 2, '', false),
        MyString::formatoNumero($producto->cantidad, 2, '', false),
        ), false, false);

      $proveedor_cantidad  += $producto->cantidad;

    }
    $datos = array('Total General',
      '', '', '',
      MyString::formatoNumero($proveedor_cantidad, 2, '', false),
      MyString::formatoNumero(($proveedor_cantidad), 2, '', false),
      );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetMyLinks(array());
    $pdf->Row($datos, false);

    $pdf->Output('compras_proveedor.pdf', 'I');
  }

  /**
   * Reporte existencias por unidad
   *
   * @return
   */
  public function getCSeguimientoOrdenData()
  {
    $sql = '';

    // //Filtros para buscar
    // $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    // $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    // $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    // $this->load->model('empresas_model');
    // $client_default = $this->empresas_model->getDefaultEmpresa();
    // $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    // $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
     //    if($this->input->get('did_empresa') != ''){
     //      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
     //    }

     //    $idsproveedores = $this->input->get('id_producto');

      $this->load->model('compras_ordenes_model');
      $this->load->model('compras_model');
      $compra = $this->compras_model->getInfoCompra($_GET['id_compra'], true);
      $orden = $this->compras_ordenes_model->info($_GET['id_orden'], true);
      $response['orden'] = $orden['info'][0];
      $response['compra'] = $compra['info'];

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getCSeguimientoOrdenPdf(){
    $res = $this->getCSeguimientoData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte Seguimiento de Operaciones de Compra';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);
    $pdf->AddPage();

    $aligns = array('L', 'L', 'R', 'L', 'L', 'L');
    $widths = array(25, 20, 25, 35, 70, 30);
    $header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Proveedor', 'Recepción');
    $pdf->SetFont('Arial','B',8);
    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row($header, false, false);
    $pdf->Line(6, $pdf->GetY(), 200, $pdf->GetY());

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row(array(
      MyString::fechaATexto(substr($res['compra']->fecha, 0, 10), '/c'),
      $res['compra']->serie,
      $res['compra']->folio,
      'Compra',
      $res['orden']->proveedor,
      MyString::fechaATexto(substr($res['orden']->fecha_aceptacion, 0, 10), '/c'),
      ), false, false);

    $aligns = array('L', 'L', 'R', 'R', 'R');
    $widths = array(25, 65, 35, 35, 35);
    $header = array('Codigo', 'Nombre', 'Cantidad', 'Surtido', 'Total');
    $pdf->SetFont('Arial','B',8);
    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row($header, false, false);
    $pdf->Line(6, $pdf->GetY(), 200, $pdf->GetY());

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',8);

    $cantidad = $surtido = $total = 0;
    foreach ($res['orden']->productos as $key => $value)
    {
      if($_GET['id_producto'] == $value->id_producto){
        $pdf->SetXY(6, $pdf->GetY()-1);
        $pdf->Row(array(
          $value->codigo,
          $value->producto,
          MyString::formatoNumero($value->cantidad, 2, '', false),
          MyString::formatoNumero($value->cantidad, 2, '', false),
          MyString::formatoNumero($value->importe+$value->iva, 2, '', false),
          ), false, false);
        $cantidad += $value->cantidad;
        $surtido += $value->cantidad;
        $total += $value->importe+$value->iva;
      }
    }

    $pdf->SetX(96);
    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetWidths(array(35, 35, 35));

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',8);
    $pdf->Row(array(
          MyString::formatoNumero($cantidad, 2, '', false),
          MyString::formatoNumero($cantidad, 2, '', false),
          MyString::formatoNumero($total, 2, '', false),
          ), false);
    $pdf->Text($pdf->GetX(), $pdf->GetY(), "Orde de Compra {$res['orden']->folio}");

    $pdf->Output('compras_proveedor.pdf', 'I');
  }


	/*-------------------------------------------
	 * --------- Reportes de inventario
	 -------------------------------------------*/

	/**
	 * Reporte existencias por unidad
   *
   * @return
	 */
	public function getEPUData($id_producto=null, $id_almacen=null, $con_req=false, $extras = [])
  {
		$sql_com = $sql_sal = $sql_req = $sql = '';

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

		if(is_array($this->input->get('ffamilias'))){
			$sql .= " AND pf.id_familia IN (".implode(',', $this->input->get('ffamilias')).")";
		}

    if($this->input->get('fid_producto') != '' || $id_producto > 0){
      $id_producto = $id_producto>0? $id_producto: $this->input->get('fid_producto');
      $sql .= " AND p.id_producto = ".$id_producto;
      $res_prod = $this->db->query("SELECT id_empresa FROM productos WHERE id_producto = {$id_producto}")->row();
      $_GET['did_empresa'] = $res_prod->id_empresa;
    }

    if (!isset($extras['empresa'])) {
      $this->load->model('empresas_model');
      $client_default = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
      $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      if($this->input->get('did_empresa') != ''){
        $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
      }
    } elseif (isset($extras['empresa'])) {
      $sql .= " AND p.id_empresa = '{$extras['empresa']}'";
    }

    if ($this->input->get('did_empresa') == 3) { // gomez gudiño
      $sql_com .= " AND Date(cp.fecha_aceptacion) > '2015-04-30'";
      $sql_sal .= " AND Date(sa.fecha_registro) > '2015-04-30'";
    }

    $id_almacen = $id_almacen>0? $id_almacen: $this->input->get('did_almacen');
    if ($id_almacen > 0) {
      $sql_com .= " AND co.id_almacen = ".$id_almacen;
      $sql_sal .= " AND sa.id_almacen = ".$id_almacen;
      $sql_req .= " AND cr.id_almacen = ".$id_almacen;
    }

    $sql_con_req = '';
    $sql_con_req_f = '';
    if ($con_req) { // toma en cuenta la existencia de las requisición pendientes
      $sql_con_req_f = ', COALESCE(con_req.cantidad, 0) AS con_req';
      $sql_con_req = "LEFT JOIN
      (
        SELECT crq.id_producto, Sum(crq.cantidad) AS cantidad
        FROM compras_requisicion cr
          INNER JOIN compras_requisicion_productos crq ON cr.id_requisicion = crq.id_requisicion
        WHERE cr.status = 'p' AND cr.tipo_orden = 'p' AND cr.autorizado = 'f' AND cr.id_autorizo IS NULL
          AND cr.es_receta = 't' AND crq.importe > 0
          {$sql_req}
        GROUP BY crq.id_producto
      ) AS con_req ON con_req.id_producto = p.id_producto";
    }

		$res = $this->db->query(
			"SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura,
        COALESCE(co.cantidad, 0) AS entradas, COALESCE(sa.cantidad, 0) AS salidas,
				(COALESCE(sal_co.cantidad, 0) - COALESCE(sal_sa.cantidad, 0)) AS saldo_anterior, p.stock_min
        {$sql_con_req_f}
			FROM productos AS p
			INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
			INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
			LEFT JOIN
			(
				SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
				FROM compras_ordenes AS co
				  INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
				WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
          AND Date(cp.fecha_aceptacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql_com} AND co.id_orden_aplico IS NULL
				GROUP BY cp.id_producto
			) AS co ON co.id_producto = p.id_producto
			LEFT JOIN
			(
				SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
				FROM compras_salidas AS sa
				INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
				WHERE sa.status <> 'ca' AND sp.tipo_orden = 'p'
          AND Date(sa.fecha_registro) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql_sal}
				GROUP BY sp.id_producto
			) AS sa ON sa.id_producto = p.id_producto
			LEFT JOIN
			(
				SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
				FROM compras_ordenes AS co
				  INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
				WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
          AND Date(cp.fecha_aceptacion) < '{$fecha}'
          {$sql_com} AND co.id_orden_aplico IS NULL
				GROUP BY cp.id_producto
			) AS sal_co ON sal_co.id_producto = p.id_producto
			LEFT JOIN
			(
				SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
				FROM compras_salidas AS sa
				INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
				WHERE sa.status <> 'ca' AND sp.tipo_orden = 'p'
          AND Date(sa.fecha_registro) < '{$fecha}'
          {$sql_sal}
				GROUP BY sp.id_producto
			) AS sal_sa ON sal_sa.id_producto = p.id_producto
      {$sql_con_req}
			WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
			ORDER BY nombre, nombre_producto ASC
			");

		$response = array();
		if($res->num_rows() > 0)
			$response = $res->result();

		return $response;
	}

	/**
	 * Reporte existencias por unidad pdf
	 */
	public function getEPUPdf(){
		$res = $this->getEPUData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');

	    if ($empresa['info']->logo !== '')
	      $pdf->logo = $empresa['info']->logo;

	  $pdf->titulo1 = $empresa['info']->nombre_fiscal;
		$pdf->titulo2 = 'Existencia por unidades';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
		$pdf->titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'R', 'R', 'R', 'R');
		$widths = array(80, 25, 25, 25, 25, 25);
		$header = array('Producto', 'Saldo', 'Entradas', 'Salidas', 'E. Teórica', 'E. Real');

		$familia = '';
		$totales = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
		$total_cargos = $total_abonos = $total_saldo = 0;
		foreach($res as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				if ($key == 0)
				{
					$pdf->SetFont('Arial','B',11);
					$pdf->SetX(6);
					$pdf->SetAligns($aligns);
					$pdf->SetWidths(array(150));
					$pdf->Row(array($item->nombre), false, false);
					$familia = $item->nombre;
				}

				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}

			if ($familia <> $item->nombre)
			{
				if($key > 0){
					$pdf->SetFont('Arial','B',8);
					$pdf->SetX(6);
					$pdf->SetAligns($aligns);
					$pdf->SetWidths($widths);
					$pdf->Row(array('',
						MyString::formatoNumero($totales['familia'][0], 2, '', false),
						MyString::formatoNumero($totales['familia'][1], 2, '', false),
						MyString::formatoNumero($totales['familia'][2], 2, '', false),
						MyString::formatoNumero($totales['familia'][3], 2, '', false),
						), true, false);
				}
				$totales['familia'] = array(0,0,0,0);

				$pdf->SetFont('Arial','B',11);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths(array(150));
				$pdf->Row(array($item->nombre), false, false);
				$familia = $item->nombre;
			}

			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);

			$imprimir = true;
			$existencia = $item->saldo_anterior+$item->entradas-$item->salidas;
			if($this->input->get('con_existencia') == 'si')
				if($existencia <= 0)
					$imprimir = false;
			if($this->input->get('con_movimiento') == 'si')
				if($item->entradas <= 0 && $item->salidas <= 0)
					$imprimir = false;


			if($imprimir)
			{
				$totales['familia'][0] += $item->saldo_anterior;
				$totales['familia'][1] += $item->entradas;
				$totales['familia'][2] += $item->salidas;
				$totales['familia'][3] += $existencia;

				$totales['general'][0] += $item->saldo_anterior;
				$totales['general'][1] += $item->entradas;
				$totales['general'][2] += $item->salidas;
				$totales['general'][3] += $existencia;

				$datos = array($item->nombre_producto.' ('.$item->abreviatura.')',
					MyString::formatoNumero($item->saldo_anterior, 2, '', false),
					MyString::formatoNumero($item->entradas, 2, '', false),
					MyString::formatoNumero($item->salidas, 2, '', false),
          MyString::formatoNumero($existencia, 2, '', false),
					'',
					);

				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($datos, false);
			}
		}

		$pdf->SetFont('Arial','B',8);
		$pdf->SetX(6);
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->Row(array('',
			MyString::formatoNumero($totales['familia'][0], 2, '', false),
			MyString::formatoNumero($totales['familia'][1], 2, '', false),
			MyString::formatoNumero($totales['familia'][2], 2, '', false),
			MyString::formatoNumero($totales['familia'][3], 2, '', false),
			), true, false);

		$pdf->SetXY(6, $pdf->GetY()+5);
		$pdf->Row(array('GENERAL',
			MyString::formatoNumero($totales['general'][0], 2, '', false),
			MyString::formatoNumero($totales['general'][1], 2, '', false),
			MyString::formatoNumero($totales['general'][2], 2, '', false),
			MyString::formatoNumero($totales['general'][3], 2, '', false),
			), false, true);

		$pdf->Output('epu.pdf', 'I');
	}

	public function getEPUXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=epu.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

		$res = $this->getEPUData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Existencia por unidades';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">
          <td style="width:30px;border:1px solid #000;background-color: #cccccc;"></td>
          <td style="width:300px;border:1px solid #000;background-color: #cccccc;">Producto</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Saldo</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Entradas</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Salidas</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Existencia</td>
        </tr>';

    $familia = '';
    $totales = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($key==0){

        if ($key == 0)
        {
          $familia = $item->nombre;
          $html .= '<tr>
              <td colspan="6" style="font-size:16px;border:1px solid #000;">'.$familia.'</td>
            </tr>';
        }
      }

      if ($familia <> $item->nombre)
      {
        if($key > 0){
          $html .= '
            <tr style="font-weight:bold">
              <td></td>
              <td style="border:1px solid #000;">TOTALES</td>
              <td style="border:1px solid #000;">'.$totales['familia'][0].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][1].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][2].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][3].'</td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>';
        }
        $totales['familia'] = array(0,0,0,0);

        $familia = $item->nombre;
        $html .= '<tr>
              <td colspan="6" style="font-size:16px;border:1px solid #000;">'.$familia.'</td>
            </tr>';
      }

      $imprimir = true;
      $existencia = $item->saldo_anterior+$item->entradas-$item->salidas;
      if($this->input->get('con_existencia') == 'si')
        if($existencia <= 0)
          $imprimir = false;
      if($this->input->get('con_movimiento') == 'si')
        if($item->entradas <= 0 && $item->salidas <= 0)
          $imprimir = false;


      if($imprimir)
      {
        $totales['familia'][0] += $item->saldo_anterior;
        $totales['familia'][1] += $item->entradas;
        $totales['familia'][2] += $item->salidas;
        $totales['familia'][3] += $existencia;

        $totales['general'][0] += $item->saldo_anterior;
        $totales['general'][1] += $item->entradas;
        $totales['general'][2] += $item->salidas;
        $totales['general'][3] += $existencia;

        $html .= '<tr>
              <td style="width:30px;border:1px solid #000;"></td>
              <td style="width:300px;border:1px solid #000;">'.$item->nombre_producto.' ('.$item->abreviatura.')'.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->saldo_anterior.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->entradas.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->salidas.'</td>
              <td style="width:200px;border:1px solid #000;">'.$existencia.'</td>
            </tr>';
      }
    }

    $html .= '
            <tr style="font-weight:bold">
              <td></td>
              <td style="border:1px solid #000;">TOTALES</td>
              <td style="border:1px solid #000;">'.$totales['familia'][0].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][1].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][2].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][3].'</td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>
            <tr style="font-weight:bold">
              <td></td>
              <td style="border:1px solid #000;">GENERAL</td>
              <td style="border:1px solid #000;">'.$totales['general'][0].'</td>
              <td style="border:1px solid #000;">'.$totales['general'][1].'</td>
              <td style="border:1px solid #000;">'.$totales['general'][2].'</td>
              <td style="border:1px solid #000;">'.$totales['general'][3].'</td>
            </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
	}

  /**
   * Reporte existencias por unidad formato
   *
   * @return
   */
  public function getFormatoInvData($id_producto=null, $id_almacen=null, $con_req=false, $extras = [])
  {
    $sql_com = $sql_sal = $sql_req = $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    if(is_array($this->input->get('ffamilias'))){
      $sql .= " AND pf.id_familia IN (".implode(',', $this->input->get('ffamilias')).")";
    }

    if($this->input->get('fid_producto') != '' || $id_producto > 0){
      $id_producto = $id_producto>0? $id_producto: $this->input->get('fid_producto');
      $sql .= " AND p.id_producto = ".$id_producto;
      $res_prod = $this->db->query("SELECT id_empresa FROM productos WHERE id_producto = {$id_producto}")->row();
      $_GET['did_empresa'] = $res_prod->id_empresa;
    }

    if (!isset($extras['empresa'])) {
      $this->load->model('empresas_model');
      $client_default = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
      $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      if($this->input->get('did_empresa') != ''){
        $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
      }
    } elseif (isset($extras['empresa'])) {
      $sql .= " AND p.id_empresa = '{$extras['empresa']}'";
    }

    if ($this->input->get('did_empresa') == 3) { // gomez gudiño
      $sql_com .= " AND Date(cp.fecha_aceptacion) > '2015-04-30'";
      $sql_sal .= " AND Date(sa.fecha_registro) > '2015-04-30'";
    }

    $id_almacen = $id_almacen>0? $id_almacen: $this->input->get('did_almacen');
    if ($id_almacen > 0) {
      $sql_com .= " AND co.id_almacen = ".$id_almacen;
      $sql_sal .= " AND sa.id_almacen = ".$id_almacen;
      $sql_req .= " AND cr.id_almacen = ".$id_almacen;
    }

    $sql_con_req = '';
    $sql_con_req_f = '';
    if ($con_req) { // toma en cuenta la existencia de las requisición pendientes
      $sql_con_req_f = ', COALESCE(con_req.cantidad, 0) AS con_req';
      $sql_con_req = "LEFT JOIN
      (
        SELECT crq.id_producto, Sum(crq.cantidad) AS cantidad
        FROM compras_requisicion cr
          INNER JOIN compras_requisicion_productos crq ON cr.id_requisicion = crq.id_requisicion
        WHERE cr.status = 'p' AND cr.tipo_orden = 'p' AND cr.autorizado = 'f' AND cr.id_autorizo IS NULL
          AND cr.es_receta = 't' AND crq.importe > 0
          {$sql_req}
        GROUP BY crq.id_producto
      ) AS con_req ON con_req.id_producto = p.id_producto";
    }

    $res = $this->db->query(
      "SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura,
        (COALESCE(co.cantidad, 0) - COALESCE(sa.cantidad, 0)) AS existenciam,
        (COALESCE(cosp.cantidad, 0) - COALESCE(sasp.cantidad, 0)) AS existenciasp
        {$sql_con_req_f}
      FROM productos AS p
      INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
      INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
      LEFT JOIN
      (
        SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
        FROM compras_ordenes AS co
          INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
          AND Date(cp.fecha_aceptacion) <= '{$_GET['ffecha2']}'
          AND co.id_almacen = 1
          {$sql_com} AND co.id_orden_aplico IS NULL
        GROUP BY cp.id_producto
      ) AS co ON co.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
        FROM compras_salidas AS sa
        INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
        WHERE sa.status <> 'ca' AND sp.tipo_orden = 'p'
          AND Date(sa.fecha_registro) <= '{$_GET['ffecha2']}'
          AND sa.id_almacen = 1
          {$sql_sal}
        GROUP BY sp.id_producto
      ) AS sa ON sa.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
        FROM compras_ordenes AS co
          INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
          AND Date(cp.fecha_aceptacion) <= '{$_GET['ffecha2']}'
          AND co.id_almacen = 2
          {$sql_com} AND co.id_orden_aplico IS NULL
        GROUP BY cp.id_producto
      ) AS cosp ON cosp.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
        FROM compras_salidas AS sa
        INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
        WHERE sa.status <> 'ca' AND sp.tipo_orden = 'p'
          AND Date(sa.fecha_registro) <= '{$_GET['ffecha2']}'
          AND sa.id_almacen = 2
          {$sql_sal}
        GROUP BY sp.id_producto
      ) AS sasp ON sasp.id_producto = p.id_producto
      {$sql_con_req}
      WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
      ORDER BY nombre, nombre_producto ASC
      ");

    $response = array();
    if($res->num_rows() > 0)
      $response = $res->result();

    return $response;
  }

  /**
   * Reporte existencias por unidad formato pdf
   */
  public function getFormatoInvPdf(){
    $res = $this->getFormatoInvData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Existencia por unidades en Almacenes';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'R', 'R', 'R');
    $widths = array(80, 30, 30, 30, 30, 25);
    $header = array('Producto', 'E. MATRIZ', 'E. Fisica', 'E. Prod SANJO', 'E. Fisica');

    $familia = '';
    $totales = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        if ($key == 0)
        {
          $pdf->SetFont('Arial','B',11);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths(array(150));
          $pdf->Row(array($item->nombre), false, false);
          $familia = $item->nombre;
        }

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      if ($familia <> $item->nombre)
      {
        // if($key > 0){
        //   $pdf->SetFont('Arial','B',8);
        //   $pdf->SetX(6);
        //   $pdf->SetAligns($aligns);
        //   $pdf->SetWidths($widths);
        //   $pdf->Row(array('',
        //     MyString::formatoNumero($totales['familia'][0], 2, '', false),
        //     MyString::formatoNumero($totales['familia'][1], 2, '', false),
        //     MyString::formatoNumero($totales['familia'][2], 2, '', false),
        //     MyString::formatoNumero($totales['familia'][3], 2, '', false),
        //     ), true, false);
        // }
        $totales['familia'] = array(0,0,0,0);

        $pdf->SetFont('Arial','B',11);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths(array(150));
        $pdf->Row(array($item->nombre), false, false);
        $familia = $item->nombre;
      }

      $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);

      $imprimir = true;
      $existencia = $item->existenciam+$item->existenciasp;
      if($this->input->get('con_existencia') == 'si')
        if($existencia <= 0)
          $imprimir = false;


      if($imprimir)
      {
        // $totales['familia'][0] += $item->saldo_anterior;
        // $totales['familia'][1] += $item->entradas;
        // $totales['familia'][2] += $item->salidas;
        // $totales['familia'][3] += $existencia;

        // $totales['general'][0] += $item->saldo_anterior;
        // $totales['general'][1] += $item->entradas;
        // $totales['general'][2] += $item->salidas;
        // $totales['general'][3] += $existencia;

        $datos = array(
          $item->nombre_producto.' ('.$item->abreviatura.')',
          MyString::formatoNumero($item->existenciam, 2, '', false),
          '',
          MyString::formatoNumero($item->existenciasp, 2, '', false),
          ''
        );

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }
    }

    // $pdf->SetFont('Arial','B',8);
    // $pdf->SetX(6);
    // $pdf->SetAligns($aligns);
    // $pdf->SetWidths($widths);
    // $pdf->Row(array('',
    //   MyString::formatoNumero($totales['familia'][0], 2, '', false),
    //   MyString::formatoNumero($totales['familia'][1], 2, '', false),
    //   MyString::formatoNumero($totales['familia'][2], 2, '', false),
    //   MyString::formatoNumero($totales['familia'][3], 2, '', false),
    //   ), true, false);

    // $pdf->SetXY(6, $pdf->GetY()+5);
    // $pdf->Row(array('GENERAL',
    //   MyString::formatoNumero($totales['general'][0], 2, '', false),
    //   MyString::formatoNumero($totales['general'][1], 2, '', false),
    //   MyString::formatoNumero($totales['general'][2], 2, '', false),
    //   MyString::formatoNumero($totales['general'][3], 2, '', false),
    //   ), false, true);

    $pdf->Output('formato_inventario.pdf', 'I');
  }
  public function getFormatoInvXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=formato_inventario.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getFormatoInvData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Existencia por unidades en Almacenes';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">
          <td style="width:30px;border:1px solid #000;background-color: #cccccc;"></td>
          <td style="width:300px;border:1px solid #000;background-color: #cccccc;">Producto</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">E. MATRIZ</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">E. Fisica</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">E. Prod SANJO</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">E. Fisica</td>
        </tr>';

    $familia = '';
    $totales = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($key==0){

        if ($key == 0)
        {
          $familia = $item->nombre;
          $html .= '<tr>
              <td colspan="6" style="font-size:16px;border:1px solid #000;">'.$familia.'</td>
            </tr>';
        }
      }

      if ($familia <> $item->nombre)
      {
        if($key > 0){
          // $html .= '
          //   <tr style="font-weight:bold">
          //     <td></td>
          //     <td style="border:1px solid #000;">TOTALES</td>
          //     <td style="border:1px solid #000;">'.$totales['familia'][0].'</td>
          //     <td style="border:1px solid #000;">'.$totales['familia'][1].'</td>
          //     <td style="border:1px solid #000;">'.$totales['familia'][2].'</td>
          //     <td style="border:1px solid #000;">'.$totales['familia'][3].'</td>
          //   </tr>';
          $html .= '<tr>
              <td colspan="6"></td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>';
        }
        $totales['familia'] = array(0,0,0,0);

        $familia = $item->nombre;
        $html .= '<tr>
              <td colspan="6" style="font-size:16px;border:1px solid #000;">'.$familia.'</td>
            </tr>';
      }

      $imprimir = true;
      $existencia = $item->existenciam+$item->existenciasp;
      if($this->input->get('con_existencia') == 'si')
        if($existencia <= 0)
          $imprimir = false;


      if($imprimir)
      {
        // $totales['familia'][0] += $item->saldo_anterior;
        // $totales['familia'][1] += $item->entradas;
        // $totales['familia'][2] += $item->salidas;
        // $totales['familia'][3] += $existencia;

        // $totales['general'][0] += $item->saldo_anterior;
        // $totales['general'][1] += $item->entradas;
        // $totales['general'][2] += $item->salidas;
        // $totales['general'][3] += $existencia;

        $html .= '<tr>
              <td style="width:30px;border:1px solid #000;"></td>
              <td style="width:300px;border:1px solid #000;">'.$item->nombre_producto.' ('.$item->abreviatura.')'.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->existenciam.'</td>
              <td style="width:200px;border:1px solid #000;"> </td>
              <td style="width:200px;border:1px solid #000;">'.$item->existenciasp.'</td>
              <td style="width:200px;border:1px solid #000;"> </td>
            </tr>';
      }
    }

    // $html .= '
    //         <tr style="font-weight:bold">
    //           <td></td>
    //           <td style="border:1px solid #000;">TOTALES</td>
    //           <td style="border:1px solid #000;">'.$totales['familia'][0].'</td>
    //           <td style="border:1px solid #000;">'.$totales['familia'][1].'</td>
    //           <td style="border:1px solid #000;">'.$totales['familia'][2].'</td>
    //           <td style="border:1px solid #000;">'.$totales['familia'][3].'</td>
    //         </tr>
    //         <tr>
    //           <td colspan="6"></td>
    //         </tr>
    //         <tr>
    //           <td colspan="6"></td>
    //         </tr>
    //         <tr style="font-weight:bold">
    //           <td></td>
    //           <td style="border:1px solid #000;">GENERAL</td>
    //           <td style="border:1px solid #000;">'.$totales['general'][0].'</td>
    //           <td style="border:1px solid #000;">'.$totales['general'][1].'</td>
    //           <td style="border:1px solid #000;">'.$totales['general'][2].'</td>
    //           <td style="border:1px solid #000;">'.$totales['general'][3].'</td>
    //         </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }

  /**
   * Reporte existencias por unidad
   *
   * @return
   */
  public function getEPUSData($id_producto=null, $id_almacen=null, $con_req=false, $extras = [])
  {
    $sql_com = $sql_sal = $sql_req = $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    if(is_array($this->input->get('ffamilias'))){
      $sql .= " AND pf.id_familia IN (".implode(',', $this->input->get('ffamilias')).")";
    }

    if($this->input->get('fid_producto') != '' || $id_producto > 0){
      $id_producto = $id_producto>0? $id_producto: $this->input->get('fid_producto');
      $sql .= " AND p.id_producto = ".$id_producto;
      $res_prod = $this->db->query("SELECT id_empresa FROM productos WHERE id_producto = {$id_producto}")->row();
      $_GET['did_empresa'] = $res_prod->id_empresa;
    }

    if (!isset($extras['empresa'])) {
      $this->load->model('empresas_model');
      $client_default = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
      $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      if($this->input->get('did_empresa') != ''){
        $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
      }
    } elseif (isset($extras['empresa'])) {
      $sql .= " AND p.id_empresa = '{$extras['empresa']}'";
    }

    if ($this->input->get('did_empresa') == 3) { // gomez gudiño
      $sql_com .= " AND Date(cp.fecha_aceptacion) > '2015-04-30'";
      $sql_sal .= " AND Date(sa.fecha_registro) > '2015-04-30'";
    }

    $id_almacen = $id_almacen>0? $id_almacen: $this->input->get('did_almacen');
    if ($id_almacen > 0) {
      $sql_com .= " AND co.id_almacen = ".$id_almacen;
      $sql_sal .= " AND sa.id_almacen = ".$id_almacen;
      $sql_req .= " AND cr.id_almacen = ".$id_almacen;
    }

    $sql_con_req = '';
    $sql_con_req_f = '';
    if ($con_req) { // toma en cuenta la existencia de las requisición pendientes
      $sql_con_req_f = ', COALESCE(con_req.cantidad, 0) AS con_req';
      $sql_con_req = "LEFT JOIN
      (
        SELECT crq.id_producto, Sum(crq.cantidad) AS cantidad
        FROM compras_requisicion cr
          INNER JOIN compras_requisicion_productos crq ON cr.id_requisicion = crq.id_requisicion
        WHERE cr.status = 'p' AND cr.tipo_orden = 'p' AND cr.autorizado = 'f' AND cr.id_autorizo IS NULL
          AND cr.es_receta = 't' AND crq.importe > 0
          {$sql_req}
        GROUP BY crq.id_producto
      ) AS con_req ON con_req.id_producto = p.id_producto";
    }

    $res = $this->db->query(
      "SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura,
        COALESCE(co.cantidad, 0) AS entradas, COALESCE(co_dev.cantidad, 0) AS devoluciones,
        COALESCE(sa.cantidad, 0) AS salidas,
        (COALESCE(sal_co.cantidad, 0) - COALESCE(sal_sa.cantidad, 0)) AS saldo_anterior, p.stock_min
        {$sql_con_req_f}
      FROM productos AS p
      INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
      INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
      LEFT JOIN
      (
        SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
        FROM compras_ordenes AS co
          INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
          AND Date(cp.fecha_aceptacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql_com} AND co.id_orden_aplico IS NULL AND co.regresa_product = 'f'
        GROUP BY cp.id_producto
      ) AS co ON co.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
        FROM compras_ordenes AS co
          INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
          AND Date(cp.fecha_aceptacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql_com} AND co.id_orden_aplico IS NULL AND co.regresa_product = 't'
        GROUP BY cp.id_producto
      ) AS co_dev ON co_dev.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
        FROM compras_salidas AS sa
        INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
        WHERE sa.status <> 'ca' AND sp.tipo_orden = 'p'
          AND Date(sa.fecha_registro) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql_sal}
        GROUP BY sp.id_producto
      ) AS sa ON sa.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
        FROM compras_ordenes AS co
          INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
          AND Date(cp.fecha_aceptacion) < '{$fecha}'
          {$sql_com} AND co.id_orden_aplico IS NULL
        GROUP BY cp.id_producto
      ) AS sal_co ON sal_co.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
        FROM compras_salidas AS sa
        INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
        WHERE sa.status <> 'ca' AND sp.tipo_orden = 'p'
          AND Date(sa.fecha_registro) < '{$fecha}'
          {$sql_sal}
        GROUP BY sp.id_producto
      ) AS sal_sa ON sal_sa.id_producto = p.id_producto
      {$sql_con_req}
      WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
      ORDER BY nombre, nombre_producto ASC
      ");

    $response = array();
    if($res->num_rows() > 0)
      $response = $res->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getEPUSPdf(){
    $res = $this->getEPUSData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Existencia por unidades con devoluciones';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'R', 'R', 'R');
    $widths = array(80, 25, 25, 25, 25, 25);
    $header = array('Producto', 'Saldo', 'Entradas', 'Devoluciones', 'Salidas', 'E. Teórica');

    $familia = '';
    $totales = array('familia' => array(0,0,0,0,0), 'general' => array(0,0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        if ($key == 0)
        {
          $pdf->SetFont('Arial','B',11);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths(array(150));
          $pdf->Row(array($item->nombre), false, false);
          $familia = $item->nombre;
        }

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      if ($familia <> $item->nombre)
      {
        if($key > 0){
          $pdf->SetFont('Arial','B',8);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row(array('',
            MyString::formatoNumero($totales['familia'][0], 2, '', false),
            MyString::formatoNumero($totales['familia'][1], 2, '', false),
            MyString::formatoNumero($totales['familia'][2], 2, '', false),
            MyString::formatoNumero($totales['familia'][3], 2, '', false),
            MyString::formatoNumero($totales['familia'][4], 2, '', false),
            ), true, false);
        }
        $totales['familia'] = array(0,0,0,0,0);

        $pdf->SetFont('Arial','B',11);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths(array(150));
        $pdf->Row(array($item->nombre), false, false);
        $familia = $item->nombre;
      }

      $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);

      $imprimir = true;
      $existencia = $item->saldo_anterior+$item->entradas+$item->devoluciones-$item->salidas;
      if($this->input->get('con_existencia') == 'si')
        if($existencia <= 0)
          $imprimir = false;
      if($this->input->get('con_movimiento') == 'si')
        if($item->entradas <= 0 && $item->salidas <= 0)
          $imprimir = false;


      if($imprimir)
      {
        $totales['familia'][0] += $item->saldo_anterior;
        $totales['familia'][1] += $item->entradas;
        $totales['familia'][2] += $item->devoluciones;
        $totales['familia'][3] += $item->salidas;
        $totales['familia'][4] += $existencia;

        $totales['general'][0] += $item->saldo_anterior;
        $totales['general'][1] += $item->entradas;
        $totales['general'][2] += $item->devoluciones;
        $totales['general'][3] += $item->salidas;
        $totales['general'][4] += $existencia;

        $pdf->SetMyLinks(['', '',
          base_url('panel/inventario/epus_comp_pdf?reg_product=f&fid_producto='.$item->id_producto.'&'.MyString::getVarsLink(array('fid_producto'))),
          base_url('panel/inventario/epus_comp_pdf?reg_product=t&fid_producto='.$item->id_producto.'&'.MyString::getVarsLink(array('fid_producto')))
        ]);
        $datos = array($item->nombre_producto.' ('.$item->abreviatura.')',
          MyString::formatoNumero($item->saldo_anterior, 2, '', false),
          MyString::formatoNumero($item->entradas, 2, '', false),
          MyString::formatoNumero($item->devoluciones, 2, '', false),
          MyString::formatoNumero($item->salidas, 2, '', false),
          MyString::formatoNumero($existencia, 2, '', false),
          );

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }
    }

    $pdf->SetFont('Arial','B',8);
    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row(array('',
      MyString::formatoNumero($totales['familia'][0], 2, '', false),
      MyString::formatoNumero($totales['familia'][1], 2, '', false),
      MyString::formatoNumero($totales['familia'][2], 2, '', false),
      MyString::formatoNumero($totales['familia'][3], 2, '', false),
      MyString::formatoNumero($totales['familia'][4], 2, '', false),
      ), true, false);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array('GENERAL',
      MyString::formatoNumero($totales['general'][0], 2, '', false),
      MyString::formatoNumero($totales['general'][1], 2, '', false),
      MyString::formatoNumero($totales['general'][2], 2, '', false),
      MyString::formatoNumero($totales['general'][3], 2, '', false),
      MyString::formatoNumero($totales['general'][4], 2, '', false),
      ), false, true);

    $pdf->Output('epus.pdf', 'I');
  }

  public function getEPUSComData($id_producto=null, $id_almacen=null, $con_req=false, $extras = [])
  {
    $sql_com = $sql_sal = $sql_req = $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    if($this->input->get('fid_producto') != '' || $id_producto > 0){
      $id_producto = $id_producto>0? $id_producto: $this->input->get('fid_producto');
      $sql_com .= " AND p.id_producto = ".$id_producto;
      $res_prod = $this->db->query("SELECT id_empresa FROM productos WHERE id_producto = {$id_producto}")->row();
      $_GET['did_empresa'] = $res_prod->id_empresa;
    }

    if ($this->input->get('did_empresa') == 3) { // gomez gudiño
      $sql_com .= " AND Date(cp.fecha_aceptacion) > '2015-04-30'";
    }

    $sql_com .= " AND co.regresa_product = '{$this->input->get('reg_product')}'";

    $id_almacen = $id_almacen>0? $id_almacen: $this->input->get('did_almacen');
    if ($id_almacen > 0) {
      $sql_com .= " AND co.id_almacen = ".$id_almacen;
    }


    $res = $this->db->query(
      "SELECT p.id_producto, p.nombre, cp.cantidad, cp.precio_unitario, cp.importe, co.folio,
        Date(co.fecha_creacion) AS fecha_creacion, Date(co.fecha_aceptacion) AS fecha_aceptacion,
        (u.nombre || ' ' || u.apellido_paterno) AS usuario
      FROM compras_ordenes AS co
        INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        INNER JOIN productos AS p ON p.id_producto = cp.id_producto
        INNER JOIN usuarios AS u ON u.id = co.id_empleado
      WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
        AND Date(cp.fecha_aceptacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
        {$sql_com} AND co.id_orden_aplico IS NULL
      ORDER BY fecha_aceptacion ASC, folio ASC
      ");

    $response = array();
    if($res->num_rows() > 0)
      $response = $res->result();

    return $response;
  }
  public function getEPUSComPdf(){
    $res = $this->getEPUSComData();
    // echo "<pre>";
    //   var_dump($res);
    // echo "</pre>";exit;

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = $_GET['reg_product'] == 't'? 'Devoluciones': 'Compras';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre."\n": '');
    $pdf->titulo3 .= (isset($res[0]->nombre)? $res[0]->nombre: '');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'R', 'R', 'R', 'L');
    $widths = array(29, 20, 20, 29, 29, 29, 47);
    $header = array('Folio Ord/Dev', 'Fecha Reg', 'Fecha Ent', 'Cantidad', 'Precio', 'Importe', 'Registro');

    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);

      $datos = array(
        $item->folio,
        $item->fecha_creacion,
        $item->fecha_aceptacion,
        MyString::formatoNumero($item->cantidad, 2, '', false),
        MyString::formatoNumero($item->precio_unitario, 2, '', false),
        MyString::formatoNumero($item->importe, 2, '', false),
        $item->usuario,
      );

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->Output('epus.pdf', 'I');
  }

  public function getCostoInventario($fecha)
  {
    $sql_com = $sql_sal = $sql = '';

    //Filtros para buscar
    if($this->input->get('did_empresa') != '') {
      $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('did_empresa') == 3) { // gomez gudiño
      $sql_com .= " AND Date(cp.fecha_aceptacion) > '2015-04-30'";
      $sql_sal .= " AND Date(sa.fecha_registro) > '2015-04-30'";
    }

    $res = $this->db->query(
      "SELECT Sum((COALESCE(co.cantidad, 0) - COALESCE(sa.cantidad, 0)) * p.precio_promedio) AS costo
      FROM productos AS p
      INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
      LEFT JOIN
      (
        SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
        FROM compras_ordenes AS co
        INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
          AND Date(cp.fecha_aceptacion) <= '{$fecha}'
          {$sql_com}
        GROUP BY cp.id_producto
      ) AS co ON co.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
        FROM compras_salidas AS sa
        INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
        WHERE sa.status <> 'ca' AND sp.tipo_orden = 'p'
          AND Date(sa.fecha_registro) <= '{$fecha}'
          {$sql_sal}
        GROUP BY sp.id_producto
      ) AS sa ON sa.id_producto = p.id_producto
      WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
      ");

    $response = $res->row();

    return $response;
  }


  /**
   * Reporte costos ueps
   * @param  [type] $id_producto [description]
   * @param  [type] $fecha1      [description]
   * @param  [type] $fecha2      [description]
   * @return [type]              [description]
   */
  public function uepsData($id_producto, $fecha1, $fecha2, $id_almacen=null)
  {
    $sql_com = '';
    $sql_sal = '';
    if ($id_almacen > 0) {
      $sql_com .= " AND co.id_almacen = ".$id_almacen;
      $sql_sal .= " AND sa.id_almacen = ".$id_almacen;
    }

    $res = $this->db->query(
    "SELECT id_producto, Date(fecha) AS fecha, Date(fecha_reg) AS fecha_reg, cantidad, precio_unitario, importe, tipo
    FROM
      (
        (
        SELECT cp.id_producto, cp.num_row, co.fecha_creacion AS fecha, cp.fecha_aceptacion AS fecha_reg,
          cp.cantidad, cp.precio_unitario, cp.importe, 'c' AS tipo
        FROM compras_ordenes AS co
        INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        WHERE cp.id_producto = {$id_producto} AND co.status <> 'ca' AND cp.status = 'a'
          AND co.tipo_orden in('p', 't') AND Date(cp.fecha_aceptacion) <= '{$fecha2}'
          {$sql_com}
        )
        UNION ALL
        (
        SELECT sp.id_producto, sp.no_row AS num_row, sa.fecha_creacion AS fecha, sa.fecha_registro AS fecha_reg,
          sp.cantidad, sp.precio_unitario, (sp.cantidad * sp.precio_unitario) AS importe, 's' AS tipo
        FROM compras_salidas AS sa
        INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
        WHERE sp.id_producto = {$id_producto} AND sp.tipo_orden = 'p' AND sa.status <> 'ca'
          AND Date(sa.fecha_registro) <= '{$fecha2}'
          {$sql_sal}
        )
      ) AS t
    ORDER BY fecha_reg ASC");

    $result   = array();
    $result[] = array('fecha' => 'S. Anterior',
            'fecha_reg' => '',
            'entrada' => array(0, 0, 0, 0),
            'salida' => array(0, 0, 0, 0),
            'saldo' => array(0, 0, 0, 0), );

    foreach ($res->result() as $key => $value)
    {
      $row = array('fecha' => $value->fecha, 'fecha_reg' => $value->fecha_reg, 'entrada' => array('', '', '', ''), 'salida' => array('', '', ''), 'saldo' => array(0, 0, 0));
      if ($value->tipo == 'c')
      {
        $row['entrada'][0] = $value->cantidad;
        $row['entrada'][1] = $value->precio_unitario;
        $row['entrada'][2] = $value->cantidad*$value->precio_unitario;
        $row['entrada'][3] = $value->cantidad;

        $row['saldo'][0] = $value->cantidad+$result[count($result)-1]['saldo'][0];
        $row['saldo'][2] = $row['entrada'][2]+$result[count($result)-1]['saldo'][2];
        $row['saldo'][1] = $value->precio_unitario; //$row['saldo'][2]/($row['saldo'][0]==0? 1: $row['saldo'][0]);
        $result[] = $row;
        // echo count($result)."<br>";
      }elseif ($value->tipo == 's')
      {
        $aux_cantidad = $value->cantidad;
        $row = NULL;
        for ($ci = count($result)-1; $ci >= 0; --$ci)
        {
        	if($result[$ci]['entrada'][0] > 0)
        	{
	          $row = array('fecha' => $value->fecha, 'fecha_reg' => $value->fecha_reg, 'misma_salida' => ($row==NULL? '' : '&&'), 'entrada' => array('', '', '', ''), 'salida' => array('', '', ''), 'saldo' => array(0, 0, 0));
	          if($aux_cantidad >= floatval($result[$ci]['entrada'][3]) && floatval($result[$ci]['entrada'][3]) > 0)
	          {
	            $row['salida'][0] = $result[$ci]['entrada'][3];
	            $row['salida'][1] = $result[$ci]['entrada'][1];
	            $row['salida'][2] = $row['salida'][0]*$row['salida'][1];
	            // resta las cantidades diponibles de las entradas y el total de salida
	            $result[$ci]['entrada'][3] = 0;
	            $aux_cantidad -= $row['salida'][0];
	          }elseif(floatval($result[$ci]['entrada'][3]) > 0)
	          {
	            $row['salida'][0] = $aux_cantidad;
	            $row['salida'][1] = $result[$ci]['entrada'][1];
	            $row['salida'][2] = $row['salida'][0]*$row['salida'][1];
	            // resta las cantidades diponibles de las entradas y el total de salida
	            $result[$ci]['entrada'][3] -= $aux_cantidad;
	            $aux_cantidad = 0;
	          }
	          //saldos cuando son salidas
	          $row['saldo'][0] = $result[count($result)-1]['saldo'][0]-$row['salida'][0];
	          $row['saldo'][1] = $row['salida'][1];
	          $row['saldo'][2] = $result[count($result)-1]['saldo'][2]-$row['salida'][2];

	          $result[] = $row;
	          if(floatval(MyString::formatoNumero($aux_cantidad, 2, '', true)) <= 0)
	            break;
        	}
        }
      }
      unset($row);
      gc_collect_cycles();
    }

    $res->free_result();
    unset($res);

    $valkey = $entro = 0;
    foreach ($result as $key => $value)
    {
      if(strtotime($fecha1) > strtotime($value['fecha_reg']))
      {
        $valkey = $key-1;
        unset($result[$valkey]);
        $entro = 1;
      }
    }
    if($entro == 1)
    {
      $result[$valkey+1] = array('fecha' => 'S. Anterior', 'entrada' => array('', '', ''), 'salida' => array('', '', ''),
            'saldo' => $result[$valkey+1]['saldo'] );
    }
    gc_collect_cycles();

    $keyconta = $entrada_cantidad = $entrada_importe = $salida_cantidad = $salida_importe = $entrada_cantidadt1 = $entrada_importet1 = 0;
    foreach ($result as $key => $value)
    {
      if(strlen($value['saldo'][1]) == 0)
        unset($result[$key]);
      else
      {
        if(isset($value['misma_salida']) && $value['misma_salida'] == '&&')
          $result[$key]['fecha'] = '-';
        $entrada_cantidad += $value['entrada'][0];
        $entrada_importe += $value['entrada'][2];

        $entrada_cantidadt1 += $value['entrada'][0];
        $entrada_importet1 += $value['entrada'][2];
        if ($keyconta > 0)
        {
          $salida_cantidad += $value['salida'][0];
          $salida_importe += $value['salida'][2];
        }else
        {
          $entrada_cantidad += $value['saldo'][0];
          $entrada_importe += $value['saldo'][2];
        }
        $keyconta++;
      }
    }
    $result[] = array('fecha' => 'Total', 'entrada' => array($entrada_cantidadt1, '', $entrada_importet1), 'salida' => array($salida_cantidad, '', $salida_importe),
            'saldo' => array('', '', '') );

    $result[] = array('fecha' => 'Total General', 'entrada' => array($entrada_cantidad, '', $entrada_importe), 'salida' => array($salida_cantidad, '', $salida_importe),
            'saldo' => array(($entrada_cantidad-$salida_cantidad), '',($entrada_importe-$salida_importe)) );

    return $result;
  }
  /**
   * Reporte de existencias por costo
   * @return [type] [description]
   */
  public function getUEPSData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

    if(is_array($this->input->get('ffamilias'))){
      $sql .= " AND pf.id_familia IN (".implode(',', $this->input->get('ffamilias')).")";
    }
    if($this->input->get('fid_producto') != ''){
      $sql .= " AND p.id_producto = ".$this->input->get('fid_producto');
    }
    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      if($this->input->get('did_empresa') != ''){
        $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
      }

    $id_almacen = $this->input->get('did_almacen')>0? $this->input->get('did_almacen'): 0;

    $res = $this->db->query(
      "SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura
      FROM productos AS p
        INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
        INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
      WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
      ORDER BY nombre, nombre_producto ASC
      ");

    $response = array();
    if($res->num_rows() > 0)
    {
      $response = $res->result();
      foreach ($response as $key => $value)
      {
        $data = $this->uepsData($value->id_producto, $_GET['ffecha1'], $fecha, $id_almacen);
        $value->data       = array_pop($data);
        $value->data_saldo = array_shift($data);
        $response[$key]    = $value;
        unset($data);
      }
    }
    $res->free_result();

    return $response;
  }
  /**
   * Reporte existencias por costo UEPS
   */
  public function getUEPSPdf()
  {
    $res = $this->getUEPSData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Existencia por costos UEPS';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'R', 'R', 'R');
    $widths = array(65, 35, 35, 35, 35);
    $header = array('Producto', 'Saldo', 'Entradas', 'Salidas', 'Existencia');

    $familia = '';
    $totaltes = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        if ($key == 0)
        {
          $pdf->SetFont('Arial','B',11);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths(array(150));
          $pdf->Row(array($item->nombre), false, false);
          $familia = $item->nombre;
        }

        $pdf->SetFont('Arial','B',9);
        // $pdf->SetTextColor(255,255,255);
        // $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, false, false);
      }

      if ($familia <> $item->nombre)
      {
        if ($key > 0)
        {
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->SetMyLinks(array());
          $pdf->Row(array('TOTAL',
            MyString::formatoNumero($totaltes['familia'][0], 2, '$', false),
            MyString::formatoNumero($totaltes['familia'][1] , 2, '$', false),
            MyString::formatoNumero($totaltes['familia'][2], 2, '$', false),
            MyString::formatoNumero($totaltes['familia'][3], 2, '$', false),
            ), false, false);
        }
        $totaltes['familia'] = array(0,0,0,0);

        $pdf->SetFont('Arial','B',11);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths(array(150));
        $pdf->Row(array($item->nombre), false, false);
        $familia = $item->nombre;
      }

      $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);

      $imprimir = true;
      if($this->input->get('con_existencia') == 'si')
        if($item->data['saldo'][2] <= 0)
          $imprimir = false;
      if($this->input->get('con_movimiento') == 'si')
        if($item->data['salida'][2] <= 0 && ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]) <= 0)
          $imprimir = false;


      if($imprimir)
      {
        $totaltes['familia'][0] += $item->data_saldo['saldo'][2];
        $totaltes['familia'][1] += ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]);
        $totaltes['familia'][2] += $item->data['salida'][2];
        $totaltes['familia'][3] += $item->data['saldo'][2];

        $totaltes['general'][0] += $item->data_saldo['saldo'][2];
        $totaltes['general'][1] += ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]);
        $totaltes['general'][2] += $item->data['salida'][2];
        $totaltes['general'][3] += $item->data['saldo'][2];

        $datos = array($item->nombre_producto.' ('.$item->abreviatura.')',
          MyString::formatoNumero($item->data_saldo['saldo'][2], 2, '$', false),
          MyString::formatoNumero( ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]) , 2, '$', false),
          MyString::formatoNumero($item->data['salida'][2], 2, '$', false),
          MyString::formatoNumero(($item->data['saldo'][2]), 2, '$', false),
          );

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->SetMyLinks(array(base_url('panel/inventario/pueps_pdf?id_producto='.$item->id_producto.'&id_empresa='.$empresa['info']->id_empresa.
                  '&did_almacen='.$this->input->get('did_almacen').'&ffecha1='.$this->input->get('ffecha1').'&ffecha2='.$this->input->get('ffecha2'))));
        $pdf->Row($datos, false);
      }

      $pdf->SetMyLinks(array());
    }

    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetMyLinks(array());
    $pdf->Row(array('TOTAL',
      MyString::formatoNumero($totaltes['familia'][0], 2, '$', false),
      MyString::formatoNumero($totaltes['familia'][1] , 2, '$', false),
      MyString::formatoNumero($totaltes['familia'][2], 2, '$', false),
      MyString::formatoNumero($totaltes['familia'][3], 2, '$', false),
      ), false, false);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array('TOTAL GENERAL',
      MyString::formatoNumero($totaltes['general'][0], 2, '$', false),
      MyString::formatoNumero($totaltes['general'][1] , 2, '$', false),
      MyString::formatoNumero($totaltes['general'][2], 2, '$', false),
      MyString::formatoNumero($totaltes['general'][3], 2, '$', false),
      ), false);

    $pdf->Output('epc.pdf', 'I');
  }
  public function getUEPSXls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=epc.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getUEPSData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Existencia por costos UEPS';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
      $html .= '<tr style="font-weight:bold">
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Producto</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Saldo</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Entradas</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Salidas</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Existencia</td>
      </tr>';

    $familia = '';
    $totaltes = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if ($key == 0)
      {
        $html .= '
            <tr style="font-weight:bold">
              <td colspan="5">'.$item->nombre.'</td>
            </tr>';
        $familia = $item->nombre;
      }

      if ($familia <> $item->nombre)
      {
        if ($key > 0)
        {
          $html .= '
              <tr style="font-weight:bold">
                <td style="border:1px solid #000;">TOTAL</td>
                <td style="border:1px solid #000;">'.$totaltes['familia'][0].'</td>
                <td style="border:1px solid #000;">'.$totaltes['familia'][1].'</td>
                <td style="border:1px solid #000;">'.$totaltes['familia'][2].'</td>
                <td style="border:1px solid #000;">'.$totaltes['familia'][3].'</td>
              </tr>';
        }
        $totaltes['familia'] = array(0,0,0,0);

        $html .= '
          <tr style="font-weight:bold">
            <td colspan="5">'.$item->nombre.'</td>
          </tr>';
        $familia = $item->nombre;
      }

      $imprimir = true;
      if($this->input->get('con_existencia') == 'si')
        if($item->data['saldo'][2] <= 0)
          $imprimir = false;
      if($this->input->get('con_movimiento') == 'si')
        if($item->data['salida'][2] <= 0 && ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]) <= 0)
          $imprimir = false;


      if($imprimir)
      {
        $totaltes['familia'][0] += $item->data_saldo['saldo'][2];
        $totaltes['familia'][1] += ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]);
        $totaltes['familia'][2] += $item->data['salida'][2];
        $totaltes['familia'][3] += $item->data['saldo'][2];

        $totaltes['general'][0] += $item->data_saldo['saldo'][2];
        $totaltes['general'][1] += ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]);
        $totaltes['general'][2] += $item->data['salida'][2];
        $totaltes['general'][3] += $item->data['saldo'][2];

        $html .= '<tr>
            <td style="width:400px;border:1px solid #000;">'.$item->nombre_producto.' ('.$item->abreviatura.')'.'</td>
            <td style="width:150px;border:1px solid #000;">'.$item->data_saldo['saldo'][2].'</td>
            <td style="width:150px;border:1px solid #000;">'.($item->data['entrada'][2] - $item->data_saldo['saldo'][2]).'</td>
            <td style="width:150px;border:1px solid #000;">'.$item->data['salida'][2].'</td>
            <td style="width:150px;border:1px solid #000;">'.$item->data['saldo'][2].'</td>
          </tr>';
      }
    }

    $html .= '
        <tr style="font-weight:bold">
          <td style="border:1px solid #000;">TOTAL</td>
          <td style="border:1px solid #000;">'.$totaltes['familia'][0].'</td>
          <td style="border:1px solid #000;">'.$totaltes['familia'][1].'</td>
          <td style="border:1px solid #000;">'.$totaltes['familia'][2].'</td>
          <td style="border:1px solid #000;">'.$totaltes['familia'][3].'</td>
        </tr>
        <tr style="font-weight:bold">
          <td style="border:1px solid #000;">TOTAL GENERAL</td>
          <td style="border:1px solid #000;">'.$totaltes['general'][0].'</td>
          <td style="border:1px solid #000;">'.$totaltes['general'][1].'</td>
          <td style="border:1px solid #000;">'.$totaltes['general'][2].'</td>
          <td style="border:1px solid #000;">'.$totaltes['general'][3].'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

  public function getPUEPSPdf()
  {
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];
    $id_almacen = $this->input->get('did_almacen')>0? $this->input->get('did_almacen'): 0;

    $res = $this->uepsData($_GET['id_producto'], $_GET['ffecha1'], $_GET['ffecha2'], $id_almacen);

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('id_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));


    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte de inventario costo UEPS';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('C', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R');
    $widths = array(20, 18, 18, 26, 18, 18, 26, 18, 18, 26);
    $header = array('Fecha', 'CANT.', 'P.U.', 'P.T.', 'CANT.', 'P.U.', 'P.T.', 'CANT.', 'P.U.', 'P.T.');

    $familia = '';
    $keyconta = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $keyconta==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(26);
        $pdf->SetAligns(array('C', 'C', 'C'));
        $pdf->SetWidths(array(62, 62, 62));
        $pdf->Row(array('Entradas', 'Salidas', 'Saldo'), true);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $keyconta++;

      if(strpos($item['fecha'], 'Total') !== false)
      {
        $pdf->SetFont('Arial','B',8);
      }else
        $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);
      $datos = array(
        MyString::fechaAT($item['fecha']),

        $item['entrada'][0]!=''? MyString::formatoNumero($item['entrada'][0], 2, '', false): $item['entrada'][0],
        $item['entrada'][1]!=''? MyString::formatoNumero($item['entrada'][1], 2, '$', false): $item['entrada'][1],
        $item['entrada'][2]!=''? MyString::formatoNumero($item['entrada'][2], 2, '$', false): $item['entrada'][2],

        $item['salida'][0]!=''? MyString::formatoNumero($item['salida'][0], 2, '', false): $item['salida'][0],
        $item['salida'][1]!=''? MyString::formatoNumero($item['salida'][1], 2, '$', false): $item['salida'][1],
        $item['salida'][2]!=''? MyString::formatoNumero($item['salida'][2], 2, '$', false): $item['salida'][2],

        $item['saldo'][0]!=''? MyString::formatoNumero($item['saldo'][0], 2, '', false): $item['saldo'][0],
        $item['saldo'][1]!=''? MyString::formatoNumero($item['saldo'][1], 2, '$', false): $item['saldo'][1],
        $item['saldo'][2]!=''? MyString::formatoNumero($item['saldo'][2], 2, '$', false): $item['saldo'][2],
        );

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->Output('pueps.pdf', 'I');
  }


	/**
	 * Reporte de existencias por costo
	 * @return [type] [description]
	 */
	public function getEPCData()
	{
		$sql = '';

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

		if(is_array($this->input->get('ffamilias'))){
			$sql .= " AND pf.id_familia IN (".implode(',', $this->input->get('ffamilias')).")";
		}
		if($this->input->get('fid_producto') != ''){
			$sql .= " AND p.id_producto = ".$this->input->get('fid_producto');
		}
		$this->load->model('empresas_model');
		$client_default = $this->empresas_model->getDefaultEmpresa();
		$_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
		$_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
	    if($this->input->get('did_empresa') != ''){
	      $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
	    }

		$res = $this->db->query(
			"SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura
			FROM productos AS p
				INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
				INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
			WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
			ORDER BY nombre, nombre_producto ASC
			");

    $id_almacen = isset($_GET['did_almacen'])? $_GET['did_almacen']: 0;
		$response = array();
		if($res->num_rows() > 0)
		{
			$response = $res->result();
			foreach ($response as $key => $value)
			{
				$data = $this->promedioData($value->id_producto, $_GET['ffecha1'], $fecha, $id_almacen);
				$value->data       = array_pop($data);
				$value->data_saldo = array_shift($data);
				$response[$key]    = $value;
			}
		}

		return $response;
	}
	/**
	 * Reporte existencias por costo pdf
	 */
	public function getEPCPdf()
	{
		$res = $this->getEPCData();

		$this->load->model('empresas_model');
    $this->load->model('almacenes_model');
		$empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');

		if ($empresa['info']->logo !== '')
		  $pdf->logo = $empresa['info']->logo;

		$pdf->titulo1 = $empresa['info']->nombre_fiscal;

		$pdf->titulo2 = 'Existencia por costos';
		$pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'R', 'R', 'R', 'R');
		$widths = array(65, 35, 35, 35, 35);
		$header = array('Producto', 'Saldo', 'Entradas', 'Salidas', 'Existencia');

		$familia = '';
		$totaltes = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
		$total_cargos = $total_abonos = $total_saldo = 0;
		foreach($res as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				if ($key == 0)
				{
					$pdf->SetFont('Arial','B',11);
					$pdf->SetX(6);
					$pdf->SetAligns($aligns);
					$pdf->SetWidths(array(150));
					$pdf->Row(array($item->nombre), false, false);
					$familia = $item->nombre;
				}

				$pdf->SetFont('Arial','B',9);
				// $pdf->SetTextColor(255,255,255);
				// $pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, false, false);
			}

			if ($familia <> $item->nombre)
			{
				if ($key > 0)
				{
					$pdf->SetX(6);
					$pdf->SetAligns($aligns);
					$pdf->SetWidths($widths);
					$pdf->SetMyLinks(array());
					$pdf->Row(array('TOTAL',
						MyString::formatoNumero($totaltes['familia'][0], 2, '$', false),
						MyString::formatoNumero($totaltes['familia'][1] , 2, '$', false),
						MyString::formatoNumero($totaltes['familia'][2], 2, '$', false),
						MyString::formatoNumero($totaltes['familia'][3], 2, '$', false),
						), false, false);
				}
				$totaltes['familia'] = array(0,0,0,0);

				$pdf->SetFont('Arial','B',11);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths(array(150));
				$pdf->Row(array($item->nombre), false, false);
				$familia = $item->nombre;
			}

			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);

			$imprimir = true;
			if($this->input->get('con_existencia') == 'si')
				if($item->data['saldo'][2] <= 0)
					$imprimir = false;
			if($this->input->get('con_movimiento') == 'si')
				if($item->data['salida'][2] <= 0 && ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]) <= 0)
					$imprimir = false;


			if($imprimir)
			{
				$totaltes['familia'][0] += $item->data_saldo['saldo'][2];
				$totaltes['familia'][1] += ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]);
				$totaltes['familia'][2] += $item->data['salida'][2];
				$totaltes['familia'][3] += $item->data['saldo'][2];

				$totaltes['general'][0] += $item->data_saldo['saldo'][2];
				$totaltes['general'][1] += ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]);
				$totaltes['general'][2] += $item->data['salida'][2];
				$totaltes['general'][3] += $item->data['saldo'][2];

				$datos = array($item->nombre_producto.' ('.$item->abreviatura.')',
					MyString::formatoNumero($item->data_saldo['saldo'][2], 2, '$', false),
					MyString::formatoNumero( ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]) , 2, '$', false),
					MyString::formatoNumero($item->data['salida'][2], 2, '$', false),
					MyString::formatoNumero(($item->data['saldo'][2]), 2, '$', false),
					);

				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->SetMyLinks(array(base_url('panel/inventario/promedio_pdf?id_producto='.$item->id_producto.'&id_empresa='.$empresa['info']->id_empresa.
									'&did_almacen='.$this->input->get('did_almacen').'&ffecha1='.$this->input->get('ffecha1').'&ffecha2='.$this->input->get('ffecha2'))));
				$pdf->Row($datos, false);
			}

			$pdf->SetMyLinks(array());
		}

		$pdf->SetX(6);
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->SetMyLinks(array());
		$pdf->Row(array('TOTAL',
			MyString::formatoNumero($totaltes['familia'][0], 2, '$', false),
			MyString::formatoNumero($totaltes['familia'][1] , 2, '$', false),
			MyString::formatoNumero($totaltes['familia'][2], 2, '$', false),
			MyString::formatoNumero($totaltes['familia'][3], 2, '$', false),
			), false, false);

		$pdf->SetXY(6, $pdf->GetY()+5);
		$pdf->Row(array('TOTAL GENERAL',
			MyString::formatoNumero($totaltes['general'][0], 2, '$', false),
			MyString::formatoNumero($totaltes['general'][1] , 2, '$', false),
			MyString::formatoNumero($totaltes['general'][2], 2, '$', false),
			MyString::formatoNumero($totaltes['general'][3], 2, '$', false),
			), false);

		$pdf->Output('epc.pdf', 'I');
	}

  public function getEPCXls() {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=epc.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getEPCData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Existencia por costos';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">
          <td style="width:30px;border:1px solid #000;background-color: #cccccc;"></td>
          <td style="width:300px;border:1px solid #000;background-color: #cccccc;">Producto</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Saldo</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Entradas</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Salidas</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Existencia</td>
        </tr>';

    $familia = '';
    $totaltes = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($key==0){

        if ($key == 0)
        {
          $familia = $item->nombre;
          $html .= '<tr>
              <td colspan="6" style="font-size:16px;border:1px solid #000;">'.$familia.'</td>
            </tr>';
        }
      }

      if ($familia <> $item->nombre)
      {
        if($key > 0){
          $html .= '
            <tr style="font-weight:bold">
              <td></td>
              <td style="border:1px solid #000;">TOTALES</td>
              <td style="border:1px solid #000;">'.$totaltes['familia'][0].'</td>
              <td style="border:1px solid #000;">'.$totaltes['familia'][1].'</td>
              <td style="border:1px solid #000;">'.$totaltes['familia'][2].'</td>
              <td style="border:1px solid #000;">'.$totaltes['familia'][3].'</td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>';
        }
        $totaltes['familia'] = array(0,0,0,0);

        $familia = $item->nombre;
        $html .= '<tr>
              <td colspan="6" style="font-size:16px;border:1px solid #000;">'.$familia.'</td>
            </tr>';
      }

      $imprimir = true;
      if($this->input->get('con_existencia') == 'si')
        if($item->data['saldo'][2] <= 0)
          $imprimir = false;
      if($this->input->get('con_movimiento') == 'si')
        if($item->data['salida'][2] <= 0 && ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]) <= 0)
          $imprimir = false;


      if($imprimir)
      {
        $totaltes['familia'][0] += $item->data_saldo['saldo'][2];
        $totaltes['familia'][1] += ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]);
        $totaltes['familia'][2] += $item->data['salida'][2];
        $totaltes['familia'][3] += $item->data['saldo'][2];

        $totaltes['general'][0] += $item->data_saldo['saldo'][2];
        $totaltes['general'][1] += ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]);
        $totaltes['general'][2] += $item->data['salida'][2];
        $totaltes['general'][3] += $item->data['saldo'][2];

        $html .= '<tr>
              <td style="width:30px;border:1px solid #000;"></td>
              <td style="width:300px;border:1px solid #000;">'.$item->nombre_producto.' ('.$item->abreviatura.')'.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->data_saldo['saldo'][2].'</td>
              <td style="width:200px;border:1px solid #000;">'.($item->data['entrada'][2] - $item->data_saldo['saldo'][2]).'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->data['salida'][2].'</td>
              <td style="width:200px;border:1px solid #000;">'.($item->data['saldo'][2]).'</td>
            </tr>';
      }
    }

    $html .= '
            <tr style="font-weight:bold">
              <td></td>
              <td style="border:1px solid #000;">TOTALES</td>
              <td style="border:1px solid #000;">'.$totaltes['familia'][0].'</td>
              <td style="border:1px solid #000;">'.$totaltes['familia'][1].'</td>
              <td style="border:1px solid #000;">'.$totaltes['familia'][2].'</td>
              <td style="border:1px solid #000;">'.$totaltes['familia'][3].'</td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>
            <tr style="font-weight:bold">
              <td></td>
              <td style="border:1px solid #000;">GENERAL</td>
              <td style="border:1px solid #000;">'.$totaltes['general'][0].'</td>
              <td style="border:1px solid #000;">'.$totaltes['general'][1].'</td>
              <td style="border:1px solid #000;">'.$totaltes['general'][2].'</td>
              <td style="border:1px solid #000;">'.$totaltes['general'][3].'</td>
            </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }

  /**
   * Reporte de existencias por costo
   * @return [type] [description]
   */
  public function getHistorialNivData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

    if($this->input->get('fid_producto') != ''){
      $sql .= " WHERE p.id_producto = ".$this->input->get('fid_producto');
    }
    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    // if($this->input->get('did_empresa') != ''){
    //   $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
    // }

    $res = $this->db->query(
      "SELECT t.fecha, t.id_empleado, u.nombre, t.descripcion
      FROM (
        SELECT Date(fecha_creacion) AS fecha, id_empleado, concepto AS descripcion
        FROM compras_salidas WHERE status = 'n' AND id_empresa = {$_GET['did_empresa']}
          AND Date(fecha_creacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'

        UNION

        SELECT Date(fecha_aceptacion) AS fecha, id_empleado, descripcion
        FROM compras_ordenes WHERE status = 'n' AND id_empresa = {$_GET['did_empresa']}
          AND Date(fecha_aceptacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
      ) t
      INNER JOIN usuarios u ON u.id = t.id_empleado
      GROUP BY t.fecha, t.id_empleado, u.nombre, t.descripcion
      ORDER BY fecha ASC, nombre ASC");

    $response = array();
    if($res->num_rows() > 0)
    {
      $response = $res->result();
      foreach ($response as $key => $value)
      {
        $res_cosa = $this->db->query("SELECT p.id_producto, p.nombre, es.entrada, es.salida, pu.abreviatura
            FROM
            (
              SELECT id_producto, Sum(entrada) AS entrada, Sum(salida) AS salida
              FROM
              (
                SELECT cp.id_producto, cp.cantidad AS entrada, 0 AS salida
                FROM compras_ordenes co
                  INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
                WHERE co.status = 'n' AND Date(co.fecha_aceptacion) = '{$value->fecha}'
                  AND co.id_empresa = {$_GET['did_empresa']}
                  AND co.id_empleado = {$value->id_empleado}

                UNION

                SELECT cp.id_producto, 0 AS entrada, cp.cantidad AS salida
                FROM compras_salidas cs
                  INNER JOIN compras_salidas_productos cp ON cs.id_salida = cp.id_salida
                WHERE cs.status = 'n' AND cp.tipo_orden = 'p'
                  AND Date(cs.fecha_creacion) = '{$value->fecha}'
                  AND cs.id_empresa = {$_GET['did_empresa']}
                  AND cs.id_empleado = {$value->id_empleado}
              ) es
              GROUP BY id_producto
            ) AS es
            INNER JOIN productos p ON p.id_producto = es.id_producto
            INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
            {$sql}");
        $value->datos = $res_cosa->result();
        foreach ($value->datos as $keypr => $prodcts)
        {
          $prodcts->inventario = $this->getNivelarData(NULL, $prodcts->id_producto);
          $prodcts->inventario = $prodcts->inventario['productos'][0];
        }
        $res_cosa->free_result();
        // $res_compra = $this->db->query("SELECT co.id_orden, p.id_producto, p.nombre, cp.cantidad, pu.abreviatura
        //   FROM compras_ordenes co
        //     INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
        //     INNER JOIN productos p ON p.id_producto = cp.id_producto
        //     INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        //   WHERE co.status = 'n' AND Date(co.fecha_aceptacion) = '{$value->fecha}' AND co.id_empresa = {$_GET['did_empresa']}");
        // $value->compras = $res_compra->result();

        // $res_salidas = $this->db->query("SELECT cs.id_salida, p.id_producto, p.nombre, cp.cantidad, pu.abreviatura
        //   FROM compras_salidas cs
        //     INNER JOIN compras_salidas_productos cp ON cs.id_salida = cp.id_salida
        //     INNER JOIN productos p ON p.id_producto = cp.id_producto
        //     INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        //   WHERE cs.status = 'n' AND Date(cs.fecha_creacion) = '{$value->fecha}' AND cs.id_empresa = {$_GET['did_empresa']}");
        // $value->salidas = $res_salidas->result();

        // $res_salidas->free_result();
        // $res_compra->free_result();
        if(count($value->datos) === 0)
          unset($response[$key]);
      }
    }

    return $response;
  }
  /**
   * Reporte existencias por costo pdf
   */
  public function getHistorialNivPdf()
  {
    $res = $this->getHistorialNivData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Historial de nivelaciones';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'R', 'R', 'R', 'R', 'R');
    $widths = array(80, 30, 30, 30, 30, 30, 30);
    $header = array('Producto', 'Anterior', 'Entradas', 'Salidas', 'Existencia');

    $familia = '';
    $totaltes = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',9);
        // $pdf->SetTextColor(255,255,255);
        // $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, false, false);
      }

      $pdf->SetFont('Arial', 'B', 11);
      $pdf->SetX(6);
      $pdf->SetAligns(array('L', 'L', 'L'));
      $pdf->SetWidths(array(30, 50, 120));
      $pdf->Row(array(MyString::fechaAT($item->fecha), $item->nombre, $item->descripcion), false, false);

      $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);

      foreach ($item->datos as $key2 => $prod)
      {
        $datos = array($prod->nombre.' ('.$prod->abreviatura.')',
          MyString::formatoNumero(($prod->inventario->data[0]+$prod->salida-$prod->entrada), 2, '', false),
          MyString::formatoNumero($prod->entrada, 2, '', false),
          MyString::formatoNumero($prod->salida, 2, '', false),
          MyString::formatoNumero($prod->inventario->data[0], 2, '', false),
          );

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }
    }

    // $pdf->SetX(6);
    // $pdf->SetAligns($aligns);
    // $pdf->SetWidths($widths);
    // $pdf->SetMyLinks(array());
    // $pdf->Row(array('TOTAL',
    //   MyString::formatoNumero($totaltes['familia'][0], 2, '$', false),
    //   MyString::formatoNumero($totaltes['familia'][1] , 2, '$', false),
    //   MyString::formatoNumero($totaltes['familia'][2], 2, '$', false),
    //   MyString::formatoNumero($totaltes['familia'][3], 2, '$', false),
    //   ), false, false);

    // $pdf->SetXY(6, $pdf->GetY()+5);
    // $pdf->Row(array('TOTAL GENERAL',
    //   MyString::formatoNumero($totaltes['familia'][0], 2, '$', false),
    //   MyString::formatoNumero($totaltes['familia'][1] , 2, '$', false),
    //   MyString::formatoNumero($totaltes['familia'][2], 2, '$', false),
    //   MyString::formatoNumero($totaltes['familia'][3], 2, '$', false),
    //   ), false);

    $pdf->Output('epc.pdf', 'I');
  }

	/**
	 * Reporte costos promedio productos
	 * @param  [type] $id_producto [description]
	 * @param  [type] $fecha1      [description]
	 * @param  [type] $fecha2      [description]
	 * @return [type]              [description]
	 */
	public function promedioData($id_producto, $fecha1, $fecha2, $id_almacen=null)
	{
    $sql_com = $sql_sal = '';
    $prod = $this->db->query("SELECT id_empresa FROM productos WHERE id_producto = {$id_producto}")->row();
    if (isset($prod->id_empresa) && $prod->id_empresa == 3) { // gomez gudiño
      $sql_com .= " AND Date(cp.fecha_aceptacion) > '2015-04-30'";
      $sql_sal .= " AND Date(sa.fecha_registro) > '2015-04-30'";
    }

    if ($id_almacen > 0) {
      $sql_com .= " AND co.id_almacen = ".$id_almacen;
      $sql_sal .= " AND sa.id_almacen = ".$id_almacen;
    }

		$res = $this->db->query(
		"SELECT id_producto, Date(fecha) AS fecha, Date(fecha_reg) AS fecha_reg, cantidad, precio_unitario, importe, folio, tipo
		FROM
			(
				(
				SELECT cp.id_producto, cp.num_row, co.fecha_creacion AS fecha, cp.fecha_aceptacion AS fecha_reg,
          cp.cantidad, cp.precio_unitario, cp.importe, co.folio, 'c' AS tipo
				FROM compras_ordenes AS co
				INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
				WHERE cp.id_producto = {$id_producto} AND co.status <> 'ca' AND cp.status = 'a'
					AND co.tipo_orden in('p', 't') AND Date(cp.fecha_aceptacion) <= '{$fecha2}'
          {$sql_com} AND co.id_orden_aplico IS NULL
				)
				UNION ALL
				(
        SELECT sp.id_producto, sp.no_row AS num_row, sa.fecha_creacion AS fecha, sa.fecha_registro AS fecha_reg,
          sp.cantidad, sp.precio_unitario, (sp.cantidad * sp.precio_unitario) AS importe, sa.folio, 's' AS tipo
        FROM compras_salidas AS sa
        INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
        WHERE sp.id_producto = {$id_producto} AND sp.tipo_orden = 'p' AND sa.status <> 'ca'
          AND Date(sa.fecha_registro) <= '{$fecha2}'
          {$sql_sal}
				)
			) AS t
		ORDER BY fecha_reg ASC, tipo ASC");

		$result   = array();
		$result[] = array('fecha' => 'S. Anterior',
              'fecha_reg' => '',
						'entrada' => array(0, 0, 0),
						'salida' => array(0, 0, 0),
						'saldo' => array(0, 0, 0, 0), );
		foreach ($res->result() as $key => $value)
		{
			$row = array('fecha' => $value->fecha, 'fecha_reg' => $value->fecha_reg, 'entrada' => array('', '', ''), 'salida' => array('', '', ''), 'saldo' => array(0, 0, 0, 0), 'folio' => $value->folio);
			if ($value->tipo == 'c')
			{
				$row['entrada'][0] = $value->cantidad;
				$row['entrada'][1] = $value->precio_unitario;
				$row['entrada'][2] = $value->cantidad*$value->precio_unitario;

				$row['saldo'][0] = $value->cantidad+$result[$key]['saldo'][0];
				$row['saldo'][2] = $row['entrada'][2]+$result[$key]['saldo'][2];
        $row['saldo'][1] = ($row['saldo'][2]/($row['saldo'][0]==0? 1: $row['saldo'][0]));
        if ($row['saldo'][1]<0) {
          $row['saldo'][1] = $row['entrada'][1];
          $row['saldo'][2] = $row['saldo'][0]*$row['saldo'][1];
        }
				// $row['saldo'][3] = $row['saldo'][2]/($row['saldo'][0]==0? 1: $row['saldo'][0]);
			}else
			{
				$row['salida'][0] = $value->cantidad;
				$row['salida'][1] = $result[$key]['saldo'][1];
				$row['salida'][2] = $value->cantidad*$row['salida'][1];

				$row['saldo'][0] = $result[$key]['saldo'][0]-$value->cantidad;
				$row['saldo'][1] = $result[$key]['saldo'][1];
        // $row['saldo'][3] = $result[$key]['saldo'][3];
				$row['saldo'][2] = $row['saldo'][0]*$row['saldo'][1];
			}

			$result[] = $row;
		}

		$valkey = $entro = 0;
		foreach ($result as $key => $value)
		{
			if($fecha1 > $value['fecha_reg'])
			{
				$valkey = $key-1;
				unset($result[$valkey]);
				$entro = 1;
			}
		}
		if($entro == 1)
		{
			$result[$valkey+1] = array('fecha' => 'S. Anterior', 'entrada' => array('', '', ''), 'salida' => array('', '', ''),
						'saldo' => $result[$valkey+1]['saldo'] );
		}

		$keyconta = $entrada_cantidad = $entrada_importe = $salida_cantidad = $salida_importe = $entrada_cantidadt1 = $entrada_importet1 = 0;
		foreach ($result as $key => $value)
		{
			$entrada_cantidad += $value['entrada'][0];
			$entrada_importe += $value['entrada'][2];

			$entrada_cantidadt1 += $value['entrada'][0];
			$entrada_importet1 += $value['entrada'][2];
			if ($keyconta > 0)
			{
				$salida_cantidad += $value['salida'][0];
				$salida_importe += $value['salida'][2];
			}else
			{
				$entrada_cantidad += $value['saldo'][0];
				$entrada_importe += $value['saldo'][2];
			}
			$keyconta++;
		}
		$result[] = array('fecha' => 'Total', 'entrada' => array($entrada_cantidadt1, '', $entrada_importet1), 'salida' => array($salida_cantidad, '', $salida_importe),
						'saldo' => array('', '', '') );

		$result[] = array('fecha' => 'Total General', 'entrada' => array($entrada_cantidad, '', $entrada_importe), 'salida' => array($salida_cantidad, '', $salida_importe),
						'saldo' => array(($entrada_cantidad-$salida_cantidad), '',($entrada_importe-$salida_importe)) );

		return $result;
	}

	public function getPromediodf()
	{
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

    $id_almacen = isset($_GET['did_almacen'])? $_GET['did_almacen']: 0;
		$res = $this->promedioData($_GET['id_producto'], $_GET['ffecha1'], $_GET['ffecha2'], $id_almacen);

		$this->load->model('empresas_model');
    $this->load->model('almacenes_model');
		$empresa = $this->empresas_model->getInfoEmpresa($this->input->get('id_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));


		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');

		if ($empresa['info']->logo !== '')
		  $pdf->logo = $empresa['info']->logo;

		$pdf->titulo1 = $empresa['info']->nombre_fiscal;
		$pdf->titulo2 = 'Reporte de inventario costo promedio';
		$pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);

		$aligns = array('C', 'C', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R');
		$widths = array(14, 18, 18, 18, 22, 18, 18, 22, 18, 18, 23);
		$header = array('Folio', 'Fecha', 'CANT.', 'P.U.', 'P.T.', 'CANT.', 'P.U.', 'P.T.', 'CANT.', 'P.U.', 'P.T.');

		$familia = '';
		$keyconta = 0;
		foreach($res as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $keyconta==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(38);
				$pdf->SetAligns(array('C', 'C', 'C'));
				$pdf->SetWidths(array(58, 58, 59));
				$pdf->Row(array('Entradas', 'Salidas', 'Saldo'), true);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}

			$keyconta++;

			if(strpos($item['fecha'], 'Total') !== false)
			{
				$pdf->SetFont('Arial','B',8);
			}else
				$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);
			$datos = array(
        (isset($item['folio'])? $item['folio']: ''),
				MyString::fechaAT($item['fecha']),

				$item['entrada'][0]!=''? MyString::formatoNumero($item['entrada'][0], 2, '', false): $item['entrada'][0],
				$item['entrada'][1]!=''? MyString::formatoNumero($item['entrada'][1], 2, '$', false): $item['entrada'][1],
				$item['entrada'][2]!=''? MyString::formatoNumero($item['entrada'][2], 2, '$', false): $item['entrada'][2],

				$item['salida'][0]!=''? MyString::formatoNumero($item['salida'][0], 2, '', false): $item['salida'][0],
				$item['salida'][1]!=''? MyString::formatoNumero($item['salida'][1], 2, '$', false): $item['salida'][1],
				$item['salida'][2]!=''? MyString::formatoNumero($item['salida'][2], 2, '$', false): $item['salida'][2],

				$item['saldo'][0]!=''? MyString::formatoNumero($item['saldo'][0], 2, '', false): $item['saldo'][0],
				$item['saldo'][1]!=''? MyString::formatoNumero($item['saldo'][1], 2, '$', false): $item['saldo'][1],
				$item['saldo'][2]!=''? MyString::formatoNumero($item['saldo'][2], 2, '$', false): $item['saldo'][2],
				);

			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}

		$pdf->Output('promedio.pdf', 'I');
	}

  public function promedioAllData($id_empresa, $fecha1, $fecha2, $id_almacen=null, $id_producto=null)
  {
    $sql = $sql_com = $sql_sal = '';
    if ($id_empresa == 3) { // gomez gudiño
      $sql_com .= " AND Date(cp.fecha_aceptacion) > '2015-04-30'";
      $sql_sal .= " AND Date(sa.fecha_registro) > '2015-04-30'";
    }

    if ($id_almacen > 0) {
      $sql_com .= " AND co.id_almacen = ".$id_almacen;
      $sql_sal .= " AND sa.id_almacen = ".$id_almacen;
    }

    if ($id_producto > 0) {
      $sql = " WHERE p.id_producto = ".$id_producto;
    }

    $res = $this->db->query(
      "SELECT p.id_producto, p.nombre, Sum(ant_en.cantidad) AS ant_entradas,
        Sum(ant_sa.cantidad) AS ant_salidas,
        (Coalesce(Sum(ant_en.cantidad), 0) - Coalesce(Sum(ant_sa.cantidad), 0)) AS ant_saldo,
        String_agg(en.folio, ', ') AS folios_ent, Sum(en.cantidad) AS entradas,
        String_agg(sa.folio, ', ') AS folios_sal, Sum(sa.cantidad) AS salidas,
        (Coalesce(Sum(en.cantidad), 0) - Coalesce(Sum(sa.cantidad), 0)) AS saldo
      FROM
        productos p
        LEFT JOIN (
          SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad, Sum(cp.importe) AS importe
          FROM compras_ordenes AS co
          INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
          WHERE co.status <> 'ca' AND cp.status = 'a'
            AND co.tipo_orden in('p', 't') AND Date(cp.fecha_aceptacion) < '{$fecha1}'
            AND co.id_orden_aplico IS NULL {$sql_com}
          GROUP BY cp.id_producto
        ) ant_en ON p.id_producto = ant_en.id_producto
        LEFT JOIN (
          SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad, Sum(sp.cantidad * sp.precio_unitario) AS importe
          FROM compras_salidas AS sa
          INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
          WHERE sp.tipo_orden = 'p' AND sa.status <> 'ca'
            AND Date(sa.fecha_registro) < '{$fecha1}' {$sql_sal}
          GROUP BY sp.id_producto
        ) ant_sa ON p.id_producto = ant_sa.id_producto
        LEFT JOIN (
          SELECT cp.id_producto, String_agg(co.fecha_creacion::text, ', ') AS fecha, String_agg(cp.fecha_aceptacion::text, ', ') AS fecha_reg,
            Sum(cp.cantidad) AS cantidad, Sum(cp.importe) AS importe, String_agg(co.folio::text, ', ') AS folio
          FROM compras_ordenes AS co
          INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
          WHERE co.status <> 'ca' AND cp.status = 'a'
            AND co.tipo_orden in('p', 't') AND Date(cp.fecha_aceptacion) BETWEEN '{$fecha1}' AND '{$fecha2}'
            AND co.id_orden_aplico IS NULL {$sql_com}
          GROUP BY cp.id_producto
        ) en ON p.id_producto = en.id_producto
        LEFT JOIN (
          SELECT sp.id_producto, String_agg(sa.fecha_creacion::text, ', ') AS fecha, String_agg(sa.fecha_registro::text, ', ') AS fecha_reg,
            Sum(sp.cantidad) AS cantidad, Sum(sp.cantidad * sp.precio_unitario) AS importe, String_agg(sa.folio::text, ', ') AS folio
          FROM compras_salidas AS sa
          INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
          WHERE sp.tipo_orden = 'p' AND sa.status <> 'ca'
            AND Date(sa.fecha_registro) BETWEEN '{$fecha1}' AND '{$fecha2}' {$sql_sal}
          GROUP BY sp.id_producto
        ) sa ON p.id_producto = sa.id_producto
      {$sql}
      GROUP BY p.id_producto
      HAVING Sum(ant_en.cantidad) > 0 AND Sum(ant_sa.cantidad) > 0
      ");

    $result = $res->result();

    return $result;
  }

  public function promedioAllPdf()
  {
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

    if (empty($_GET['did_empresa'])) {
      $empresaDef = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = $empresaDef->id_empresa;
    }

    $id_almacen = isset($_GET['did_almacen'])? $_GET['did_almacen']: 0;
    $id_producto = isset($_GET['fid_producto'])? $_GET['fid_producto']: 0;
    $res = $this->promedioAllData($this->input->get('did_empresa'), $_GET['ffecha1'], $_GET['ffecha2'], $id_almacen, $id_producto);

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));


    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte de inventario 2';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('R', 'R', 'L', 'L', 'R', 'L', 'R');
    $aligns2 = array('C', 'C', 'L', 'L', 'C', 'C', 'C');
    $widths = array(18, 18, 30, 72, 18, 30, 18);
    $header = array('EXISTENCIA INI', 'ENTRADAS', 'FOLIOS ENTRADAS', 'PRODUCTO', 'SALIDAS', 'FOLIOS SALIDAS', 'EXISTENCIA FIN');

    $familia = '';
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B', 8);
        $pdf->SetFillColor(220,220,220);

        $pdf->SetX(6);
        $pdf->SetAligns($aligns2);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $pdf->SetFont('Arial', '', 7);

      $pdf->SetTextColor(0, 0, 0);
      $datos = array(
        MyString::formatoNumero($item->ant_saldo, 2, '', false),
        MyString::formatoNumero($item->entradas, 2, '', false),
        $item->folios_ent,
        $item->nombre,
        MyString::formatoNumero($item->salidas, 2, '', false),
        $item->folios_sal,
        MyString::formatoNumero($item->saldo+$item->ant_saldo, 2, '', false),
      );

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->Output('existencia2.pdf', 'I');
  }

  public function promedioAllXls() {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=existencia2.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

    $id_almacen = isset($_GET['did_almacen'])? $_GET['did_almacen']: 0;
    $id_producto = isset($_GET['fid_producto'])? $_GET['fid_producto']: 0;
    $res = $this->promedioAllData($this->input->get('did_empresa'), $_GET['ffecha1'], $_GET['ffecha2'], $id_almacen, $id_producto);

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de inventario 2';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">
          <td style="width:30px;border:1px solid #000;background-color: #cccccc;"></td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">EXISTENCIA INI</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">ENTRADAS</td>
          <td style="width:300px;border:1px solid #000;background-color: #cccccc;">FOLIOS ENTRADAS</td>
          <td style="width:500px;border:1px solid #000;background-color: #cccccc;">PRODUCTO</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">SALIDAS</td>
          <td style="width:300px;border:1px solid #000;background-color: #cccccc;">FOLIOS SALIDAS</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">EXISTENCIA FIN</td>
        </tr>';

    $familia = '';
    $totaltes = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $imprimir = true;
      // if($this->input->get('con_existencia') == 'si')
      //   if($item->data['saldo'][2] <= 0)
      //     $imprimir = false;
      // if($this->input->get('con_movimiento') == 'si')
      //   if($item->data['salida'][2] <= 0 && ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]) <= 0)
      //     $imprimir = false;


      if($imprimir)
      {
        $html .= '<tr>
            <td style="width:30px;border:1px solid #000;"></td>
            <td style="width:200px;border:1px solid #000;">'.MyString::formatoNumero($item->ant_saldo, 2, '', false).'</td>
            <td style="width:200px;border:1px solid #000;">'.MyString::formatoNumero($item->entradas, 2, '', false).'</td>
            <td style="width:300px;border:1px solid #000;">'.$item->folios_ent.'</td>
            <td style="width:500px;border:1px solid #000;">'.$item->nombre.'</td>
            <td style="width:200px;border:1px solid #000;">'.MyString::formatoNumero($item->salidas, 2, '', false).'</td>
            <td style="width:300px;border:1px solid #000;">'.$item->folios_sal.'</td>
            <td style="width:200px;border:1px solid #000;">'.MyString::formatoNumero($item->saldo+$item->ant_saldo, 2, '', false).'</td>
          </tr>';
      }
    }


    $html .= '</tbody>
    </table>';

    echo $html;
  }

	public function getNivelarData($id_familia, $id_producto=NULL, $id_almacen=NULL)
	{
		$this->load->library('pagination');
		$params = array(
				'result_items_per_page' => '50',
				'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
		);
		if($params['result_page'] % $params['result_items_per_page'] == 0)
			$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

		$sql = '';

		//Filtros para buscar
		if($id_familia != NULL)
      $sql .= " AND p.id_familia = ".$id_familia;

    if($id_producto != NULL)
      $sql .= " AND p.id_producto = ".$id_producto;

	    $query = BDUtil::pagination(
	    	"SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura,
	    		COALESCE((SELECT cp.precio_unitario FROM compras_ordenes co INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
                  WHERE co.status <> 'ca' AND cp.status = 'a' AND cp.precio_unitario > 0 AND cp.id_producto = p.id_producto ORDER BY cp.fecha_aceptacion DESC LIMIT 1), 0) AS ul_precio_unitario
			FROM productos AS p
				INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
				INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
			WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
			ORDER BY nombre, nombre_producto ASC
			", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
			'productos'       => array(),
			'total_rows'     => $query['total_rows'],
			'items_per_page' => $params['result_items_per_page'],
			'result_page'    => $params['result_page'],
		);
		if($res->num_rows() > 0) {
			$response['productos'] = $res->result();

      // determinara por la fecha del form o la actual.
			$fecha = isset($_GET['dfecha']) ? $_GET['dfecha'] : date('Y-m-d');
      $fecha = date($fecha, strtotime("+1 day"));

			foreach ($response['productos'] as $key => $value)
			{
				$data = $this->promedioData($value->id_producto, $fecha, $fecha, $id_almacen);
				array_pop($data); array_pop($data);
				$value->data = array_pop($data)['saldo'];
				$response[$key] = $value;
			}
		}

		return $response;
	}

	/**
	 * Realiza una nivelacion de inventario, agregando ordenes de compra y/o salidas de productos
	 * @return [type] [description]
	 */
	public function nivelar()
	{
    $fecha = isset($_GET['dfecha']{0})? $_GET['dfecha']: date("Y-m-d");
    $id_almacen = ($this->input->post('id_almacen')>0?$this->input->post('id_almacen'):1);
		$compra = array();
		$salida = array();
		foreach ($_POST['idproducto'] as $key => $produto)
		{
			if($_POST['diferencia'][$key] > 0) //salida
			{
				$salida[] = array(
					'id_producto'     => $produto,
					'cantidad'        => abs($_POST['diferencia'][$key]),
					'precio_unitario' => $_POST['precio_producto'][$key],
				);
			}elseif($_POST['diferencia'][$key] < 0) //compra
			{
				$presenta = $this->db->query("SELECT id_presentacion FROM productos_presentaciones WHERE status = 'ac' AND id_producto = {$produto} AND cantidad = 1 LIMIT 1")->row();
				$compra[] = array(
          'id_producto'      => $produto,
          'id_presentacion'  => (count($presenta)>0? $presenta->id_presentacion: NULL),
          'descripcion'      => $_POST['descripcion'][$key],
          'cantidad'         => abs($_POST['diferencia'][$key]),
          'precio_unitario'  => $_POST['precio_producto'][$key],
          'importe'          => (abs($_POST['diferencia'][$key])*$_POST['precio_producto'][$key]),
          'status'           => 'a',
          'fecha_aceptacion' => $fecha,
					);
			}
		}

		if(count($salida) > 0) //registra salida
		{
			$this->load->model('productos_salidas_model');

			$res_salidas = $this->db->query("SELECT cs.id_salida, Count(csp.id_salida) AS rows
          FROM compras_salidas AS cs
            LEFT JOIN compras_salidas_productos AS csp ON cs.id_salida = csp.id_salida
          WHERE status = 'n' AND Date(fecha_creacion) = '{$fecha}'
            AND cs.id_almacen = {$id_almacen} AND cs.id_empresa = {$_GET['did_empresa']}
          GROUP BY cs.id_salida")->row();

			$rows_salidas = 0;
			if (isset($res_salidas->rows) && $res_salidas->rows > 0) //ya existe una salida nivelacion en el dia
			{
				$rows_salidas = $res_salidas->rows;
				$id_salida    = $res_salidas->id_salida;
			}else
			{
				$res = $this->productos_salidas_model->agregar(array(
            'id_empresa'      => $_GET['did_empresa'],
						'id_almacen'      => $id_almacen,
						'id_empleado'     => $this->session->userdata('id_usuario'),
						'folio'           => 0,
						'concepto'        => 'Nivelacion de inventario',
						'status'          => 'n',
            'fecha_creacion'  => $fecha,
            'fecha_registro'  => $fecha,
					));
				$id_salida = $res['id_salida'];
			}
			foreach ($salida as $key => $value)
			{
				$rows_salidas++;
				$salida[$key]['id_salida'] = $id_salida;
				$salida[$key]['no_row']    = $rows_salidas;
			}
			$this->productos_salidas_model->agregarProductos($id_salida, $salida, false);
		}

		if (count($compra) > 0) //se registra una orden de compra
		{
			$this->load->model('compras_ordenes_model');

			$res_compra = $this->db->query("SELECT cs.id_orden, Count(csp.id_orden)
        FROM compras_ordenes AS cs
				  LEFT JOIN compras_productos AS csp ON cs.id_orden = csp.id_orden
				WHERE cs.status = 'n' AND Date(cs.fecha_aceptacion) = '{$fecha}'
          AND cs.id_almacen = {$id_almacen} AND cs.id_empresa = {$_GET['did_empresa']}
        GROUP BY cs.id_orden")->row();
			$rows_compras = 0;

			if (isset($res_compra->count)) //ya existe una salida nivelacion en el dia
			{
				$rows_compras = $res_compra->count;
				$id_orden    = $res_compra->id_orden;
			}else
			{
				$proveedor = $this->db->query("SELECT id_proveedor FROM proveedores WHERE UPPER(nombre_fiscal)='FICTICIO' LIMIT 1")->row();
				$departamento = $this->db->query("SELECT id_departamento FROM compras_departamentos WHERE UPPER(nombre)='FICTICIO' LIMIT 1")->row();
				$data = array(
					'id_empresa'      => $_GET['did_empresa'],
					'id_proveedor'    => $proveedor->id_proveedor,
					'id_departamento' => $departamento->id_departamento,
					'id_empleado'     => $this->session->userdata('id_usuario'),
          'id_almacen'      => $id_almacen,
					'folio'           => 0,
          'status'          => 'n',
					'tipo_orden'      => 'p',
					'autorizado'      => 't',
          'fecha_autorizacion' => $fecha,
          'fecha_aceptacion' => $fecha,
          'fecha_creacion' => $fecha,
				);

				$res = $this->compras_ordenes_model->agregarData($data);
				$id_orden = $res['id_orden'];
			}
			foreach ($compra as $key => $value)
			{
				$rows_compras++;
				$compra[$key]['id_orden'] = $id_orden;
				$compra[$key]['num_row']  = $rows_compras;
			}
			$this->compras_ordenes_model->agregarProductosData($compra);
		}

		return array('passes' => true, 'msg' => 3);
	}


	/**
	 * Reporte de existencias por clasificaciones
	 * @return [type] [description]
	 */
	public function getEClasifData()
	{
		$sql = '';

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-d"): $this->input->get('ffecha1');

		if($this->input->get('did_unidad') != ''){
			$sql .= " AND rpr.id_unidad = ".$this->input->get('did_unidad');
		}
		if($this->input->get('did_etiqueta') != ''){
			$sql .= " AND rpr.id_etiqueta = ".$this->input->get('did_etiqueta');
		}
		if($this->input->get('did_calibre') != ''){
			$sql .= " AND rpr.id_calibre = ".$this->input->get('did_calibre');
		}
    $sqlall = '';
    if($this->input->get('dcon_mov') == 'si'){
      $sqlall .= " AND (COALESCE(en.cajas, 0) - COALESCE(sa.cajas, 0)) > 0";
    }

		$res = $this->db->query(
			"SELECT c.id_clasificacion, c.nombre, COALESCE(en.cajas, 0) AS entradas, COALESCE(sa.cajas, 0) AS salidas,
				(COALESCE(en.cajas, 0) - COALESCE(sa.cajas, 0)) AS existencia, COALESCE(en.kilos, 0) AS entradas_kilos,
				COALESCE(sa.kilos, 0) AS salidas_kilos, (COALESCE(en.kilos, 0) - COALESCE(sa.kilos, 0)) AS existencia_kilos
			FROM clasificaciones AS c
			LEFT JOIN
			(
				SELECT rpr.id_clasificacion, Sum(rpr.cajas) AS cajas, (rrc.kilos * Sum(rpr.cajas)) AS kilos
				FROM rastria_pallets_rendimiento AS rpr
				INNER JOIN rastria_rendimiento_clasif AS rrc ON (rrc.id_rendimiento = rpr.id_rendimiento AND rrc.id_clasificacion = rpr.id_clasificacion AND rrc.id_unidad = rpr.id_unidad AND rrc.id_calibre = rpr.id_calibre AND rrc.id_etiqueta = rpr.id_etiqueta)
				WHERE Date(rpr.fecha) <= '{$_GET['ffecha1']}' {$sql}
				GROUP BY rpr.id_clasificacion, rrc.kilos
			) AS en ON en.id_clasificacion = c.id_clasificacion
			LEFT JOIN
			(
				SELECT rpr.id_clasificacion, Sum(rpr.cajas) AS cajas, (rrc.kilos * Sum(rpr.cajas)) AS kilos
				FROM facturacion AS f
					INNER JOIN facturacion_pallets AS fp ON f.id_factura = fp.id_factura
					INNER JOIN rastria_pallets_rendimiento AS rpr ON rpr.id_pallet = fp.id_pallet
					INNER JOIN rastria_rendimiento_clasif AS rrc ON rrc.id_rendimiento = rpr.id_rendimiento AND rrc.id_clasificacion = rpr.id_clasificacion AND rrc.id_unidad = rpr.id_unidad AND rrc.id_calibre = rpr.id_calibre AND rrc.id_etiqueta = rpr.id_etiqueta
				WHERE Date(f.fecha) <= '{$_GET['ffecha1']}' {$sql}
				GROUP BY rpr.id_clasificacion, rrc.kilos
			) AS sa ON sa.id_clasificacion = c.id_clasificacion
			WHERE c.status = 't' {$sqlall}
			ORDER BY nombre ASC
			");

		$response = array();
		if($res->num_rows() > 0)
		{
			$response = $res->result();
		}

		return $response;
	}
	/**
	 * Reporte existencias por clasificaciones pdf
	 */
	public function getEClasifPdf()
	{
		$res = $this->getEClasifData();

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Existencia de Clasificaciones';
		$pdf->titulo3 = " Al ".MyString::fechaAT($this->input->get('ffecha1'))."\n";
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'R', 'R', 'R', 'R', 'R', 'R');
		$widths = array(75, 20, 20, 20, 23, 23, 23);
		$header = array('Clasificacion', 'Entradas', 'Salidas', 'Existencia', 'Kg Entradas', 'Kg Salidas', 'Kg Existencia');

		$familia = '';
		$total_cargos = $total_abonos = $total_saldo = 0;
		foreach($res as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}

			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);
			$datos = array($item->nombre,
				MyString::formatoNumero($item->entradas, 2, '', false),
				MyString::formatoNumero($item->salidas, 2, '', false),
				MyString::formatoNumero(($item->existencia), 2, '', false),
				MyString::formatoNumero($item->entradas_kilos, 2, '', false),
				MyString::formatoNumero($item->salidas_kilos, 2, '', false),
				MyString::formatoNumero(($item->existencia_kilos), 2, '', false),
				);

			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}

		$pdf->Output('eclasif.pdf', 'I');
	}
}