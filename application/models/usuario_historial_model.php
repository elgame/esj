<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Usuario_historial_model extends CI_Model {

  private $usuarioId;

  private $valoresActualesDb = array();

  private $campoEspecial = array(
    'id_empresa' => "SELECT nombre_fiscal AS valor FROM empresas WHERE id_empresa = ?"
    );

  public function make($eventos)
  {
    $campos = array();

    foreach ($eventos as $evento)
    {
      $campos[] = $evento['campo'];
    }

    $camposAObtener = implode(', ', $campos);

    $this->valoresActualesDb = $this->obtenerValoresDb($camposAObtener);

    $this->guardaHistorial($this->obtenerHistorialAGuardarDeEventos($eventos));
  }

  public function buildEvent($evento)
  {
    $datos = array(
      'id_usuario' => $this->usuarioId,
      'id_usuario_logueado' => $this->idUsuarioLogueado(),
      'evento' => $evento['evento'],
      'antes' => $evento['valor_anterior'],
      'despues' => $evento['valor_nuevo'],
    );

    if (isset($evento['fecha'])) {
      $datos['fecha'] = $evento['fecha'];
    }

    return $datos;
  }

  private function obtenerValoresDb($campos)
  {
    return $this->db->select($campos)
      ->from('usuarios')
      ->where('id', $this->usuarioId)
      ->get()
      ->row();
  }

  private function obtenerHistorialAGuardarDeEventos($eventos)
  {
    $historial = array();

    foreach ($eventos as $evento)
    {
      if ($evento['valor_nuevo'] != $this->valoresActualesDb->{$evento['campo']})
      {
        if(isset($this->campoEspecial[$evento['campo']]))
        {
          $evento['valor_anterior'] = $this->db->query( str_replace('?', $this->valoresActualesDb->{$evento['campo']}, $this->campoEspecial[$evento['campo']]) )->row()->valor;
          $evento['valor_nuevo']    = $this->db->query( str_replace('?', $evento['valor_nuevo'], $this->campoEspecial[$evento['campo']]) )->row()->valor;
        } else
          $evento['valor_anterior'] = $this->valoresActualesDb->{$evento['campo']};

        $historial[] = $this->buildEvent($evento);
      }
    }

    return $historial;
  }

  public function guardaHistorial($historial)
  {
    if (count($historial) > 0)
    {
      $this->db->insert_batch('usuarios_historial', $historial);
    }
  }

  public function setIdUsuario($usuarioId)
  {
    $this->usuarioId = $usuarioId;
  }

  public function idUsuarioLogueado()
  {
    return $this->session->userdata('id_usuario');
  }

  public function printHistorialDeEmpleado($usuarioId)
  {
    $historial = $this->getHistorial($usuarioId);

    $this->load->library('mypdf');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    $usuario = $this->usuarios_model->get_usuario_info($usuarioId, true);
    $empresa = $this->empresas_model->getInfoEmpresa($usuario['info'][0]->id_empresa);

    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
    {
      $pdf->logo = $empresa['info']->logo;
    }

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "HISTORIAL {$usuario['info'][0]->nombre} {$usuario['info'][0]->apellido_paterno} {$usuario['info'][0]->apellido_materno}";
    // $pdf->titulo3 = $this->input->get('fempresa').' | '.$data['tipo'].' | '.$data['status'];

    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);

    $aligns = array('L', 'L', 'L', 'L', 'L');
    $widths = array(18, 65, 40, 40, 40);
    $header = array('FECHA', 'EVENTO', 'VALOR ANTERIOR', 'VALOR NUEVO', 'USUARIO AUTOR.');

    foreach($historial as $key => $log)
    {
      if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(240,240,240);
        $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, 1, 1);
      }

      $pdf->SetFont('helvetica', '', 8);

      // se colocaria la info de la bascula
      $pdf->SetY($pdf->GetY());
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
        $log->fecha,
        $log->evento,
        $log->antes,
        $log->despues,
        $log->usuario_auto,
      ), false, false);
    }

    $pdf->Output('HISTORIAL.pdf', 'I');
  }

  private function getHistorial($empleadoId)
  {
    return $this->db->query(
      "SELECT uh.id,
              DATE(uh.fecha) as fecha,
              uh.evento,
              uh.antes,
              uh.despues,
              (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) as empleado,
              (u2.nombre || ' ' || u2.apellido_paterno || ' ' || u2.apellido_materno) as usuario_auto,
              u.id_empresa
         FROM usuarios_historial uh
         INNER JOIN usuarios u ON u.id = uh.id_usuario
         INNER JOIN usuarios u2 ON u2.id = uh.id_usuario_logueado
         WHERE id_usuario = {$empleadoId}
         ORDER BY id ASC"
    )->result();
  }

  public function printPrestamosDeEmpleado($usuarioId)
  {
    $historial = $this->getPrestamos($usuarioId);

    $this->load->library('mypdf');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    $usuario = $this->usuarios_model->get_usuario_info($usuarioId, true);
    $empresa = $this->empresas_model->getInfoEmpresa($usuario['info'][0]->id_empresa);

    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
    {
      $pdf->logo = $empresa['info']->logo;
    }

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "PRODUCTOS PRESTADOS A {$usuario['info'][0]->nombre} {$usuario['info'][0]->apellido_paterno} {$usuario['info'][0]->apellido_materno}";
    // $pdf->titulo3 = $this->input->get('fempresa').' | '.$data['tipo'].' | '.$data['status'];

    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);

    $aligns = array('L', 'L', 'L', 'L', 'R', 'R');
    $widths = array(20, 18, 85, 20, 30, 30);
    $header = array('FOLIO', 'FECHA', 'PRODUCTO', 'CANTIDAD', 'PRECIO', 'IMPORTE');

    foreach($historial as $key => $log)
    {
      if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(240,240,240);
        $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, 1, 1);
      }

      $pdf->SetFont('helvetica', '', 8);

      // se colocaria la info de la bascula
      $pdf->SetY($pdf->GetY());
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
        $log->folio,
        $log->fecha,
        $log->nombre,
        $log->cantidad,
        MyString::formatoNumero($log->precio_unitario, 2, '', false),
        MyString::formatoNumero($log->cantidad*$log->precio_unitario, 2, '', false),
      ), false, false);
    }

    $pdf->Output('prestamos.pdf', 'I');
  }

  private function getPrestamos($empleadoId)
  {
    return $this->db->query(
      "SELECT cs.id_salida, cs.folio, Date(cs.fecha_creacion) AS fecha, (u.nombre || ' ' || u.apellido_paterno) AS empleado,
        csp.cantidad, csp.precio_unitario, p.nombre
       FROM compras_salidas cs
       INNER JOIN usuarios u ON u.id = cs.id_usuario
       INNER JOIN compras_salidas_productos csp ON cs.id_salida = csp.id_salida
       INNER JOIN productos p ON p.id_producto = csp.id_producto
       WHERE u.id = {$empleadoId} AND Date(cs.fecha_creacion) BETWEEN Date(u.fecha_entrada) AND Date(now())
        AND cs.status <> 'ca'
       ORDER BY id ASC"
    )->result();
  }

}

/* End of file usuario_historial_model.php */
/* Location: ./application/models/usuario_historial_model.php */