<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class productos_traspasos_model extends CI_Model {

    private $empresaId;

    public function setEmpresaId($id)
    {
        $this->empresaId = $id;
    }

    public function productosDeLaEmpresa($producto = null)
    {
        $this->load->library('pagination');

        $params = array(
            'result_items_per_page' => '50',
            'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
        );

        if($params['result_page'] % $params['result_items_per_page'] == 0)
        {
            $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
        }

        $sql = '';

        //Filtros para buscar
        if($this->empresaId)
        {
            $sql .= " AND p.id_empresa = {$this->empresaId}";
        }

        //Filtros para buscar
        if($producto)
        {
            $sql .= " AND LOWER(p.nombre) LIKE '%".pg_escape_string(mb_strtolower($producto, 'UTF-8'))."%'";
        }

        $queryString = $this->getQueryStringProductosEmpresa($sql);

        $query = BDUtil::pagination($queryString, $params, true);

        $res = $this->db->query($query['query']);

        $response = array(
            'productos'       => array(),
            'total_rows'     => $query['total_rows'],
            'items_per_page' => $params['result_items_per_page'],
            'result_page'    => $params['result_page'],
        );

        if($res->num_rows() > 0)
        {
            $this->load->model('inventario_model');

            $response['productos'] = $res->result();

            foreach ($response['productos'] as $key => $value)
            {
                $data = $this->inventario_model->promedioData($value->id_producto, date('Y-m-d'), date('Y-m-d'));
                array_pop($data); array_pop($data);
                $value->data = array_pop($data)['saldo'];
                $response[$key] = $value;
            }
        }

        return $response;
    }

    private function getQueryStringProductosEmpresa($sql = '')
    {
        return "SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura
                FROM productos AS p
                INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
                INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
                WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
                ORDER BY nombre, nombre_producto ASC";
    }

    public function empresaTieneProducto($empresa_id, $producto)
    {
        $queryString = $this->getQueryStringProductosEmpresa(" AND p.id_empresa = $empresa_id AND p.nombre = '$producto'");

        $queryResult = $this->db->query("$queryString LIMIT 1");

        $producto = null;
        $existe = false;

        if ($queryResult->num_rows() > 0)
        {
            $existe = true;
            $producto = $queryResult->row();
        }

        return array('existe' => $existe, 'producto' => $producto);
    }

    public function make($data)
    {
        $salida = $this->storeSalidaAEmpresa($data);

        $orden = $this->storeOrdenAEmpresa($data);

        $traspaso = array(
            'id_empresa_de' => $data['empresa_id_de'],
            'id_empresa_para' => $data['empresa_id_para'],
            'id_salida' => $salida['id_salida'],
            'id_orden' => $orden['id_orden'],
            'id_usuario' => $this->session->userdata('id_usuario'),
            'fecha' => date('Y-m-d'),
            'descripcion' => $data['descripcion'],
        );

        $traspasoId = $this->storeTraspaso($traspaso);

        return $traspasoId;
    }

    private function storeSalidaAEmpresa($data)
    {
        $this->load->model('productos_salidas_model');

        $salida = $this->productos_salidas_model->agregar(array(
            'id_empresa'      => $data['empresa_id_de'],
            'id_empleado'     => $this->session->userdata('id_usuario'),
            'folio'           => 0,
            'concepto'        => 'Nivelacion de inventario',
            'status'          => 'n',
            'fecha_creacion'  => date('Y-m-d'),
            'fecha_registro'  => date('Y-m-d'),
        ));

        $productosSalida = array();

        foreach ($data['productos_id'] as $key => $productoId)
        {
            $productosSalida[] = array(
                'id_salida'       => $salida['id_salida'],
                'no_row'          => $key,
                'id_producto'     => $productoId,
                'cantidad'        => abs($data['productos_cantidad'][$key]),
                'precio_unitario' => 0, //$data['productos_precio'][$key],
            );
        }

        $this->productos_salidas_model->agregarProductos($salida['id_salida'], $productosSalida);

        return $salida;
    }

    private function storeOrdenAEmpresa($data)
    {
        $this->load->model('compras_ordenes_model');

        $proveedor = $this->db->query("SELECT id_proveedor FROM proveedores WHERE UPPER(nombre_fiscal)='FICTICIO' LIMIT 1")->row();

        $departamento = $this->db->query("SELECT id_departamento FROM compras_departamentos WHERE UPPER(nombre)='FICTICIO' LIMIT 1")->row();

        $fecha = date('Y-m-d');

        $orden = $this->compras_ordenes_model->agregarData(array(
            'id_empresa'      => $data['empresa_id_para'],
            'id_proveedor'    => $proveedor->id_proveedor,
            'id_departamento' => $departamento->id_departamento,
            'id_empleado'     => $this->session->userdata('id_usuario'),
            'folio'           => 0,
            'status'          => 'n',
            'autorizado'      => 't',
            'fecha_autorizacion' => $fecha,
            'fecha_aceptacion' => $fecha,
            'fecha_creacion' => $fecha,
        ));

        $ordenProductos = array();

        foreach ($data['productos_id'] as $key => $productoId)
        {
            $presenta = $this->db->query("SELECT id_presentacion FROM productos_presentaciones WHERE status = 'ac' AND id_producto = {$productoId} AND cantidad = 1 LIMIT 1")->row();

            $ordenProductos[] =  array(
                'id_orden' => $orden['id_orden'],
                'num_row' => $key,
                'id_producto' => $productoId,
                'id_presentacion'  => (count($presenta) > 0 ? $presenta->id_presentacion : NULL),
                'descripcion' => $data['productos_nombre'][$key],
                'cantidad' => abs($data['productos_cantidad'][$key]),
                'precio_unitario'  => 0,
                'importe' => 0, //(abs($data['productos_cantidad'][$key]) * $data['productos_precio'][$key]),
                'status' => 'a',
                'fecha_aceptacion' => $fecha,
                'observacion' => $data['productos_descripcion'][$key],
            );
        }

        $this->compras_ordenes_model->agregarProductosData($ordenProductos);

        return $orden;
    }

    private function storeTraspaso($data)
    {
        $this->db->insert('productos_traspasos', $data);

        return $this->db->insert_id();
    }

    private function getInfoTraspaso($traspasoId)
    {
        $info = array();

        $this->load->model('empresas_model');
        $this->load->model('compras_ordenes_model');
        $this->load->model('productos_salidas_model');

        $traspasoData = $this->db->select("id_empresa_de, id_empresa_para, id_salida, id_orden, DATE(fecha) as fecha, descripcion")
            ->from('productos_traspasos')
            ->where('id', $traspasoId)
            ->get()
            ->row();

        if (count($traspasoData) > 0)
        {
            $info['fecha'] = $traspasoData->fecha;
            $info['descripcion'] = $traspasoData->descripcion;
            $info['empresa_de'] = $this->empresas_model->getInfoEmpresa($traspasoData->id_empresa_de, true);
            $info['empresa_para'] = $this->empresas_model->getInfoEmpresa($traspasoData->id_empresa_para, true);
            $info['orden'] = $this->compras_ordenes_model->info($traspasoData->id_orden, true);
            $info['salida'] = $this->productos_salidas_model->info($traspasoData->id_salida, true);
        }

        return $info;
    }

    public function printOrden($traspasoId)
    {
        $traspaso = $this->getInfoTraspaso($traspasoId);

        if (count($traspaso) === 0) {
            return false;
        }

        $this->load->library('mypdf');
        // Creación del objeto de la clase heredada
        $pdf = new MYpdf('P', 'mm', 'Letter');

        $pdf->titulo1 = $traspaso['empresa_de']['info']->nombre_fiscal;
        $pdf->titulo2 = 'Traspaso de productos el ' . $traspaso['fecha'];
        $pdf->titulo3 = '';
        // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
        // $pdf->AliasNbPages();
        $pdf->AddPage();
        // $pdf->SetFont('Arial','',8);

        $pdf->SetX(6);

        $pdf->SetFont('Arial','B', 9);
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(100, 100));
        $pdf->Row(array('Salio de la empresa:', 'Entro a la empresa:'), false, false);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX(6);
        $pdf->Row(array($traspaso['empresa_de']['info']->nombre_fiscal, $traspaso['empresa_para']['info']->nombre_fiscal), false, false);

        $pdf->SetX(6);
        $pdf->SetFont('Arial','B', 9);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(200));
        $pdf->Row(array('Descripcion: ' . $traspaso['descripcion']), false, false);

        $pdf->SetXY(6, $pdf->GetY() + 5);
        $aligns = array('L', 'L');
        $widths = array(150, 50);
        $header = array('Productos', 'Cantidad');

        $pdf->SetWidths($widths);
        $pdf->Row($header, false, true);

        foreach($traspaso['orden']['info'][0]->productos as $key => $producto)
        {
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();

              $pdf->SetFont('Arial','B',9);
              $pdf->SetX(6);
              $pdf->SetAligns($aligns);
              $pdf->SetWidths($widths);
              $pdf->Row($header, false, true);
            }

            $pdf->SetFont('Arial','',8);

            $datos = array(
                "$producto->producto ($producto->abreviatura)",
                $producto->cantidad,
            );

            $pdf->SetX(6);
            $pdf->SetFont('Arial','',9);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($datos, false, false);
        }

            // $pdf->SetX(146);
            // $pdf->SetFont('Arial','B',8);
            // $pdf->SetAligns(array('R', 'R'));
            // $pdf->SetWidths(array(30, 30));
            // $pdf->Row(array(
            //   String::formatoNumero($total_total, 2, '', false),
            //   String::formatoNumero($total_saldo, 2, '', false)), false);

        $pdf->Output('orden_traspaso.pdf', 'I');
    }
}