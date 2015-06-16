<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad_pallets_model extends privilegios_model {


	function __construct()
	{
		parent::__construct();
    $this->load->model('bitacora_model');
	}

	public function getPallets($paginados = true)
	{
		$sql = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '40',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}
		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql = "WHERE ( lower(c.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%'
								".(is_numeric($this->input->get('fnombre'))? " OR rp.folio = '".$this->input->get('fnombre')."'": '')." )";

		if($this->input->get('ffecha') != '')
			$sql .= ($sql==''? 'WHERE': ' AND')." Date(rp.fecha) = '".$this->input->get('ffecha')."'";

		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." rp.status = '".$this->input->get('fstatus')."'";

		if($this->input->get('parea') != '')
			$sql .= ($sql==''? 'WHERE': ' AND')." rp.id_area = '".$this->input->get('parea')."'";

		$query = BDUtil::pagination("SELECT
					rp.id_pallet, rp.folio, Date(rp.fecha) AS fecha, rp.no_cajas, Coalesce(Sum(rpr.cajas), 0) AS cajas,
					c.nombre_fiscal
				FROM rastria_pallets AS rp
					LEFT JOIN rastria_pallets_rendimiento AS rpr ON rp.id_pallet = rpr.id_pallet
					LEFT JOIN clientes AS c ON rp.id_cliente = c.id_cliente
				{$sql}
				GROUP BY rp.id_pallet, rp.folio, rp.fecha, rp.no_cajas, c.nombre_fiscal
				ORDER BY folio DESC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'pallets'      => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0)
			$response['pallets'] = $res->result();

		$res->free_result();

		return $response;
	}

	public function getInfoPallet($id_pallet, $basic_info=FALSE, $cajas_libres=true){
		$result = $this->db->query("SELECT * FROM rastria_pallets_lista WHERE id_pallet = {$id_pallet}");
		$response['info'] = array();
		if($result->num_rows() > 0)
		{
			$response['info'] = $result->row();
			$result->free_result();

			if(!$basic_info)
			{
				$result = $this->db->query("SELECT rpr.id_pallet, rr.id_rendimiento, c.id_clasificacion, c.nombre, rr.lote, rr.lote_ext, to_char(rr.fecha, 'DD-MM-YYYY') AS fecha, rpr.cajas,
						u.id_unidad, u.nombre AS unidad, cal.id_calibre, cal.nombre AS calibre, e.id_etiqueta, e.nombre AS etiqueta, sz.id_calibre AS id_size, sz.nombre AS size,
						rpr.kilos, c.iva as iva_clasificacion, c.id_unidad as id_unidad_clasificacion, rr.certificado
					FROM rastria_pallets_rendimiento AS rpr
						INNER JOIN rastria_rendimiento AS rr ON rpr.id_rendimiento = rr.id_rendimiento
						INNER JOIN clasificaciones AS c ON c.id_clasificacion = rpr.id_clasificacion
						INNER JOIN unidades AS u ON rpr.id_unidad = u.id_unidad
						INNER JOIN calibres AS cal ON rpr.id_calibre = cal.id_calibre
						INNER JOIN etiquetas AS e ON rpr.id_etiqueta = e.id_etiqueta
						LEFT JOIN calibres AS sz ON rpr.id_size = sz.id_calibre
					WHERE id_pallet = {$id_pallet}");
				$response['rendimientos'] = $result->result();
        $response['info']->calibre_fijo_origen = ($response['info']->calibre_fijo==''? NULL: $response['info']->calibre_fijo);
        if($response['info']->calibre_fijo == '')
        {
          $nombre_calibres = array();
          foreach ($response['rendimientos'] as $key => $value)
          {
            if( ! isset($nombre_calibres[$value->size]) )
              $nombre_calibres[$value->size] = 1;
          }
          $response['info']->calibre_fijo = implode(',', array_keys($nombre_calibres));
        }

				// if($cajas_libres){
				// 	$rendimientos_libres     = $this->getRendimientoLibre($response['info']->id_clasificacion);
				// 	$response['rend_libres'] = $rendimientos_libres['rendimientos'];
				// }

				//lista calibres
				$response['calibres'] = array();
				$data_calibres = $this->db->query("SELECT rpc.id_pallet, rpc.id_calibre, c.nombre
													FROM rastria_pallets_calibres AS rpc
														INNER JOIN calibres AS c ON c.id_calibre = rpc.id_calibre
													WHERE id_pallet = {$id_pallet}");
				if($data_calibres->num_rows() > 0)
					$response['calibres'] = $data_calibres->result();

        //Salidas de productos
        $response['psalidas'] = array();
        $data_calibres = $this->db->query("SELECT p.id_producto, p.nombre, rps.cantidad, rps.nom_row
                          FROM rastria_pallets_salidas AS rps
                            LEFT JOIN productos AS p ON p.id_producto = rps.id_producto
                          WHERE rps.id_pallet = {$id_pallet} ORDER BY rps.nom_row ASC");
        $aux_num_row = 1;
        if($data_calibres->num_rows() > 0) {
          $response['psalidas'] = $data_calibres->result();
          foreach ($response['psalidas'] as $key => $ps) {
            if($aux_num_row != $ps->nom_row) {
              $response['psalidas2'][] = count($psalidas)>1? $psalidas: $psalidas[0];
              $psalidas = array();
              $aux_num_row = $ps->nom_row;
            }
            $psalidas[] = $ps;
          }

          if(count($psalidas) > 0) {
            $response['psalidas2'][] = count($psalidas)>1? $psalidas: $psalidas[0];
          }
        }

				//Info cliente
        $response['cliente'] = array();
        if ($response['info']->id_cliente !== null)
        {
          $this->load->model('clientes_model');
          $data_cliente        = $this->clientes_model->getClienteInfo($response['info']->id_cliente, true);
          $response['cliente'] = $data_cliente['info'];
        }
			}
		}
		return $response;
	}

	public function calibreSelec($id_calibre, $selecs)
	{
		$res = false;
		foreach ($selecs as $key => $value)
		{
		  if($id_calibre == $value->id_calibre)
		  {
		  	$res = true;
		  	break;
		  }
		}
		return $res;
	}

	/**
	 * Obtiene el siguiente folio para el pallet
	 * @return [type] [description]
	 */
 	public function getNextFolio($id_area){
 		$result = $this->db->query("SELECT (folio+1) AS folio FROM rastria_pallets WHERE id_area = {$id_area} ORDER BY folio DESC LIMIT 1")->row();
 		return (is_object($result)? $result->folio: '1');
 	}

 	/**
 	 * Obtiene la lista de rendimientos con cajas disponibles para agregarlos a los pallets
 	 * de una clasificacion espesifica
 	 * @param  [type] $id_clasificacion [description]
 	 * @return [type]                   [description]
 	 */
 	public function getRendimientoLibre($id_clasificacion, $idunidad, $idcalibre, $idetiqueta){
		$sql = $idunidad!=''? ' AND rcl.id_unidad = '.$idunidad: '';
		$sql .= $idcalibre!=''? ' AND rcl.id_calibre = '.$idcalibre: '';
		$sql .= $idetiqueta!=''? ' AND rcl.id_etiqueta = '.$idetiqueta: '';
 		$result = $this->db->query("SELECT rr.id_rendimiento, rr.lote, to_char(rr.fecha, 'DD-MM-YYYY') AS fecha, rcl.rendimiento, rcl.cajas, rcl.libres,
 										rcl.kilos, u.id_unidad, u.nombre AS unidad, c.id_calibre, c.nombre AS calibre, e.id_etiqueta, e.nombre AS etiqueta,
 										rcl.id_clasificacion, sz.id_calibre AS id_size, sz.nombre AS size
 		                           FROM rastria_rendimiento AS rr
									INNER JOIN rastria_cajas_libres AS rcl ON rr.id_rendimiento = rcl.id_rendimiento
									LEFT JOIN unidades AS u ON rcl.id_unidad = u.id_unidad
									LEFT JOIN calibres AS c ON rcl.id_calibre = c.id_calibre
									LEFT JOIN etiquetas AS e ON rcl.id_etiqueta = e.id_etiqueta
									LEFT JOIN calibres AS sz ON rcl.id_size = sz.id_calibre
 		                           WHERE rcl.id_clasificacion = {$id_clasificacion} {$sql}
 		                           ORDER BY fecha DESC, lote ASC, unidad ASC");
 		$response = array('rendimientos' => array());
		if($result->num_rows() > 0)
			$response['rendimientos'] = $result->result();

		$result->free_result();

		return $response;
 	}

 	/**
 	 * Agregar un pallet a la bd
 	 * @param [type] $data array con los valores a insertar
 	 */
 	public function addPallet($data=NULL)
 	{
 		if ($data==NULL)
		{
			$data = array(
						// 'id_clasificacion' => $this->input->post('fid_clasificacion'),
						'folio'        => $this->input->post('ffolio'),
						'no_cajas'     => $this->input->post('fcajas'),
						'no_hojas'     => 0,
						'kilos_pallet' => $this->input->post('fkilos'),
						'id_area'      => $this->input->post('parea'),
						);
			if($this->input->post('fid_cliente') > 0)
				$data['id_cliente'] = $this->input->post('fid_cliente');
		}

		//se valida que no este un pallet pendiente de la misma clasificacion
		// if($this->checkPalletPendiente($data['id_clasificacion'])){
			$this->db->insert('rastria_pallets', $data);
			$id_pallet = $this->db->insert_id('rastria_pallets', 'id_pallet');

      // Bitacora
      $this->bitacora_model->_insert('rastria_pallets', $id_pallet,
                                      array(':accion'    => 'el pallet', ':seccion' => 'pallets',
                                            ':folio'     => $data['folio'],
                                            ':id_empresa' => '2',
                                            ':empresa'   => 'en EMPAQUE SAN JORGE SA DE CV'));

			$this->addPalletRendimientos($id_pallet);

			$this->addPalletCalibres($id_pallet);

      $this->addPalletProductosSalida($id_pallet);

			// $this->addPalletRendimiento($data['id_clasificacion']);

			$this->registraSalida($id_pallet);

      return array('msg' => 3, $id_pallet);
		// }
		// return array('msg' => 4, 0);
 	}

 	/**
 	 * Modifica un pallet a la bd
 	 * @param [type] $data array con los valores a insertar
 	 */
 	public function updatePallet($id_pallet, $data=NULL){
 		if ($data==NULL)
		{
			$data = array(
						// 'id_clasificacion' => $this->input->post('fid_clasificacion'),
						'folio'        => $this->input->post('ffolio'),
						'no_cajas'     => $this->input->post('fcajas'),
						'no_hojas'     => 0,
						'kilos_pallet' => $this->input->post('fkilos'),
            'calibre_fijo' => $this->input->post('fcalibre_fijo'),
						);
			if($this->input->post('fid_cliente') > 0)
				$data['id_cliente'] = $this->input->post('fid_cliente');
		}

    // Bitacora
    $id_bitacora = $this->bitacora_model->_update('rastria_pallets', $id_pallet, $data,
                              array(':accion'       => 'el pallet', ':seccion' => 'pallets',
                                    ':folio'        => $data['folio'],
                                    ':id_empresa'   => '2',
                                    ':empresa'      => 'en EMPAQUE SAN JORGE SA DE CV',
                                    ':id'           => 'id_pallet',
                                    ':titulo'       => 'Pallet'));

		$this->db->update('rastria_pallets', $data, "id_pallet = {$id_pallet}");

		$this->db->delete('rastria_pallets_rendimiento', "id_pallet = {$id_pallet}");
		$this->addPalletRendimientos($id_pallet, NULL, $id_bitacora);

		$this->db->delete('rastria_pallets_calibres', "id_pallet = {$id_pallet}");
		$this->addPalletCalibres($id_pallet);

    $this->db->delete('rastria_pallets_salidas', "id_pallet = {$id_pallet}");
    $this->addPalletProductosSalida($id_pallet);

    $this->db->delete('compras_salidas', "id_pallet = {$id_pallet}");
    $this->registraSalida($id_pallet);

		return array('msg' => 5);
 	}

	public function addPalletRendimientos($id_pallet, $data=NULL, $id_bitacora=0){
		if ($data==NULL)
		{
			if(is_array($this->input->post('rendimientos')))
			{
				foreach ($this->input->post('rendimientos') as $key => $cajas)
				{
					$data[] = array(
						'id_pallet'        => $id_pallet,
						'id_rendimiento'   => $_POST['idrendimientos'][$key],
						'id_clasificacion' => $_POST['idclasificacion'][$key],
						'id_unidad'        => $_POST['idunidad'][$key],
						'id_calibre'       => $_POST['idcalibre'][$key],
						'id_etiqueta'      => $_POST['idetiqueta'][$key],
						'id_size'          => $_POST['idsize'][$key],
						'cajas'            => $cajas,
						'kilos'            => $_POST['dkilos'][$key],
						);
				}
			}
		}

		if(count($data) > 0) {
      if($id_bitacora > 0) {
        // Bitacora
        $this->bitacora_model->_updateExt($id_bitacora, 'rastria_pallets_rendimiento', $id_pallet, $data,
                                  array(':id'             => 'id_pallet',
                                        ':titulo'         => 'Clasificaciones',
                                        ':updates_fields' => 'rastria_pallets_rendimiento'));
      }
			$this->db->insert_batch('rastria_pallets_rendimiento', $data);
    }

		return true;
	}

	public function addPalletCalibres($id_pallet, $data=null)
	{
		if ($data==NULL)
		{
      $data = array();
			if(is_array($this->input->post('idcalibre')))
			{
				foreach ($this->input->post('idcalibre') as $key => $calibre)
				{
					if (!array_key_exists($calibre, $data))
					{
						$data[$calibre] = array(
							'id_pallet'  => $id_pallet,
							'id_calibre' => $calibre,
							);
					}
				}
			}
		}

		if(count($data) > 0)
			$this->db->insert_batch('rastria_pallets_calibres', $data);

		return true;
	}

  public function addPalletProductosSalida($id_pallet, $data=NULL, $id_bitacora=0){
    if ($data==NULL)
    {
      if(is_array($this->input->post('ps_id')))
      {
        foreach ($this->input->post('ps_id') as $key => $prod)
        {
          $data[] = array(
            'id_pallet'   => $id_pallet,
            'id_producto' => $prod>0? $prod: NULL,
            'cantidad'    => floatval($_POST['ps_num'][$key]),
            'nom_row'     => $_POST['ps_row'][$key],
            'row'         => $key,
            );
        }
      }
    }

    if(count($data) > 0) {
      $this->db->insert_batch('rastria_pallets_salidas', $data);
    }

    return true;
  }

  public function registraSalida($id_pallet)
  {
    $dato_info = $this->getInfoPallet($id_pallet, true);

    if($dato_info['info']->no_cajas == $dato_info['info']->cajas)
    {
      // Procesa la salida
      $this->load->model('productos_salidas_model');
      $this->load->model('inventario_model');

      $infoSalida      = array();
      $productosSalida = array(); // contiene los productos que se daran salida.
      // // Se obtienen los pallets ligados a la factura
      // $listaPallets = $this->db->query("SELECT id_pallet FROM facturacion_pallets WHERE id_factura = {$idFactura}")->result();
      // // Si hay pallets ligados
      // if(count($listaPallets) > 0)
      // {
        // $lipallets = array();
        // foreach ($listaPallets as $keylp => $lipallet) {
        //   $lipallets[] = $lipallet->id_pallet;
        // }
        $productosPallets = $this->db->query("SELECT id_pallet, id_producto, cantidad, nom_row
                            FROM rastria_pallets_salidas WHERE id_pallet = {$id_pallet} AND id_producto IS NOT NULL")->result();
        if (count($productosPallets))
        {

          $infoSalida = array(
            'id_empresa'      => 2, // sanjorge
            'id_empleado'     => $this->session->userdata('id_usuario'),
            'folio'           => $this->productos_salidas_model->folio(),
            'fecha_creacion'  => $dato_info['info']->fecha,
            'fecha_registro'  => $dato_info['info']->fecha,
            'status'          => 's',
            // 'concepto'        => 'Salida automatica de productos al armar pallet',
            'id_pallet'       => $id_pallet,
          );

          $ress = $this->productos_salidas_model->agregar($infoSalida);

          $row = 0;
          foreach ($productosPallets as $keypp => $prodspp) {
            $inv   = $this->inventario_model->promedioData($prodspp->id_producto, substr($dato_info['info']->fecha, 0, 10), substr($dato_info['info']->fecha, 0, 10));
            $saldo = array_shift($inv);
            $productosSalida[] = array(
                  'id_salida'       => $ress['id_salida'],
                  'id_producto'     => $prodspp->id_producto,
                  'no_row'          => $row,
                  'cantidad'        => $prodspp->cantidad,
                  'precio_unitario' => $saldo['saldo'][1],
                );

            $row++;
          }
        }

        // Si hay al menos 1 producto para las salidas lo inserta.
        if (count($productosSalida) > 0)
        {
          $this->productos_salidas_model->agregarProductos(null, $productosSalida);
        }
        // Si no hay productos para ninguna de las medidas elimina la salida.
        else
        {
          $this->db->delete('compras_salidas', array('id_salida' => $ress['id_salida']));
        }
      // }
    }
  }

	public function deletePallet($id_pallet, $delRendimientos = false)
	{
		$data = $this->db->query("SELECT Count(f.id_factura) AS num
				FROM facturacion AS f INNER JOIN facturacion_pallets AS fp ON f.id_factura = fp.id_factura
				WHERE fp.id_pallet = {$id_pallet} AND f.status <> 'ca' AND f.status <> 'b'")->row();
		$response = array('passes' => false, 'msg' => '8');
		if ($data->num == 0)
		{
      if ($delRendimientos)
      {
        $sql = $this->db->select('id_rendimiento')
                       ->from('rastria_pallets_rendimiento')
                       ->where('id_pallet', $id_pallet)
                       ->get();

        if ($sql->num_rows() > 0)
        {
          $rendimientos = $sql->result();
          foreach ($rendimientos as $r)
          {
            $this->db->delete('rastria_rendimiento', array('id_rendimiento' => $r->id_rendimiento));
          }
        }
      }

      // Bitacora
      $clientedata = $this->getInfoPallet($id_pallet);
      $this->bitacora_model->_cancel('rastria_pallets', $id_pallet,
                                      array(':accion'     => 'el pallet', ':seccion' => 'pallets',
                                            ':folio'      => $clientedata['info']->folio,
                                            ':id_empresa' => '2',
                                            ':empresa'    => 'de EMPAQUE SAN JORGE SA DE CV'));

			$this->db->delete('rastria_pallets', array('id_pallet' => $id_pallet));

			$response = array('passes' => true, 'msg' => '7');
		}
		return $response;
	}


	public function pallet_pdf($id_pallet){
		// Obtiene los datos del reporte.
		$data = $this->getInfoPallet($id_pallet, false, false);


		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', array(105, 140));
		$pdf->show_head = false;

		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetFont('helvetica','', 8);

		$pdf->SetXY(25, 3);
		$pdf->Image(APPPATH.'images/logo.png');

		$clasificaciones = array();
		foreach ($data['rendimientos'] as $key => $value) {
			if(!isset($clasificaciones[$value->id_clasificacion]))
				$clasificaciones[$value->id_clasificacion] = $value->nombre;
		}
		$clsf_show = (count($clasificaciones) > 1? true: false);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetX(6);
		$pdf->SetAligns(array('L'));
		$pdf->SetWidths(array(46, 46));
		$pdf->Row(array('DESTINO:', "No CLASIF: ".implode(', ', $clasificaciones)), false);
		$pdf->SetX(6);
		$pdf->SetAligns(array('C', 'C'));
		$pdf->Row(array('LOTE', 'CAJAS'), false);

		foreach ($data['rendimientos'] as $key => $value) {
			$fecha = strtotime($value->fecha);
			$pdf->SetX(6);
		  $pdf->Row(array(date("Ww", $fecha).' '.$value->lote, $value->cajas.($clsf_show? ' '.$value->nombre: '')), false);
		}
		$pdf->SetX(6);
		$pdf->Row(array('No. TARIMA', $data['info']->no_cajas), false);

		$pdf->SetX(6);
		$pdf->SetAligns(array('L'));
		$pdf->SetWidths(array(66));
		$pdf->Row(array('FECHA: '.$data['info']->fecha), false, false);

		$pdf->Output('REPORTE_DIARIO.pdf', 'I');
	}

	public function palletBig_pdf($id_pallet)
  {
		// Obtiene los datos del reporte.
		$data = $this->getInfoPallet($id_pallet, false, false);

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $certificados = $data;
    $noCertificados = $data;

    $certificados['rendimientos'] = array();
    $certificados['info'] = clone $data['info'];
    $certificados['info']->cajas = 0;
    $certificados['info']->kilos_pallet = 0;

    $noCertificados['rendimientos'] = array();
    $noCertificados['info'] = clone $data['info'];
    $noCertificados['info']->cajas = 0;
    $noCertificados['info']->kilos_pallet = 0;

    foreach ($data['rendimientos'] as $rendimiento)
    {
      if ($rendimiento->certificado === 't')
      {
        $certificados['rendimientos'][] = $rendimiento;
        $certificados['info']->cajas += $rendimiento->cajas;
        $certificados['info']->kilos_pallet += $rendimiento->kilos * $rendimiento->cajas;
      }
      else
      {
        $noCertificados['rendimientos'][] = $rendimiento;
        $noCertificados['info']->cajas += $rendimiento->cajas;
        $noCertificados['info']->kilos_pallet += $rendimiento->kilos * $rendimiento->cajas;
      }
    }

    // echo "<pre>";
    //   var_dump($data, $certificados, $noCertificados);
    // echo "</pre>";exit;

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm');
		$pdf->show_head = false;

    $this->mergePalletsPdf($pdf, $data);

    if (count($certificados['rendimientos']) > 0)
    {
      $this->mergePalletsPdf($pdf, $certificados, true);
    }

    if (count($noCertificados['rendimientos']) > 0 && count($certificados['rendimientos']) > 0)
    {
      $this->mergePalletsPdf($pdf, $noCertificados);
    }
		// $pdf->AliasNbPages();
  //   $pdf->AddPage();
		// $pdf->SetFont('helvetica','B', 14);

		// $pdf->SetAligns(array('L'));
		// $pdf->SetWidths(array(90));

		// $pdf->Rect(6, 8, 100, 60, '');
		// $pdf->SetXY(23, 23);
		// $pdf->Image(APPPATH.'images/logo.png', null, null, 65);

		// $pdf->Rect(106, 8, 100, 20, '');
		// $pdf->SetXY(110, 13);
		// $pdf->Cell(33, 10, 'PALLET No. ', 0);
		// $pdf->SetFont('helvetica','B', 22);
		// $pdf->Cell(90, 10, $data['info']->folio, 0);
		// $pdf->SetFont('helvetica','B', 14);

		// $pdf->Rect(106, 28, 100, 40, '');
		// $pdf->SetXY(109, 30);
		// $pdf->Cell(90, 10, 'PACKING DATE/ FECHA DE EMPAQUE', 0);
		// $pdf->SetFont('helvetica','B', 22);
		// $pdf->SetXY(109, 45);
		// $pdf->Row(array( date("d/m/Y", strtotime($data['info']->fecha)) ), false, false);
		// $pdf->SetFont('helvetica','B', 14);

		// $pdf->Rect(6, 68, 100, 80, '');
		// $pdf->SetXY(9, 70);
		// $pdf->Cell(90, 10, 'SIZE/ CALIBRE', 0);
		// $nombre_calibres = array();
  //   if($data['info']->calibre_fijo == '')
  //   {
  // 		foreach ($data['rendimientos'] as $key => $value)
  // 		{
  // 			if( ! isset($nombre_calibres[$value->size]) )
  // 				$nombre_calibres[$value->size] = 1;
  // 		}
  //     $data['info']->calibre_fijo = implode(',', array_keys($nombre_calibres));
  //   }
		// $pdf->SetFont('helvetica','B', 22);
		// $pdf->SetXY(9, 80);
		// $pdf->Row(array( $data['info']->calibre_fijo ), false, false);
		// $pdf->SetFont('helvetica','B', 14);

		// $pdf->Rect(6, 108, 100, 40, '');
		// $pdf->SetXY(9, 110);
		// $pdf->Cell(90, 10, 'KILOS', 0);
		// $pdf->SetFont('helvetica','B', 30);
		// $pdf->SetXY(9, 120);
		// $pdf->Row(array($data['info']->kilos_pallet), false, false);
		// $pdf->SetFont('helvetica','B', 14);

		// $pdf->Rect(106, 68, 100, 40, '');
		// $pdf->SetXY(109, 70);
		// $pdf->Cell(90, 10, 'CLIENTE', 0);
		// $pdf->SetXY(109, 80);

  //   $nombreFiscal = isset($data['cliente']->nombre_fiscal) ? $data['cliente']->nombre_fiscal : '';
		// $pdf->Row(array($nombreFiscal), false, false);

		// $pdf->Rect(106, 108, 100, 40, '');
		// $pdf->SetXY(109, 110);
		// $pdf->Cell(90, 10, 'BOXES/ CAJAS', 0);
		// $pdf->SetFont('helvetica','B', 30);
		// $pdf->SetXY(109, 120);
		// $pdf->Row(array($data['info']->cajas), false, false);
		// $pdf->SetFont('helvetica','B', 14);

		// $pdf->Rect(6, 148, 20, 111, '');
		// $pdf->RotatedText(16, 240, 'LISTA DE LOTIFICACION', 90);

		// $pdf->SetXY(26, 148);
		// $pdf->SetTextColor(0,0,0);
		// // $pdf->SetX(6);
		// $pdf->SetAligns(array('C', 'C'));
		// $pdf->SetWidths(array(80, 100));
		// $pdf->Row(array('No. LOTE', "No. DE CAJAS"), false);
		// $filas = 13;
		// foreach ($data['rendimientos'] as $key => $value) {
		// 	$fecha = strtotime($value->fecha);
		// 	$pdf->SetX(26);
		// 	$pdf->Row(array(date("Ww", $fecha).'-'.$value->lote_ext, $value->cajas), false);
		// 	$filas--;
		// }
		// for ($i = $filas; $i > 0; $i--)
		// {
		// 	$pdf->SetX(26);
		// 	$pdf->Row(array('', ''), false);
		// }

		$pdf->Output('REPORTE_DIARIO.pdf', 'I');
	}

  private function mergePalletsPdf(&$pdf, $data, $certificado = false)
  {
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('helvetica','B', 14);

    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(90));

    $pdf->Rect(6, 8, 100, 20, '');
    $pdf->SetXY(35, 10);
    $pdf->Cell(45, 10, 'BITACORA 19A', 0);
    $pdf->SetXY(8, 16);
    $pdf->Cell(100, 10, 'CONTROL DE PRODUCTO EMPACADO', 0);

    $pdf->Rect(6, 8, 100, 60, '');
    $pdf->SetXY(23, 38);
    $pdf->Image(APPPATH.'images/logo.png', null, null, 65);

    $pdf->Rect(106, 8, 100, 20, '');
    $pdf->SetXY(110, 13);
    $pdf->Cell(33, 10, 'PALLET No. ', 0);
    $pdf->SetFont('helvetica','B', 22);
    $pdf->Cell(90, 10, $data['info']->folio, 0);
    $pdf->SetFont('helvetica','B', 14);

    $pdf->Rect(106, 28, 100, 40, '');
    $pdf->SetXY(109, 30);
    $pdf->Cell(90, 10, 'PACKING DATE/ FECHA DE EMPAQUE', 0);
    $pdf->SetFont('helvetica','B', 22);
    $pdf->SetXY(109, 45);
    $pdf->Row(array( date("d/m/Y", strtotime($data['info']->fecha)) ), false, false);
    $pdf->SetFont('helvetica','B', 14);

    $pdf->Rect(6, 68, 100, 80, '');
    $pdf->SetXY(9, 70);
    $pdf->Cell(90, 10, 'SIZE/ CALIBRE', 0);
    $nombre_calibres = array();
    if($data['info']->calibre_fijo == '')
    {
      foreach ($data['rendimientos'] as $key => $value)
      {
        if( ! isset($nombre_calibres[$value->size]) )
          $nombre_calibres[$value->size] = 1;
      }
      $data['info']->calibre_fijo = implode(',', array_keys($nombre_calibres));
    }
    $pdf->SetFont('helvetica','B', 22);
    $pdf->SetXY(9, 80);
    $pdf->Row(array( $data['info']->calibre_fijo ), false, false);
    $pdf->SetFont('helvetica','B', 14);

    $pdf->Rect(6, 108, 100, 40, '');
    $pdf->SetXY(9, 110);
    $pdf->Cell(90, 10, 'KILOS', 0);
    $pdf->SetFont('helvetica','B', 30);
    $pdf->SetXY(9, 120);
    $pdf->Row(array($data['info']->kilos_pallet), false, false);
    $pdf->SetFont('helvetica','B', 14);

    $pdf->Rect(106, 68, 100, 40, '');
    $pdf->SetXY(109, 70);
    $pdf->Cell(90, 10, 'CLIENTE', 0);
    $pdf->SetXY(109, 80);

    $nombreFiscal = isset($data['cliente']->nombre_fiscal) ? $data['cliente']->nombre_fiscal : '';
    $pdf->Row(array($nombreFiscal), false, false);

    $pdf->Rect(106, 108, 100, 40, '');
    $pdf->SetXY(109, 110);
    $pdf->Cell(90, 10, 'BOXES/ CAJAS', 0);
    $pdf->SetFont('helvetica','B', 30);
    $pdf->SetXY(109, 120);
    $pdf->Row(array($data['info']->cajas), false, false);
    $pdf->SetFont('helvetica','B', 14);

    $pdf->Rect(6, 148, 20, 111, '');
    $pdf->RotatedText(12, 240, 'LISTA DE LOTIFICACION', 90);
    $pdf->RotatedText(18, 240, $data['info']->nombre_area, 90);

    if ($certificado)
    {
      $pdf->SetFont('helvetica','B', 15);
      $pdf->RotatedText(24, 240, 'PROD. CERTIFICADO', 90);
    }

    $pdf->SetFont('helvetica','B', 14);
    $pdf->SetXY(26, 148);
    $pdf->SetTextColor(0,0,0);
    // $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C'));
    $pdf->SetWidths(array(80, 100));
    $pdf->Row(array('No. LOTE', "No. DE CAJAS"), false);
    $filas = 13;
    foreach ($data['rendimientos'] as $key => $value) {
      $fecha = strtotime($value->fecha);
      $pdf->SetX(26);
      $pdf->Row(array(date("Ww", $fecha).'-'.$value->lote_ext, $value->cajas), false);
      $filas--;
    }
    for ($i = $filas; $i > 0; $i--)
    {
      $pdf->SetX(26);
      $pdf->Row(array('', ''), false);
    }
  }

 	public function addPalletRendimiento($id_clasificacion){
 		$pallets = $this->db->query("SELECT
									id_pallet, id_clasificacion, folio, fecha, no_cajas, status, nombre, cajas, cajas_faltantes
								FROM rastria_pallets_lista
								WHERE id_clasificacion = {$id_clasificacion} AND cajas_faltantes > 0
								ORDER BY id_pallet ASC");
 		foreach ($pallets->result() as $key => $pallet) {
 			$cajas_faltantes = $pallet->cajas_faltantes;
 			$cajas_pallet = $pallet->cajas;
 			$pallets_rendimiento = array();
 			$cajas = $this->db->query("SELECT
										id_rendimiento, id_clasificacion, rendimiento, cajas, libres
									FROM rastria_cajas_libres
									WHERE id_clasificacion = {$id_clasificacion}
									ORDER BY id_rendimiento ASC");
 			foreach ($cajas->result() as $key => $caja) {
 				if($cajas_pallet < $pallet->cajas_faltantes){
 					$cajas_agregar = ($caja->libres>=$cajas_faltantes? $cajas_faltantes: $caja->libres);
 					$pallets_rendimiento[] = array(
								'id_pallet'        => $pallet->id_pallet,
								'id_rendimiento'   => $caja->id_rendimiento,
								'id_clasificacion' => $pallet->id_clasificacion,
								'cajas'            => $cajas_agregar,
 						);
					$cajas_pallet   += $cajas_agregar;
					$cajas_faltantes -= $cajas_agregar;
 				}else
 					break;
 			}
 			$cajas->free_result();

 			if(count($pallets_rendimiento) > 0)
 				$this->db->insert_batch('rastria_pallets_rendimiento', $pallets_rendimiento);
 		}
 		$pallets->free_result();
 		return true;
 	}

 	public function checkPalletPendiente($id_clasificacion){
 		$result = $this->db->query("SELECT Count(id_pallet) AS num
 		                           FROM rastria_pallets_lista
 		                           WHERE id_clasificacion = {$id_clasificacion} AND cajas_faltantes > 0")->row();
 		return ($result->num == 0? true: false);
 	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */