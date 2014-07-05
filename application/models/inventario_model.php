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

	    $idsproveedores = '0';
	    if(is_array($this->input->get('ids_proveedores')))
	    	$idsproveedores = implode(',', $this->input->get('ids_proveedores'));

	    $proveedores = $this->db->query("SELECT id_proveedor, nombre_fiscal FROM proveedores WHERE id_proveedor IN({$idsproveedores})");
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
	    	$proveedor->productos = $productos->result();
	    	$response[] = $proveedor;
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
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
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
					String::formatoNumero($producto->cantidad, 2, '', false),
					$producto->abreviatura,
					String::formatoNumero($producto->importe, 2, '', false),
					String::formatoNumero($producto->impuestos, 2, '', false),
					String::formatoNumero(($producto->total), 2, '', false),
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
				String::formatoNumero($proveedor_cantidad, 2, '', false),
				'',
				String::formatoNumero($proveedor_importe, 2, '', false),
				String::formatoNumero($proveedor_impuestos, 2, '', false),
				String::formatoNumero(($proveedor_total), 2, '', false),
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
			String::formatoNumero($total_cantidad, 2, '', false),
			'',
			String::formatoNumero($total_importe, 2, '', false),
			String::formatoNumero($total_impuestos, 2, '', false),
			String::formatoNumero(($total_total), 2, '', false),
			);
		$pdf->SetXY(6, $pdf->GetY());
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->Row($datos, false);

		$pdf->Output('compras_proveedor.pdf', 'I');
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

      if ($this->input->get('dcon_mov') == 'si')
        $idsproveedores .= " AND COALESCE(cp.total, 0) > 0";

	    $response = array();
    	$productos = $this->db->query("SELECT p.id_producto, p.nombre, pu.abreviatura, COALESCE(cp.cantidad, 0) AS cantidad,
    				COALESCE(cp.importe, 0) AS importe, COALESCE(cp.impuestos, 0) AS impuestos, COALESCE(cp.total, 0) AS total
				FROM
					productos AS p LEFT JOIN (
						SELECT cp.id_producto, SUM(cp.cantidad) AS cantidad, SUM(cp.importe) AS importe, (SUM(cp.iva) - SUM(cp.retencion_iva)) AS impuestos, SUM(cp.total) AS total
						FROM compras AS c
							INNER JOIN compras_facturas AS cf ON c.id_compra = cf.id_compra
							INNER JOIN compras_productos AS cp ON cf.id_orden = cp.id_orden
						WHERE cp.id_producto IS NOT NULL {$sql} AND
							Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
						GROUP BY cp.id_producto
					) AS cp ON p.id_producto = cp.id_producto
    				INNER JOIN productos_unidades AS pu ON p.id_unidad = pu.id_unidad
    			{$idsproveedores}
				ORDER BY p.nombre ASC");
    	$response = $productos->result();

		return $response;
	}
	/**
	 * Reporte existencias por unidad pdf
	 */
	public function getCProductosPdf(){
		$res = $this->getCProductosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

		$pdf->titulo2 = 'Reporte de Compras por Producto';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'R', 'C', 'R', 'R', 'R');
		$widths = array(65, 30, 20, 30, 30, 30);
		$header = array('Nombre (Producto, Servicio)', 'Cantidad', 'Unidad', 'Neto', 'Impuestos', 'Total');

		$familia = '';
		$proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
		foreach($res as $key => $producto){
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
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
				String::formatoNumero($producto->cantidad, 2, '', false),
				$producto->abreviatura,
				String::formatoNumero($producto->importe, 2, '', false),
				String::formatoNumero($producto->impuestos, 2, '', false),
				String::formatoNumero(($producto->total), 2, '', false),
				);
			$pdf->SetXY(6, $pdf->GetY()-2);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->SetMyLinks(array( base_url('panel/inventario/cproducto_pdf?id_producto='.$producto->id_producto.'&'.String::getVarsLink(array('fproductor', 'ids_productos'))) ));
			$pdf->Row($datos, false, false);

			$proveedor_cantidad  += $producto->cantidad;
			$proveedor_importe   += $producto->importe;
			$proveedor_impuestos += $producto->impuestos;
			$proveedor_total     += $producto->total;

		}
		$datos = array('Total General',
			String::formatoNumero($proveedor_cantidad, 2, '', false),
			'',
			String::formatoNumero($proveedor_importe, 2, '', false),
			String::formatoNumero($proveedor_impuestos, 2, '', false),
			String::formatoNumero(($proveedor_total), 2, '', false),
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
   * @return
   */
  public function getCUnProductosData()
  {
    $sql = '';
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
              productos AS p LEFT JOIN (
                SELECT cp.id_producto, c.serie, c.folio, Date(c.fecha) AS fecha, cp.cantidad, cp.importe,
                  (cp.iva - cp.retencion_iva) AS impuestos, cp.total, cp.precio_unitario, pr.nombre_fiscal AS proveedor
                FROM compras AS c
                  INNER JOIN compras_facturas AS cf ON c.id_compra = cf.id_compra
                  INNER JOIN compras_productos AS cp ON cf.id_orden = cp.id_orden
                  INNER JOIN proveedores AS pr ON pr.id_proveedor = c.id_proveedor
                WHERE cp.id_producto IS NOT NULL {$idsproveedores2} AND cp.id_producto = {$product} {$sql} AND
                  Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
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

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de seguimientos x Producto';
    // $pdf->titulo3 = (isset($res[0]->nombre)?'PRODUCTO: '.$res[0]->nombre:'')."\n";
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
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
          $datos = array($producto->fecha,
            $producto->serie.' '.$producto->folio,
            $producto->proveedor,
            $producto->cantidad.' '.$producto->abreviatura,
            String::formatoNumero($producto->precio_unitario, 2, '', false),
            String::formatoNumero($producto->impuestos, 2, '', false),
            String::formatoNumero(($producto->total), 2, '', false),
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
          String::formatoNumero(($proveedor_total), 2, '', false),
          );
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'R'));
        $pdf->SetWidths(array(170, 36));
        $pdf->Row($datos, false, false);
      }
    }
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total General',
      String::formatoNumero(($total_general), 2, '', false),
      );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(170, 36));
    $pdf->Row($datos, false, false);

    $pdf->Output('compras_proveedor.pdf', 'I');
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

	    $idsproveedores = $this->input->get('id_producto');

	    $response = array();
    	$productos = $this->db->query("SELECT p.id_producto, p.codigo, p.nombre, pu.abreviatura, COALESCE(cp.cantidad, 0) AS cantidad,
				COALESCE(cp.importe, 0) AS importe, COALESCE(cp.impuestos, 0) AS impuestos, COALESCE(cp.total, 0) AS total,
				cp.fecha, cp.serie, cp.folio, cp.fechao, cp.folioo, cp.id_compra, cp.id_orden
			FROM
				productos AS p LEFT JOIN (
					SELECT cp.id_producto, c.id_compra, Date(c.fecha) AS fecha, c.serie, c.folio, co.id_orden, Date(co.fecha_autorizacion) AS fechao, co.folio AS folioo,
						cp.cantidad, cp.importe, (cp.iva - cp.retencion_iva) AS impuestos, cp.total
					FROM compras AS c
						INNER JOIN compras_facturas AS cf ON c.id_compra = cf.id_compra
						INNER JOIN compras_ordenes AS co ON cf.id_orden = co.id_orden
						INNER JOIN compras_productos AS cp ON co.id_orden = cp.id_orden
					WHERE cp.id_producto = {$idsproveedores} {$sql} AND
						Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
				) AS cp ON p.id_producto = cp.id_producto
				INNER JOIN productos_unidades AS pu ON p.id_unidad = pu.id_unidad
			WHERE p.id_producto = {$idsproveedores}
				ORDER BY p.nombre ASC");

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
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
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
			$pdf->Row(array($producto->fechao,
				'',
				String::formatoNumero($producto->folioo, 0, '', false),
				'Orden de Compra',
				String::formatoNumero($producto->cantidad, 2, '', false),
				String::formatoNumero(0, 2, '', false),
				), false, false);

			$pdf->SetXY(6, $pdf->GetY()-2);
			$pdf->SetMyLinks(array('','','', base_url('panel/inventario/cseguimiento_pdf?id_compra='.$producto->id_compra.
							'&id_orden='.$producto->id_orden.'&'.String::getVarsLink(array('id_orden', 'id_compra'))) ));
			$pdf->Row(array($producto->fecha,
				$producto->serie,
				String::formatoNumero($producto->folio, 0, '', false),
				'Compra',
				String::formatoNumero(0, 2, '', false),
				String::formatoNumero($producto->cantidad, 2, '', false),
				), false, false);

			$proveedor_cantidad  += $producto->cantidad;

		}
		$datos = array('Total General',
			'', '', '',
			String::formatoNumero($proveedor_cantidad, 2, '', false),
			String::formatoNumero(($proveedor_cantidad), 2, '', false),
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
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
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
			String::fechaATexto(substr($res['compra']->fecha, 0, 10), '/c'),
			$res['compra']->serie,
			$res['compra']->folio,
			'Compra',
			$res['orden']->proveedor,
			String::fechaATexto(substr($res['orden']->fecha_aceptacion, 0, 10), '/c'),
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
					String::formatoNumero($value->cantidad, 2, '', false),
					String::formatoNumero($value->cantidad, 2, '', false),
					String::formatoNumero($value->importe+$value->iva, 2, '', false),
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
					String::formatoNumero($cantidad, 2, '', false),
					String::formatoNumero($cantidad, 2, '', false),
					String::formatoNumero($total, 2, '', false),
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
	public function getEPUData()
  {
		$sql = '';

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

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
			"SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura, COALESCE(co.cantidad, 0) AS entradas, COALESCE(sa.cantidad, 0) AS salidas,
				(COALESCE(sal_co.cantidad, 0) - COALESCE(sal_sa.cantidad, 0)) AS saldo_anterior, p.stock_min
			FROM productos AS p
			INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
			INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
			LEFT JOIN
			(
				SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
				FROM compras_ordenes AS co
				INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
				WHERE co.status <> 'ca' AND co.tipo_orden = 'p' AND cp.status = 'a' AND Date(cp.fecha_aceptacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
				GROUP BY cp.id_producto
			) AS co ON co.id_producto = p.id_producto
			LEFT JOIN
			(
				SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
				FROM compras_salidas AS sa
				INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
				WHERE sa.status <> 'ca' AND Date(sa.fecha_registro) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
				GROUP BY sp.id_producto
			) AS sa ON sa.id_producto = p.id_producto
			LEFT JOIN
			(
				SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
				FROM compras_ordenes AS co
				INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
				WHERE co.status <> 'ca' AND co.tipo_orden = 'p' AND cp.status = 'a' AND Date(cp.fecha_aceptacion) < '{$fecha}'
				GROUP BY cp.id_producto
			) AS sal_co ON sal_co.id_producto = p.id_producto
			LEFT JOIN
			(
				SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
				FROM compras_salidas AS sa
				INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
				WHERE sa.status <> 'ca' AND Date(sa.fecha_registro) < '{$fecha}'
				GROUP BY sp.id_producto
			) AS sal_sa ON sal_sa.id_producto = p.id_producto
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
	    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');

	    if ($empresa['info']->logo !== '')
	      $pdf->logo = $empresa['info']->logo;

	    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
		$pdf->titulo2 = 'Existencia por unidades';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'R', 'R', 'R', 'R');
		$widths = array(65, 35, 35, 35, 35);
		$header = array('Producto', 'Saldo', 'Entradas', 'Salidas', 'Existencia');

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
						String::formatoNumero($totales['familia'][0], 2, '', false),
						String::formatoNumero($totales['familia'][1], 2, '', false),
						String::formatoNumero($totales['familia'][2], 2, '', false),
						String::formatoNumero($totales['familia'][3], 2, '', false),
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
					String::formatoNumero($item->saldo_anterior, 2, '', false),
					String::formatoNumero($item->entradas, 2, '', false),
					String::formatoNumero($item->salidas, 2, '', false),
					String::formatoNumero($existencia, 2, '', false),
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
			String::formatoNumero($totales['familia'][0], 2, '', false),
			String::formatoNumero($totales['familia'][1], 2, '', false),
			String::formatoNumero($totales['familia'][2], 2, '', false),
			String::formatoNumero($totales['familia'][3], 2, '', false),
			), true, false);

		$pdf->SetXY(6, $pdf->GetY()+5);
		$pdf->Row(array('GENERAL',
			String::formatoNumero($totales['general'][0], 2, '', false),
			String::formatoNumero($totales['general'][1], 2, '', false),
			String::formatoNumero($totales['general'][2], 2, '', false),
			String::formatoNumero($totales['general'][3], 2, '', false),
			), false, true);

		$pdf->Output('epu.pdf', 'I');
	}

	public function cuentasPagarExcel(){
		$res = $this->getCuentasPagarData(60);

		$this->load->library('myexcel');
		$xls = new myexcel();

		$worksheet =& $xls->workbook->addWorksheet();

		$xls->titulo2 = 'Cuentas por pagar';
		$xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$xls->titulo4 = ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': $this->input->get('ftipo') == 'pp'? 'Pendientes por pagar': 'Todas');

		$data_fac = $res['cuentas'];

		$row=0;
		//Header
		$xls->excelHead($worksheet, $row, 8, array(
				array($xls->titulo2, 'format_title2'),
				array($xls->titulo3, 'format_title3'),
				array($xls->titulo4, 'format_title3')
		));

		$row +=3;
		$xls->excelContent($worksheet, $row, $data_fac, array(
				'head' => array('Cliente', 'Cargos', 'Abonos', 'Saldo'),
				'conte' => array(
						array('name' => 'nombre', 'format' => 'format4', 'sum' => -1),
						array('name' => 'total', 'format' => 'format4', 'sum' => 0),
						array('name' => 'abonos', 'format' => 'format4', 'sum' => 0),
						array('name' => 'saldo', 'format' => 'format4', 'sum' => 0),
					)
		));

		foreach ($data_fac as $key => $cuenta) {
			$_GET['id_proveedor'] = $cuenta->id_proveedor;
			$this->cuentaProveedorExcel($xls, false);
		}

		$xls->workbook->send('cuentas_pagar.xls');
		$xls->workbook->close();
	}


  /**
   * Reporte costos ueps
   * @param  [type] $id_producto [description]
   * @param  [type] $fecha1      [description]
   * @param  [type] $fecha2      [description]
   * @return [type]              [description]
   */
  public function uepsData($id_producto, $fecha1, $fecha2)
  {
    $res = $this->db->query(
    "SELECT id_producto, Date(fecha) AS fecha, cantidad, precio_unitario, importe, tipo
    FROM
      (
        (
        SELECT cp.id_producto, cp.num_row, cp.fecha_aceptacion AS fecha, cp.cantidad, cp.precio_unitario, cp.importe, 'c' AS tipo
        FROM compras_ordenes AS co
        INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        WHERE cp.id_producto = {$id_producto} AND co.status <> 'ca' AND cp.status = 'a'
          AND co.tipo_orden = 'p' AND Date(cp.fecha_aceptacion) <= '{$fecha2}'
        )
        UNION ALL
        (
        SELECT sp.id_producto, sp.no_row AS num_row, sa.fecha_registro AS fecha, sp.cantidad, sp.precio_unitario, (sp.cantidad * sp.precio_unitario) AS importe, 's' AS tipo
        FROM compras_salidas AS sa
        INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
        WHERE sp.id_producto = {$id_producto} AND sa.status <> 'ca' AND Date(sa.fecha_registro) <= '{$fecha2}'
        )
      ) AS t
    ORDER BY fecha ASC");

    $result   = array();
    $result[] = array('fecha' => 'S. Anterior',
            'entrada' => array(0, 0, 0, 0),
            'salida' => array(0, 0, 0, 0),
            'saldo' => array(0, 0, 0, 0), );
    foreach ($res->result() as $key => $value)
    {
      $row = array('fecha' => $value->fecha, 'entrada' => array('', '', '', ''), 'salida' => array('', '', ''), 'saldo' => array(0, 0, 0));
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
      }elseif ($value->tipo == 's')
      {
        $aux_cantidad = $value->cantidad;
        $row = NULL;
        for ($ci = count($result)-1; $ci >= 0; --$ci)
        {
          $row = array('fecha' => $value->fecha, 'misma_salida' => ($row==NULL? '' : '&&'), 'entrada' => array('', '', '', ''), 'salida' => array('', '', ''), 'saldo' => array(0, 0, 0));
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
          if(floatval(String::formatoNumero($aux_cantidad, 2, '', true)) == 0)
            break;
        }
      }

    }

    $valkey = $entro = 0;
    foreach ($result as $key => $value)
    {
      if(strtotime($fecha1) > strtotime($value['fecha']))
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
        $data = $this->uepsData($value->id_producto, $_GET['ffecha1'], $fecha);
        $value->data       = array_pop($data);
        $value->data_saldo = array_shift($data);
        $response[$key]    = $value;
      }
    }

    return $response;
  }
  /**
   * Reporte existencias por costo UEPS
   */
  public function getUEPSPdf()
  {
    $res = $this->getUEPSData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Existencia por costos UEPS';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
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
            String::formatoNumero($totaltes['familia'][0], 2, '$', false),
            String::formatoNumero($totaltes['familia'][1] , 2, '$', false),
            String::formatoNumero($totaltes['familia'][2], 2, '$', false),
            String::formatoNumero($totaltes['familia'][3], 2, '$', false),
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
          String::formatoNumero($item->data_saldo['saldo'][2], 2, '$', false),
          String::formatoNumero( ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]) , 2, '$', false),
          String::formatoNumero($item->data['salida'][2], 2, '$', false),
          String::formatoNumero(($item->data['saldo'][2]), 2, '$', false),
          );

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->SetMyLinks(array(base_url('panel/inventario/pueps_pdf?id_producto='.$item->id_producto.'&id_empresa='.$empresa['info']->id_empresa.
                  '&ffecha1='.$this->input->get('ffecha1').'&ffecha2='.$this->input->get('ffecha2'))));
        $pdf->Row($datos, false);
      }

      $pdf->SetMyLinks(array());
    }

    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetMyLinks(array());
    $pdf->Row(array('TOTAL',
      String::formatoNumero($totaltes['familia'][0], 2, '$', false),
      String::formatoNumero($totaltes['familia'][1] , 2, '$', false),
      String::formatoNumero($totaltes['familia'][2], 2, '$', false),
      String::formatoNumero($totaltes['familia'][3], 2, '$', false),
      ), false, false);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array('TOTAL GENERAL',
      String::formatoNumero($totaltes['familia'][0], 2, '$', false),
      String::formatoNumero($totaltes['familia'][1] , 2, '$', false),
      String::formatoNumero($totaltes['familia'][2], 2, '$', false),
      String::formatoNumero($totaltes['familia'][3], 2, '$', false),
      ), false);

    $pdf->Output('epc.pdf', 'I');
  }

  public function getPUEPSPdf()
  {
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

    $res = $this->uepsData($_GET['id_producto'], $_GET['ffecha1'], $_GET['ffecha2']);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('id_empresa'));


    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte de inventario costo UEPS';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
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
        $item['fecha'],

        $item['entrada'][0]!=''? String::formatoNumero($item['entrada'][0], 2, '', false): $item['entrada'][0],
        $item['entrada'][1]!=''? String::formatoNumero($item['entrada'][1], 2, '$', false): $item['entrada'][1],
        $item['entrada'][2]!=''? String::formatoNumero($item['entrada'][2], 2, '$', false): $item['entrada'][2],

        $item['salida'][0]!=''? String::formatoNumero($item['salida'][0], 2, '', false): $item['salida'][0],
        $item['salida'][1]!=''? String::formatoNumero($item['salida'][1], 2, '$', false): $item['salida'][1],
        $item['salida'][2]!=''? String::formatoNumero($item['salida'][2], 2, '$', false): $item['salida'][2],

        $item['saldo'][0]!=''? String::formatoNumero($item['saldo'][0], 2, '', false): $item['saldo'][0],
        $item['saldo'][1]!=''? String::formatoNumero($item['saldo'][1], 2, '$', false): $item['saldo'][1],
        $item['saldo'][2]!=''? String::formatoNumero($item['saldo'][2], 2, '$', false): $item['saldo'][2],
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

		$response = array();
		if($res->num_rows() > 0)
		{
			$response = $res->result();
			foreach ($response as $key => $value)
			{
				$data = $this->promedioData($value->id_producto, $_GET['ffecha1'], $fecha);
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
		$empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');

		if ($empresa['info']->logo !== '')
		  $pdf->logo = $empresa['info']->logo;

		$pdf->titulo1 = $empresa['info']->nombre_fiscal;

		$pdf->titulo2 = 'Existencia por costos';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
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
						String::formatoNumero($totaltes['familia'][0], 2, '$', false),
						String::formatoNumero($totaltes['familia'][1] , 2, '$', false),
						String::formatoNumero($totaltes['familia'][2], 2, '$', false),
						String::formatoNumero($totaltes['familia'][3], 2, '$', false),
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
					String::formatoNumero($item->data_saldo['saldo'][2], 2, '$', false),
					String::formatoNumero( ($item->data['entrada'][2] - $item->data_saldo['saldo'][2]) , 2, '$', false),
					String::formatoNumero($item->data['salida'][2], 2, '$', false),
					String::formatoNumero(($item->data['saldo'][2]), 2, '$', false),
					);

				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->SetMyLinks(array(base_url('panel/inventario/promedio_pdf?id_producto='.$item->id_producto.'&id_empresa='.$empresa['info']->id_empresa.
									'&ffecha1='.$this->input->get('ffecha1').'&ffecha2='.$this->input->get('ffecha2'))));
				$pdf->Row($datos, false);
			}

			$pdf->SetMyLinks(array());
		}

		$pdf->SetX(6);
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->SetMyLinks(array());
		$pdf->Row(array('TOTAL',
			String::formatoNumero($totaltes['familia'][0], 2, '$', false),
			String::formatoNumero($totaltes['familia'][1] , 2, '$', false),
			String::formatoNumero($totaltes['familia'][2], 2, '$', false),
			String::formatoNumero($totaltes['familia'][3], 2, '$', false),
			), false, false);

		$pdf->SetXY(6, $pdf->GetY()+5);
		$pdf->Row(array('TOTAL GENERAL',
			String::formatoNumero($totaltes['familia'][0], 2, '$', false),
			String::formatoNumero($totaltes['familia'][1] , 2, '$', false),
			String::formatoNumero($totaltes['familia'][2], 2, '$', false),
			String::formatoNumero($totaltes['familia'][3], 2, '$', false),
			), false);

		$pdf->Output('epc.pdf', 'I');
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
      "SELECT DISTINCT(fecha), u.nombre
      FROM (
        SELECT Date(fecha_creacion) AS fecha, id_empleado FROM compras_salidas WHERE status = 'n' AND id_empresa = {$_GET['did_empresa']}
          AND Date(fecha_creacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
        UNION
        SELECT Date(fecha_aceptacion) AS fecha, id_empleado FROM compras_ordenes WHERE status = 'n' AND id_empresa = {$_GET['did_empresa']}
          AND Date(fecha_aceptacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
      ) t
      INNER JOIN usuarios u ON u.id = t.id_empleado
      ORDER BY fecha ASC");

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
                WHERE co.status = 'n' AND Date(co.fecha_aceptacion) = '{$value->fecha}' AND co.id_empresa = {$_GET['did_empresa']}
                UNION
                SELECT cp.id_producto, 0 AS entrada, cp.cantidad AS salida
                FROM compras_salidas cs
                  INNER JOIN compras_salidas_productos cp ON cs.id_salida = cp.id_salida
                WHERE cs.status = 'n' AND Date(cs.fecha_creacion) = '{$value->fecha}' AND cs.id_empresa = {$_GET['did_empresa']}
              ) es
              GROUP BY id_producto
            ) AS es
            INNER JOIN productos p ON p.id_producto = es.id_producto
            INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
            {$sql}");
        $value->datos = $res_cosa->result();
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
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'R', 'R', 'R');
    $widths = array(80, 35, 35, 35, 35);
    $header = array('Producto', 'Entradas', 'Salidas');

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
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(30, 50));
      $pdf->Row(array($item->fecha, $item->nombre), false, false);

      $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);

      foreach ($item->datos as $key2 => $prod)
      {
        $datos = array($prod->nombre.' ('.$prod->abreviatura.')',
          String::formatoNumero($prod->entrada, 2, '', false),
          String::formatoNumero($prod->salida, 2, '', false),
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
    //   String::formatoNumero($totaltes['familia'][0], 2, '$', false),
    //   String::formatoNumero($totaltes['familia'][1] , 2, '$', false),
    //   String::formatoNumero($totaltes['familia'][2], 2, '$', false),
    //   String::formatoNumero($totaltes['familia'][3], 2, '$', false),
    //   ), false, false);

    // $pdf->SetXY(6, $pdf->GetY()+5);
    // $pdf->Row(array('TOTAL GENERAL',
    //   String::formatoNumero($totaltes['familia'][0], 2, '$', false),
    //   String::formatoNumero($totaltes['familia'][1] , 2, '$', false),
    //   String::formatoNumero($totaltes['familia'][2], 2, '$', false),
    //   String::formatoNumero($totaltes['familia'][3], 2, '$', false),
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
	public function promedioData($id_producto, $fecha1, $fecha2)
	{
		$res = $this->db->query(
		"SELECT id_producto, Date(fecha) AS fecha, cantidad, precio_unitario, importe, tipo
		FROM
			(
				(
				SELECT cp.id_producto, cp.num_row, cp.fecha_aceptacion AS fecha, cp.cantidad, cp.precio_unitario, cp.importe, 'c' AS tipo
				FROM compras_ordenes AS co
				INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
				WHERE cp.id_producto = {$id_producto} AND co.status <> 'ca' AND cp.status = 'a'
					AND co.tipo_orden = 'p' AND Date(cp.fecha_aceptacion) <= '{$fecha2}'
				)
				UNION ALL
				(
				SELECT sp.id_producto, sp.no_row AS num_row, sa.fecha_registro AS fecha, sp.cantidad, sp.precio_unitario, (sp.cantidad * sp.precio_unitario) AS importe, 's' AS tipo
				FROM compras_salidas AS sa
				INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
				WHERE sp.id_producto = {$id_producto} AND sa.status <> 'ca' AND Date(sa.fecha_registro) <= '{$fecha2}'
				)
			) AS t
		ORDER BY fecha ASC");

		$result   = array();
		$result[] = array('fecha' => 'S. Anterior',
						'entrada' => array(0, 0, 0),
						'salida' => array(0, 0, 0),
						'saldo' => array(0, 0, 0), );
		foreach ($res->result() as $key => $value)
		{
			$row = array('fecha' => $value->fecha, 'entrada' => array('', '', ''), 'salida' => array('', '', ''), 'saldo' => array(0, 0, 0));
			if ($value->tipo == 'c')
			{
				$row['entrada'][0] = $value->cantidad;
				$row['entrada'][1] = $value->precio_unitario;
				$row['entrada'][2] = $value->cantidad*$value->precio_unitario;

				$row['saldo'][0] = $value->cantidad+$result[$key]['saldo'][0];
				$row['saldo'][2] = $row['entrada'][2]+$result[$key]['saldo'][2];
				$row['saldo'][1] = $row['saldo'][2]/($row['saldo'][0]==0? 1: $row['saldo'][0]);
			}else
			{
				$row['salida'][0] = $value->cantidad;
				$row['salida'][1] = $result[$key]['saldo'][1];
				$row['salida'][2] = $value->cantidad*$row['salida'][1];

				$row['saldo'][0] = $result[$key]['saldo'][0]-$value->cantidad;
				$row['saldo'][1] = $result[$key]['saldo'][1];
				$row['saldo'][2] = $row['saldo'][0]*$row['saldo'][1];
			}

			$result[] = $row;
		}

		$valkey = $entro = 0;
		foreach ($result as $key => $value)
		{
			if($fecha1 > $value['fecha'])
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

		$res = $this->promedioData($_GET['id_producto'], $_GET['ffecha1'], $_GET['ffecha2']);

		$this->load->model('empresas_model');
		$empresa = $this->empresas_model->getInfoEmpresa($this->input->get('id_empresa'));


		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');

		if ($empresa['info']->logo !== '')
		  $pdf->logo = $empresa['info']->logo;

		$pdf->titulo1 = $empresa['info']->nombre_fiscal;
		$pdf->titulo2 = 'Reporte de inventario costo promedio';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
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
				$item['fecha'],

				$item['entrada'][0]!=''? String::formatoNumero($item['entrada'][0], 2, '', false): $item['entrada'][0],
				$item['entrada'][1]!=''? String::formatoNumero($item['entrada'][1], 2, '$', false): $item['entrada'][1],
				$item['entrada'][2]!=''? String::formatoNumero($item['entrada'][2], 2, '$', false): $item['entrada'][2],

				$item['salida'][0]!=''? String::formatoNumero($item['salida'][0], 2, '', false): $item['salida'][0],
				$item['salida'][1]!=''? String::formatoNumero($item['salida'][1], 2, '$', false): $item['salida'][1],
				$item['salida'][2]!=''? String::formatoNumero($item['salida'][2], 2, '$', false): $item['salida'][2],

				$item['saldo'][0]!=''? String::formatoNumero($item['saldo'][0], 2, '', false): $item['saldo'][0],
				$item['saldo'][1]!=''? String::formatoNumero($item['saldo'][1], 2, '$', false): $item['saldo'][1],
				$item['saldo'][2]!=''? String::formatoNumero($item['saldo'][2], 2, '$', false): $item['saldo'][2],
				);

			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}

		$pdf->Output('promedio.pdf', 'I');
	}


	public function getNivelarData($id_familia)
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
		$sql .= " AND p.id_familia = ".$id_familia;

	    $query = BDUtil::pagination(
	    	"SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura
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
				$data = $this->promedioData($value->id_producto, $fecha, $fecha);
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

			$res_salidas = $this->db->query("SELECT cs.id_salida, Count(csp.id_salida)
          FROM compras_salidas AS cs
            LEFT JOIN compras_salidas_productos AS csp ON cs.id_salida = csp.id_salida
          WHERE status = 'n' AND Date(fecha_creacion) = '{$fecha}' GROUP BY cs.id_salida")->row();

			$rows_salidas = 0;
			if (isset($res_salidas->count)) //ya existe una salida nivelacion en el dia
			{
				$rows_salidas = $res_salidas->count;
				$id_salida    = $res_salidas->id_salida;
			}else
			{
				$res = $this->productos_salidas_model->agregar(array(
						'id_empresa'      => $_GET['did_empresa'],
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
			$this->productos_salidas_model->agregarProductos($id_salida, $salida);
		}

		if (count($compra) > 0) //se registra una orden de compra
		{
			$this->load->model('compras_ordenes_model');

			$res_compra = $this->db->query("SELECT cs.id_orden, Count(csp.id_orden)
        FROM compras_ordenes AS cs
				  LEFT JOIN compras_productos AS csp ON cs.id_orden = csp.id_orden
				WHERE cs.status = 'n' AND Date(cs.fecha_aceptacion) = '{$fecha}' GROUP BY cs.id_orden")->row();
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
					'folio'           => 0,
					'status'          => 'n',
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
		$pdf->titulo3 = " Al ".$this->input->get('ffecha1')."\n";
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
				String::formatoNumero($item->entradas, 2, '', false),
				String::formatoNumero($item->salidas, 2, '', false),
				String::formatoNumero(($item->existencia), 2, '', false),
				String::formatoNumero($item->entradas_kilos, 2, '', false),
				String::formatoNumero($item->salidas_kilos, 2, '', false),
				String::formatoNumero(($item->existencia_kilos), 2, '', false),
				);

			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}

		$pdf->Output('eclasif.pdf', 'I');
	}
}