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
    return array(
      'id_usuario' => $this->usuarioId,
      'id_usuario_logueado' => $this->idUsuarioLogueado(),
      'evento' => $evento['evento'],
      'antes' => $evento['valor_anterior'],
      'despues' => $evento['valor_nuevo'],
    );
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

}

/* End of file usuario_historial_model.php */
/* Location: ./application/models/usuario_historial_model.php */