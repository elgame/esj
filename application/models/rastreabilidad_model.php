<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  public function saveClasificacion()
  {
    //Si no es un id, se inserta o se obtiene el calibre
    if ( ! is_numeric($_POST['id_calibre']))
    {
      $this->load->model('calibres_model');
      $data_calibre        = $this->calibres_model->addCalibre($_POST['fcalibre']);
      $_POST['id_calibre'] = $data_calibre['id'];
    }
    //Si no es un id, se inserta o se obtiene el calibre
    if ( ! is_numeric($_POST['id_size']))
    {
      $this->load->model('calibres_model');
      $data_size        = $this->calibres_model->addCalibre($_POST['fsize']);
      $_POST['id_size'] = $data_size['id'];
    }

    $_POST['id_clasificacion_old'] = 0;
    $_POST['id_unidad_old']        = 0;
    $_POST['id_calibre_old']       = 0;
    $_POST['id_etiqueta_old']      = 0;
    $_POST['id_size_old']          = 0;
    $_POST['kilos_old']            = 0;

    $data = array(
      'id_rendimiento'   => $_POST['id_rendimiento'],
      'id_clasificacion' => $_POST['id_clasificacion'],
      'id_unidad'        => $_POST['id_unidad'],
      'id_calibre'       => $_POST['id_calibre'],
      'id_size'          => $_POST['id_size'],
      'id_etiqueta'      => $_POST['id_etiqueta'],
      'existente'        => $_POST['existente'],
      'kilos'            => $_POST['kilos'],
      'linea1'           => $_POST['linea1'],
      'linea2'           => $_POST['linea2'],
      'total'            => $_POST['total'],
      'rendimiento'      => $_POST['rendimiento'],
    );

    $passess = false;
    if( ! $this->existeRendimiento($data))
    {
      $this->db->insert('rastria_rendimiento_clasif', $data);

      $info = $this->getLoteInfo($_POST['id_rendimiento']);

      // Obtiene los lotes siguientes al lote de la clasificacion que se modifico
      $sql = $this->db->query(
        "SELECT id_rendimiento, lote, lote_ext
          FROM rastria_rendimiento
          WHERE fecha = '{$info['info']->fecha}' AND lote > {$info['info']->lote}
          ORDER BY lote ASC
        ");

      // Si existen lotes siguientes
      if ($sql->num_rows() > 0)
        $this->updateMasivoClasifi($sql->result(), $data['total']);
      $passess = true;
    }

    return array('passess' => $passess,
            'id_calibre' => (isset($data_calibre['id'])? $data_calibre['id']: ''),
            'id_size' => (isset($data_size['id'])? $data_size['id']: '') );
  }

  public function existeRendimiento($data, $tipo='add')
  {
    $data = $this->db->query(
      "SELECT Count(*) AS num
        FROM rastria_rendimiento_clasif
        WHERE id_rendimiento = '{$data['id_rendimiento']}'
          AND id_clasificacion = '{$data['id_clasificacion']}'
          AND id_unidad = '{$data['id_unidad']}'
          AND id_calibre = '{$data['id_calibre']}'
          AND id_etiqueta = '{$data['id_etiqueta']}'
          AND id_size = '{$data['id_size']}'
          AND kilos = '{$data['kilos']}'
      ")->row();
    if($tipo=='add')
      return ($data->num>0? true: false);

    //si se actualiza
    if($data->num <= 1)
      return false;
    return true;
  }

  /**
   * Edita una clasificacion de un lote y todas aquellas clasificaciones iguales
   * de los lotes siguientes.
   *
   * @return array
   */
  public function editClasificacion()
  {
    //Si no es un id, se inserta o se obtiene el calibre
    if ( ! is_numeric($_POST['id_calibre']))
    {
      $this->load->model('calibres_model');
      $data_calibre        = $this->calibres_model->addCalibre($_POST['fcalibre']);
      $_POST['id_calibre'] = $data_calibre['id'];
    }
    //Si no es un id, se inserta o se obtiene el calibre
    if ( ! is_numeric($_POST['id_size']))
    {
      $this->load->model('calibres_model');
      $data_size        = $this->calibres_model->addCalibre($_POST['fsize']);
      $_POST['id_size'] = $data_size['id'];
    }

    $data = array(
      'id_clasificacion' => $_POST['id_clasificacion'],
      'id_unidad'        => $_POST['id_unidad'],
      'id_calibre'       => $_POST['id_calibre'],
      'id_size'          => $_POST['id_size'],
      'id_etiqueta'      => $_POST['id_etiqueta'],

      'existente'        => $_POST['existente'],
      'kilos'            => $_POST['kilos'],
      'linea1'           => $_POST['linea1'],
      'linea2'           => $_POST['linea2'],
      'total'            => $_POST['total'],
      'rendimiento'      => $_POST['rendimiento'],
    );

    $passess = false;
    $dataval = $data;
    $dataval['id_rendimiento']   = $_POST['id_rendimiento'];
    // $dataval['id_clasificacion'] = $_POST['id_clasificacion'];
    if( ! $this->existeRendimiento($dataval, 'edit'))
    {
      // Actualiza los datos de la clasificacion
      $this->db->update('rastria_rendimiento_clasif',$data, array(
        'id_rendimiento'   => $_POST['id_rendimiento'],
        'id_clasificacion' => $_POST['id_clasificacion_old'],
        'id_unidad'        => $_POST['id_unidad_old'],
        'id_calibre'       => $_POST['id_calibre_old'],
        'id_etiqueta'      => $_POST['id_etiqueta_old'],
        'id_size'          => $_POST['id_size_old'],
        'kilos'            => $_POST['kilos_old'])
      );
      //Actualiza los rendimientos asignados al pallet
      $this->db->update('rastria_pallets_rendimiento',
        array(
          'id_clasificacion' => $_POST['id_clasificacion'],
          'id_unidad'        => $_POST['id_unidad'],
          'id_calibre'       => $_POST['id_calibre'],
          'id_etiqueta'      => $_POST['id_etiqueta'],
          'id_size'          => $_POST['id_size'],
          'kilos'            => $_POST['kilos'],
        ),
        array(
          'id_rendimiento'   => $_POST['id_rendimiento'],
          'id_clasificacion' => $_POST['id_clasificacion_old'],
          'id_unidad'        => $_POST['id_unidad_old'],
          'id_calibre'       => $_POST['id_calibre_old'],
          'id_etiqueta'      => $_POST['id_etiqueta_old'],
          'id_size'          => $_POST['id_size_old'],
          'kilos'            => $_POST['kilos_old'],
        )
      );

      // // Elimina la clasificacion de los pallets
      // $this->db->delete('rastria_pallets_rendimiento', array(
      //   'id_rendimiento'   => $_POST['id_rendimiento'],
      //   'id_clasificacion' => $_POST['id_clasificacion']
      // ));

      // Obtiene la fecha y el lote de la clasificacion que se modifico.
      $res = $this->db->select("DATE(fecha) AS fecha, lote, lote_ext, certificado, id_area")
        ->from("rastria_rendimiento")
        ->where("id_rendimiento", $_POST['id_rendimiento'])
        ->get()->row();

      // Obtiene los lotes siguientes al lote de la clasificacion que se modifico
      $sql = $this->db->query(
        "SELECT id_rendimiento, lote, lote_ext, certificado, id_area
          FROM rastria_rendimiento
          WHERE id_area = {$res->id_area} AND fecha = '{$res->fecha}' AND lote > {$res->lote}
          ORDER BY lote ASC
        ");

      // Si existen lotes siguientes
      if ($sql->num_rows() > 0)
        $this->updateMasivoClasifi($sql->result(), $data['total']);
      $passess = true;
    }

    return array('passess' => $passess,
            'id_calibre' => (isset($data_calibre['id'])? $data_calibre['id']: ''),
            'id_size' => (isset($data_size['id'])? $data_size['id']: '') );
  }

  /**
   * Elimina una clasificacion de la BDD.
   *
   * @return array
   */
  public function delClasificacion()
  {
    // Obtiene la fecha y el lote de la clasificacion que se elimino.
    $res = $this->db->select("DATE(fecha) AS fecha, lote, lote_ext")
      ->from("rastria_rendimiento")
      ->where("id_rendimiento", $_POST['id_rendimiento'])
      ->get()->row();

    // Se elimina la clasificacion
    $tables = array('rastria_rendimiento_clasif', 'rastria_pallets_rendimiento');
    $this->db->where(array(
      'id_rendimiento'   => $_POST['id_rendimiento'],
      'id_clasificacion' => $_POST['id_clasificacion'],
      'id_unidad'        => $_POST['id_unidad'],
      'id_calibre'       => $_POST['id_calibre'],
      'id_etiqueta'      => $_POST['id_etiqueta'],
      'id_size'          => $_POST['id_size'],
      'kilos'            => $_POST['kilos'],
      )
    );
    $this->db->delete($tables);

    // Obtiene los lotes anteriores al lote de la clasificacion que se elimino
    $sql = $this->db->query(
      "SELECT id_rendimiento, lote, lote_ext
        FROM rastria_rendimiento
        WHERE fecha = '{$res->fecha}' AND lote < {$res->lote}
        ORDER BY lote DESC
      ");

    $existente = 0;

    // Si existen lotes anteriores
    if ($sql->num_rows() > 0)
    {
      // Recorre los lotes para ver si tienen una clasificacion como la que se
      // elimino y si tienen entonces toma los datos de esa clasificacion como base
      // para recalcular los demas lotes.
      foreach ($sql->result() as $key => $lote)
      {
        $sql2 = $this->db->query(
          "SELECT id_rendimiento, id_clasificacion, existente, linea1, linea2,
                  total, rendimiento
            FROM rastria_rendimiento_clasif
            WHERE id_clasificacion = '{$_POST['id_clasificacion']}' AND id_rendimiento = '{$lote->id_rendimiento}'
              AND id_unidad = '{$_POST['id_unidad']}' AND id_calibre = '{$_POST['id_calibre']}'
              AND id_etiqueta = '{$_POST['id_etiqueta']}' AND id_size = '{$_POST['id_size']}'
              AND kilos = '{$_POST['kilos']}'
        ");

        if ($sql2->num_rows() > 0)
        {
          $lote_iden = $sql2->result();

          $existente = $lote_iden[0]->total;

          break;
        }
      }
    }

    // Obtiene los lotes siguientes apartir del lote de la clasificacion
    // que se elimino.
    $sql3 = $this->db->query(
      "SELECT id_rendimiento, lote, lote_ext
        FROM rastria_rendimiento
        WHERE fecha = '{$res->fecha}' AND lote > {$res->lote}
        ORDER BY lote ASC
      ");

    if ($sql3->num_rows() > 0)
      $this->updateMasivoClasifi($sql3->result(), $existente);

    return array('passess' => true);
  }

  public function updateMasivoClasifi($lotes, $existente)
  {
    $existente = $existente;

    // Recorre los lotes para ver si tiene clasificaciones como las que se
    // modifico y si tienen entonces recalculan sus datos.
    foreach ($lotes as $key => $lote)
    {
      //Se obtienen los rendimientos nuevos
      $sql_news = $this->db->query(
        "SELECT id_rendimiento, id_clasificacion, existente, linea1, linea2,
                total, rendimiento
        FROM rastria_rendimiento_clasif
        WHERE id_clasificacion = '{$_POST['id_clasificacion']}' AND id_rendimiento = '{$lote->id_rendimiento}'
            AND id_unidad = '{$_POST['id_unidad']}' AND id_calibre = '{$_POST['id_calibre']}'
            AND id_etiqueta = '{$_POST['id_etiqueta']}' AND id_size = '{$_POST['id_size']}'
            AND kilos = '{$_POST['kilos']}'
      ");
      //se obtienen los rendimientos antiguos
      $sql2 = $this->db->query(
        "SELECT id_rendimiento, id_clasificacion, existente, linea1, linea2,
                total, rendimiento
        FROM rastria_rendimiento_clasif
        WHERE id_clasificacion = '{$_POST['id_clasificacion_old']}' AND id_rendimiento = '{$lote->id_rendimiento}'
            AND id_unidad = '{$_POST['id_unidad_old']}' AND id_calibre = '{$_POST['id_calibre_old']}'
            AND id_etiqueta = '{$_POST['id_etiqueta_old']}' AND id_size = '{$_POST['id_size_old']}'
            AND kilos = '{$_POST['kilos_old']}'
      ");

      if ($sql_news->num_rows() > 0) //ya existe uno con los valores nuevos
      {
        $clasifi = $sql_news->row();

        $campos = array(
            'existente' => $existente,
            'total'     => floatval($existente) + floatval($clasifi->linea1) + floatval($clasifi->linea2)
          );

        if ($sql2->num_rows() > 0)
        {
          $clasifi2 = $sql2->row();

          if (
            $_POST['id_clasificacion'] != $_POST['id_clasificacion_old'] ||
            $_POST['id_unidad'] != $_POST['id_unidad_old'] ||
            $_POST['id_calibre'] != $_POST['id_calibre_old'] ||
            $_POST['id_etiqueta'] != $_POST['id_etiqueta_old'] ||
            $_POST['id_size'] != $_POST['id_size_old'] ||
            $_POST['kilos'] != $_POST['kilos_old'] )
          {
            // $campos['existente']        = $_POST['existente'];
            $campos['linea1']      = $clasifi->linea1+$clasifi2->linea1;
            $campos['linea2']      = $clasifi->linea2+$clasifi2->linea2;
            $campos['total']       += $clasifi2->linea1+$clasifi2->linea2;
            $campos['rendimiento'] = $campos['linea1']+$campos['linea2'];

            //Elimina el rendimiento viejo
            $this->db->delete('rastria_rendimiento_clasif', array(
                'id_rendimiento'   => $clasifi2->id_rendimiento,
                'id_clasificacion' => $_POST['id_clasificacion_old'],
                'id_unidad'        => $_POST['id_unidad_old'],
                'id_calibre'       => $_POST['id_calibre_old'],
                'id_etiqueta'      => $_POST['id_etiqueta_old'],
                'id_size'          => $_POST['id_size_old'],
                'kilos'            => $_POST['kilos_old'],
            ));
            $this->db->delete('rastria_pallets_rendimiento' , array(
                'id_rendimiento'   => $clasifi2->id_rendimiento,
                'id_clasificacion' => $_POST['id_clasificacion_old'],
                'id_unidad'        => $_POST['id_unidad_old'],
                'id_calibre'       => $_POST['id_calibre_old'],
                'id_etiqueta'      => $_POST['id_etiqueta_old'],
                'id_size'          => $_POST['id_size_old'],
                'kilos'            => $_POST['kilos_old'],
            ));
          }
        }

        // Actualiza los datos de la clasificacion.
        $this->db->update('rastria_rendimiento_clasif',
          $campos,
          array(
            'id_rendimiento'   => $clasifi->id_rendimiento,
            'id_clasificacion' => $_POST['id_clasificacion'],
            'id_unidad'        => $_POST['id_unidad'],
            'id_calibre'       => $_POST['id_calibre'],
            'id_etiqueta'      => $_POST['id_etiqueta'],
            'id_size'          => $_POST['id_size'],
            'kilos'            => $_POST['kilos'],
          )
        );
        //Actualiza los rendimientos asignados al pallet
        $this->db->update('rastria_pallets_rendimiento',
          array(
            'id_clasificacion' => $_POST['id_clasificacion'],
            'id_unidad'        => $_POST['id_unidad'],
            'id_calibre'       => $_POST['id_calibre'],
            'id_etiqueta'      => $_POST['id_etiqueta'],
            'id_size'          => $_POST['id_size'],
            'kilos'            => $_POST['kilos'],
          ),
          array(
            'id_rendimiento'   => $clasifi->id_rendimiento,
            'id_clasificacion' => $_POST['id_clasificacion_old'],
            'id_unidad'        => $_POST['id_unidad_old'],
            'id_calibre'       => $_POST['id_calibre_old'],
            'id_etiqueta'      => $_POST['id_etiqueta_old'],
            'id_size'          => $_POST['id_size_old'],
            'kilos'            => $_POST['kilos_old'],
          )
        );

        $existente = $campos['total'];

      }elseif ($sql2->num_rows() > 0) //si hay con los valores viejos
      {
        $clasifi2 = $sql2->row();

        // Actualiza los datos de la clasificacion.
        $this->db->update('rastria_rendimiento_clasif',
          array(
            'existente'        => $existente,
            'total'            => floatval($existente) + floatval($clasifi2->linea1) + floatval($clasifi2->linea2),
            'id_clasificacion' => $_POST['id_clasificacion'],
            'id_unidad'        => $_POST['id_unidad'],
            'id_calibre'       => $_POST['id_calibre'],
            'id_etiqueta'      => $_POST['id_etiqueta'],
            'id_size'          => $_POST['id_size'],
            'kilos'            => $_POST['kilos'],
          ),
          array(
            'id_rendimiento'   => $clasifi2->id_rendimiento,
            'id_clasificacion' => $_POST['id_clasificacion_old'],
            'id_unidad'        => $_POST['id_unidad_old'],
            'id_calibre'       => $_POST['id_calibre_old'],
            'id_etiqueta'      => $_POST['id_etiqueta_old'],
            'id_size'          => $_POST['id_size_old'],
            'kilos'            => $_POST['kilos_old'],
          )
        );
        //Actualiza los rendimientos asignados al pallet
        $this->db->update('rastria_pallets_rendimiento',
          array(
            'id_clasificacion' => $_POST['id_clasificacion'],
            'id_unidad'        => $_POST['id_unidad'],
            'id_calibre'       => $_POST['id_calibre'],
            'id_etiqueta'      => $_POST['id_etiqueta'],
            'id_size'          => $_POST['id_size'],
            'kilos'            => $_POST['kilos'],
          ),
          array(
            'id_rendimiento'   => $clasifi2->id_rendimiento,
            'id_clasificacion' => $_POST['id_clasificacion_old'],
            'id_unidad'        => $_POST['id_unidad_old'],
            'id_calibre'       => $_POST['id_calibre_old'],
            'id_etiqueta'      => $_POST['id_etiqueta_old'],
            'id_size'          => $_POST['id_size_old'],
            'kilos'            => $_POST['kilos_old'],
          )
        );

        $existente = floatval($existente) + floatval($clasifi2->linea1) + floatval($clasifi2->linea2);
      }

      $sql2->free_result();
      $sql_news->free_result();
    }
  }

  public function createLote($fecha, $lote, $lote_ext, $id_area, $certificado = 'f')
  {
    $this->db->insert('rastria_rendimiento', array(
      'lote'        => $lote,
      'fecha'       => $fecha,
      'lote_ext'    => $lote_ext,
      'certificado' => $certificado,
      'id_area'     => $id_area,
    ));

    $id = $this->db->insert_id();

    return $id;
  }

  public function getLotesByFecha($fecha, $id_area)
  {
    $sql = $this->db->query(
      "SELECT id_rendimiento, lote, fecha, status, lote_ext, id_area, certificado
      FROM rastria_rendimiento
      WHERE id_area = {$id_area} AND
        DATE(fecha) = '{$fecha}' AND
        status = true
      ORDER BY lote ASC
      ");

    $lotes = array();
    if ($sql->num_rows() > 0)
      $lotes = $sql->result();

    return $lotes;
  }

  public function getLoteInfo($id_rendimiento, $full_info = true)
  {
    $sql = $this->db->select("id_rendimiento, lote, DATE(fecha) AS fecha, status, lote_ext, certificado, id_area")
      ->from("rastria_rendimiento")
      ->where("id_rendimiento", $id_rendimiento)
      ->get();

    $data = array(
      "info" => array(),
      "clasificaciones" => array(),
    );

    if ($sql->num_rows > 0)
    {
      $data['info'] = $sql->row();

      if ($full_info)
      {
        $sql->free_result();

        $sql = $this->db->query(
          "SELECT rrc.id_rendimiento, rrc.id_clasificacion, rrc.existente, rrc.kilos, rrc.linea1, rrc.linea2,
                  rrc.total, rrc.rendimiento, cl.nombre as clasificacion,
                  u.id_unidad, u.nombre AS unidad, ca.id_calibre, ca.nombre AS calibre,
                  e.id_etiqueta, e.nombre AS etiqueta, cas.id_calibre AS id_size, cas.nombre AS size
          FROM rastria_rendimiento_clasif AS rrc
            INNER JOIN clasificaciones AS cl ON cl.id_clasificacion = rrc.id_clasificacion
            LEFT JOIN unidades AS u ON u.id_unidad = rrc.id_unidad
            LEFT JOIN calibres AS ca ON ca.id_calibre = rrc.id_calibre
            LEFT JOIN etiquetas AS e ON e.id_etiqueta = rrc.id_etiqueta
            LEFT JOIN calibres AS cas ON cas.id_calibre = rrc.id_size
          WHERE
            rrc.id_rendimiento = {$id_rendimiento}
          ORDER BY (rrc.id_rendimiento, cl.nombre, u.nombre, ca.nombre, e.nombre) ASC
          ");

        if ($sql->num_rows() > 0)
          $data['clasificaciones'] = $sql->result();
      }
    }

    return $data;
  }

  public function getLoteExt($fecha, $lote, $id_area)
  {
    $sql = $this->db->select("id_rendimiento, lote, DATE(fecha) AS fecha, status, lote_ext")
      ->from("rastria_rendimiento")
      ->where("DATE(fecha)", $fecha)
      // ->where("lote", $lote)
      ->where('id_area', $id_area)
      ->order_by('lote_ext', 'DESC')
      ->limit(1)
      ->get();
    if ($sql->num_rows() > 0)
    {
      $data = $sql->row();
      return intval($data->lote_ext!=''? $data->lote_ext: $lote)+1;
    }
    return intval($lote)+1;
  }

  public function actualizaLoteExt($id_rendimiento, $lote_ext, $estaCertificado)
  {
    $this->db->update('rastria_rendimiento', array('lote_ext' => $lote_ext, 'certificado' => $estaCertificado), "id_rendimiento = {$id_rendimiento}");
    return array('passess' => true);
  }

  /**
   * Obtiene los existentes de una clasificacion
   *
   * @param  string $id_rendimiento
   * @param  string $id_clasificacion
   * @return array
   */
  public function getPrevClasificacion($id_rendimiento, $id_clasificacion, $lote,
                                        $id_unidad, $id_calibre, $id_etiqueta,
                                        $id_size, $kilos)
  {

    //Si no es un id, se inserta o se obtiene el calibre
    if ( ! is_numeric($id_calibre))
    {
      $this->load->model('calibres_model');
      $data_calibre = $this->calibres_model->addCalibre($_GET['fcalibre']);
      $id_calibre   = $data_calibre['id'];
    }
    //Si no es un id, se inserta o se obtiene el calibre
    if ( ! is_numeric($id_size))
    {
      $this->load->model('calibres_model');
      $data_size = $this->calibres_model->addCalibre($_GET['fsize']);
      $id_size   = $data_size['id'];
    }

    $info = $this->getLoteInfo($id_rendimiento, false);

    if (intval($lote) === 1)
    {
      $sql = $this->db->select('SUM(libres) AS existentes')
        ->from('rastria_cajas_libres AS rcl')
        ->join('rastria_rendimiento AS rd','rd.id_rendimiento = rcl.id_rendimiento', 'join')
        ->where('rcl.id_clasificacion', $id_clasificacion)
        ->where('rcl.id_unidad', $id_unidad)
        ->where('rcl.id_calibre', $id_calibre)
        ->where('rcl.id_etiqueta', $id_etiqueta)
        ->where('rcl.id_size', $id_size)
        ->where('rcl.kilos', $kilos)
        ->where('DATE(rd.fecha) <', $info['info']->fecha)
        ->get();
    }
    else
    {
      // Obtiene los lotes anteriores.
      $sql2 = $this->db->query(
        "SELECT id_rendimiento, lote
          FROM rastria_rendimiento
          WHERE fecha = '{$info['info']->fecha}' AND lote < {$info['info']->lote}
          ORDER BY lote DESC
        ");

      // echo "<pre>";
      //   var_dump($sql2->result());
      // echo "</pre>";exit;

      $tiene = false;

      // Si existen lotes anteriores
      if ($sql2->num_rows() > 0)
      {
        foreach ($sql2->result() as $key => $lotee)
        {
          $sql = $this->db->query(
            "SELECT total AS existentes
              FROM rastria_rendimiento_clasif
              WHERE id_clasificacion = '{$id_clasificacion}' AND id_rendimiento = '{$lotee->id_rendimiento}'
                    AND id_unidad = '{$id_unidad}' AND id_calibre = '{$id_calibre}'
                    AND id_etiqueta = '{$id_etiqueta}' AND id_size = '{$id_size}' AND kilos = '{$kilos}'
          ");

          if ($sql->num_rows() > 0)
          {
            $tiene = true;
            break;
          }
        }
      }

      if ($tiene === false)
      {
        $sql->free_result();

        $sql = $this->db->select('SUM(libres) AS existentes')
          ->from('rastria_cajas_libres AS rcl')
          ->join('rastria_rendimiento AS rd','rd.id_rendimiento = rcl.id_rendimiento', 'join')
          ->where('rcl.id_clasificacion', $id_clasificacion)
          ->where('rcl.id_unidad', $id_unidad)
          ->where('rcl.id_calibre', $id_calibre)
          ->where('rcl.id_etiqueta', $id_etiqueta)
          ->where('rcl.id_size', $id_size)
          ->where('rcl.kilos', $kilos)
          ->where('DATE(rd.fecha) <', $info['info']->fecha)
          ->get();
      }
    }

    return $sql->row();
  }

  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
  */
  /**
   * REPORTE DE RASTREABILIDAD DE PRODUCTOS
   * @return [type] [description]
   */
  public function rrp_data()
   {
      $response = array('data' => array(), 'calidad' => '', 'tipo' => 'Entrada');
      $sql = '';

      if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
        $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
        $sql .= " AND Date(b.fecha_tara) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      }
      //Filtros de calidad
      if ($this->input->get('fcalidad') != ''){
        $sql .= " AND bc.id_calidad = " . $_GET['fcalidad'];

        // Obtiene la informacion del Area filtrada.
        $this->load->model('calidades_model');
        $response['calidad'] = $this->calidades_model->getCalidadInfo($_GET['fcalidad']);
      }else
        $sql .= " AND bc.id_calidad = 0";

      $query = $this->db->query(
        "SELECT b.id_bascula,
                b.folio,
                b.no_lote,
                b.fecha_tara,
                b.chofer_es_productor,
                p.nombre_fiscal,
                c.nombre,
                Sum(bc.cajas) AS cajas,
                Sum(bc.kilos) AS kilos
        FROM bascula AS b
          INNER JOIN bascula_compra AS bc ON bc.id_bascula = b.id_bascula
          INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
          LEFT JOIN choferes AS c ON c.id_chofer = b.id_chofer
        WHERE b.status = true AND b.tipo = 'en' AND b.accion IN('sa', 'p', 'b')
          {$sql}
        GROUP BY b.id_bascula, b.folio, b.no_lote, b.fecha_tara, b.chofer_es_productor, p.nombre_fiscal, c.nombre
        ORDER BY b.no_lote ASC, b.folio ASC
        "
      );
      if($query->num_rows() > 0)
        $response['data'] = $query->result();


      return $response;
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Rastreabilidad de productos
    *
    * @return void
    */
   public function rrp_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->rrp_data();

      if(isset($data['calidad']['info']->nombre)){
        $calidad_nombre = $data['calidad']['info']->nombre;
      }else
        $calidad_nombre = '';

      $fecha = new DateTime($_GET['ffecha1']);
      $fecha2 = new DateTime($_GET['ffecha2']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE RASTREABILIDAD DEL PRODUCTO <{$calidad_nombre}>";
      $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} AL {$fecha2->format('d/m/Y')}\n";
      $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
      $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'L', 'L', 'R', 'R');
      $widths = array(12, 20, 68, 70, 15, 20);
      $header = array('LOTE', 'BOLETA', 'PRODUCTOR','FACTURADOR', 'CAJAS', 'KGS');

      $total_kilos = 0;
      $total_cajas = 0;
      $kilos_lote  = 0;
      $cajas_lote  = 0;
      $num_lote    = -1;

      foreach($data['data'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);

          if($key==0)
            $num_lote = $boleta->no_lote;
        }

        if($num_lote != $boleta->no_lote){
          $pdf->SetFont('helvetica','B',8);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row(array(
              '', '', '', '',
              String::formatoNumero($cajas_lote, 2, ''),
              String::formatoNumero($kilos_lote, 2, ''),
            ), true);
          $cajas_lote = 0;
          $kilos_lote = 0;
          $num_lote = $boleta->no_lote;
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
          $pdf->Row(array(
              $boleta->no_lote,
              $boleta->folio,
              ($boleta->chofer_es_productor=='t'? $boleta->nombre: ''),
              $boleta->nombre_fiscal,
              String::formatoNumero($boleta->cajas, 2, ''),
              String::formatoNumero($boleta->kilos, 2, ''),
            ), false);
        $cajas_lote  += $boleta->cajas;
        $kilos_lote  += $boleta->kilos;
        $total_cajas += $boleta->cajas;
        $total_kilos += $boleta->kilos;
      }
      //Total del ultimo lote
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
          '', '', '', '',
          String::formatoNumero($cajas_lote, 2, ''),
          String::formatoNumero($kilos_lote, 2, ''),
        ), true);

      //total general
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
        $pdf->Row(array(
            '', '', '', 'TOTAL',
            String::formatoNumero($total_cajas, 2, ''),
            String::formatoNumero($total_kilos, 2, ''),
          ), false, false);


      $pdf->Output('reporte_rastreabilidad_'.$fecha->format('d/m/Y').'.pdf', 'I');
   }

   /**
   * REPORTE DE RASTREABILIDAD DE PRODUCTOS
   * @return [type] [description]
   */
  public function ref_data()
  {
      $response = array('data' => array(), 'calidad' => '', 'tipo' => 'Entrada');
      $sql = '';

      if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
        $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
        $sql .= " AND Date(b.fecha_tara) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      }
      //Filtros de area
      if ($this->input->get('farea') != ''){
        $sql .= " AND b.id_area = " . $_GET['farea'];
      }else
        $sql .= " AND b.id_area = 0";

      $query = $this->db->query(
        "SELECT b.id_bascula,
          bc.id_calidad,
          c.nombre,
          b.folio,
          b.no_lote,
          b.fecha_tara,
          p.nombre_fiscal,
          Sum(bc.cajas) AS cajas,
          Sum(bc.kilos) AS kilos
        FROM bascula AS b
          INNER JOIN bascula_compra as bc ON bc.id_bascula = b.id_bascula
          INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
          INNER JOIN calidades AS c ON bc.id_calidad = c.id_calidad
        WHERE b.status = true AND b.tipo = 'en' AND b.accion IN('sa', 'p', 'b')
          {$sql}
        GROUP BY b.id_bascula, bc.id_calidad, c.nombre, b.folio, b.no_lote, b.fecha_tara, p.nombre_fiscal, bc.num_registro
        ORDER BY no_lote ASC, folio ASC, num_registro ASC
        "
      );
      if($query->num_rows() > 0){
        $response['data'] = $query->result();
        $query->free_result();
      }


      return $response;
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Rastreabilidad de productos
    *
    * @return void
    */
   public function ref_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->ref_data();

      if(isset($data['calidad']['info']->nombre)){
        $calidad_nombre = $data['calidad']['info']->nombre;
      }else
        $calidad_nombre = '';

      $fecha = new DateTime($_GET['ffecha1']);
      $fecha2 = new DateTime($_GET['ffecha2']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE RASTREABILIDAD DEL PRODUCTO <{$calidad_nombre}>";
      $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} AL {$fecha2->format('d/m/Y')}\n";
      $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
      $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'L', 'L', 'R', 'R');
      $widths = array(12, 20, 68, 70, 15, 20);
      $header = array('LOTE', 'BOLETA', 'PRODUCTOR','CALIDAD', 'CAJAS', 'KGS');

      $total_kilos = 0;
      $total_cajas = array();
      $num_lote    = -1;

      foreach($data['data'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
          $pdf->Row(array(
              ($num_lote != $boleta->no_lote? $boleta->no_lote: ''),
              $boleta->folio,
              $boleta->nombre_fiscal,
              $boleta->nombre,
              String::formatoNumero($boleta->cajas, 2, ''),
              String::formatoNumero($boleta->kilos, 2, ''),
            ), false);

        if($num_lote != $boleta->no_lote){
          $num_lote = $boleta->no_lote;
        }

        if(array_key_exists($boleta->id_calidad, $total_cajas)){
          $total_cajas[$boleta->id_calidad]['cajas'] += $boleta->cajas;
          $total_cajas[$boleta->id_calidad]['kilos'] += $boleta->kilos;
        }else{
          $total_cajas[$boleta->id_calidad] = array('cajas' => $boleta->cajas, 'kilos' => $boleta->kilos, 'nombre' => $boleta->nombre);
        }
      }

      //total general
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetAligns(array('L', 'R', 'R'));
      $pdf->SetWidths(array(40, 20, 20));

      $pdf->SetX(6);
      $pdf->Row(array(
          'CALIDAD', 'CAJAS', 'KILOS',
        ), false, false);
      foreach ($total_cajas as $key => $value) {
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetX(6);
        $pdf->Row(array(
            $value['nombre'],
            String::formatoNumero($value['cajas'], 2, ''),
            String::formatoNumero($value['kilos'], 2, ''),
          ), false, false);
      }


      $pdf->Output('reporte_rastreabilidad_'.$fecha->format('d/m/Y').'.pdf', 'I');
   }

   /**
   * REPORTE DE RASTREABILIDAD DE PRODUCTOS
   * @return [type] [description]
   */
  public function rrs_data()
  {
      $response = array('boletas' => array(), 'rendimientos' => array(), 'pallets' => array());
      $sql = '';

      if (empty($_GET['ffecha1'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1'];
        $sql .= " AND Date(b.fecha_tara) = '".$_GET['ffecha1']."'";
      }
      //Filtros de area
      if ($this->input->get('farea') != ''){
        $sql .= " AND b.id_area = " . $_GET['farea'];
      }else
        $sql .= " AND b.id_area = 0";

      $flotes = explode('-', $this->input->get('flotes'));
      if($flotes[1] != '')
        $sql .= " AND b.no_lote = ".$flotes[1];

      // Obtenemos las boletas de ese lote y area
      $query = $this->db->query(
        "SELECT b.id_bascula,
          b.folio,
          b.no_lote,
          b.fecha_tara,
          b.certificado,
          b.kilos_bruto,
          b.kilos_tara,
          b.kilos_neto,
          p.nombre_fiscal
        FROM bascula AS b
          INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
        WHERE b.status = true AND b.tipo = 'en' AND b.accion IN('sa', 'p', 'b')
          {$sql}
        ORDER BY b.folio ASC");
      if($query->num_rows() > 0)
        $response['boletas'] = $query->result();
      $query->free_result();

      // Obtenemos los rendimientos x lote
      $query = $this->db->query(
        "SELECT rr.id_rendimiento,
          rr.lote, rr.lote_ext, rr.certificado,
          rrc.rendimiento, rrc.kilos,
          (rrc.rendimiento*rrc.kilos) AS kilos_total,
          c.nombre AS clasificacion, u.nombre AS unidad,
          ca.nombre AS calibre, e.nombre AS etiqueta,
          cas.nombre AS size, a.nombre AS area
        FROM rastria_rendimiento AS rr
          INNER JOIN rastria_rendimiento_clasif AS rrc ON rr.id_rendimiento = rrc.id_rendimiento
          INNER JOIN clasificaciones AS c ON c.id_clasificacion = rrc.id_clasificacion
          INNER JOIN unidades AS u ON u.id_unidad = rrc.id_unidad
          INNER JOIN calibres AS ca ON ca.id_calibre = rrc.id_calibre
          INNER JOIN etiquetas AS e ON e.id_etiqueta = rrc.id_etiqueta
          INNER JOIN calibres AS cas ON cas.id_calibre = rrc.id_size
          INNER JOIN areas AS a ON a.id_area = rr.id_area
        WHERE rr.status = 't' AND a.id_area = {$_GET['farea']}
          AND rr.id_rendimiento = {$flotes[0]}
        ORDER BY c.nombre ASC");
      if($query->num_rows() > 0)
        $response['rendimientos'] = $query->result();
      $query->free_result();

      // Obtenemos los pallets
      $query = $this->db->query(
        "SELECT rp.id_pallet,
          rp.folio, Date(rp.fecha) AS fecha,
          f.serie, f.folio AS foliov, Date(f.fecha) AS fecha_venta,
          c.nombre_fiscal
        FROM rastria_pallets rp
          INNER JOIN rastria_pallets_rendimiento rpr ON rp.id_pallet = rpr.id_pallet
          LEFT JOIN clientes c ON c.id_cliente = rp.id_cliente
          LEFT JOIN facturacion_pallets fp ON rp.id_pallet = fp.id_pallet
          LEFT JOIN facturacion f ON (f.id_factura = fp.id_factura AND f.status IN('p', 'pa'))
        WHERE rp.status = 't' AND rp.id_area = {$_GET['farea']}
          AND rpr.id_rendimiento = {$flotes[0]}
        ORDER BY rp.folio ASC");
      if($query->num_rows() > 0)
        $response['pallets'] = $query->result();
      $query->free_result();


      return $response;
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Rastreabilidad de productos
    *
    * @return void
    */
   public function rrs_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->rrs_data();
      // echo "<pre>";
      //   var_dump($data);
      // echo "</pre>";exit;

      $fecha = new DateTime($_GET['ffecha1']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE RASTREABILIDAD Y SEGUIMIENTO PRODUCTO";
      if(isset($data['rendimientos'][0]))
        $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} | LOTE: {$data['rendimientos'][0]->lote_ext} | AREA: {$data['rendimientos'][0]->area}\n";
      // $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
      // $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

      $pdf->AliasNbPages();


      // Listado de boletas
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'L', 'R', 'R', 'R', 'C');
      $widths = array(20, 80, 20, 20, 20, 20);
      $header = array('BOLETA', 'PRODUCTOR', 'K Bruto', 'K Tara', 'K Neto', 'Certificado');

      $total_kilos_bruto = 0;
      $total_kilos_tara = 0;
      $total_kilos_neto = 0;
      $total_kilos_neto_cer = 0;

      foreach($data['boletas'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $boleta->folio,
            $boleta->nombre_fiscal,
            String::formatoNumero($boleta->kilos_bruto, 2, '', false),
            String::formatoNumero($boleta->kilos_tara, 2, '', false),
            String::formatoNumero($boleta->kilos_neto, 2, '', false),
            ($boleta->certificado=='t'? 'Si': 'No'),
          ), false);

        $total_kilos_bruto    += $boleta->kilos_bruto;
        $total_kilos_tara     += $boleta->kilos_tara;
        $total_kilos_neto     += $boleta->kilos_neto;
        $total_kilos_neto_cer += ($boleta->certificado=='t'? $boleta->kilos_neto: 0);
      }

      //total general
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(20, 20, 20, 20, 20));
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(106);
      $pdf->Row(array(
              String::formatoNumero($total_kilos_bruto, 2, '', false),
              String::formatoNumero($total_kilos_tara, 2, '', false),
              String::formatoNumero($total_kilos_neto, 2, '', false),
              String::formatoNumero($total_kilos_neto_cer, 2, '', false),
            ), false);

      // Listado de Rendimientos x lote
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetY($pdf->GetY()+2);

      $aligns = array('C', 'L', 'R', 'R', 'R', 'C');
      $widths = array(60, 60, 20, 20, 20, 20);
      $header = array('Clasificacion', 'Otros', 'Rendimiento', 'Kilos', 'T Kilos', 'Certificado');

      $total_rendimiento = 0;
      $total_kilos_total = 0;

      foreach($data['rendimientos'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $boleta->clasificacion,
            $boleta->unidad.' '.$boleta->calibre.' '.$boleta->size.' '.$boleta->etiqueta,
            String::formatoNumero($boleta->rendimiento, 2, '', false),
            String::formatoNumero($boleta->kilos, 2, '', false),
            String::formatoNumero($boleta->kilos_total, 2, '', false),
            ($boleta->certificado=='t'? 'Si': 'No'),
          ), false);

        $total_rendimiento += $boleta->rendimiento;
        $total_kilos_total += $boleta->kilos_total;
      }

      //total general
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(20, 40, 20, 20));
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(126);
      $pdf->Row(array(
              String::formatoNumero($total_rendimiento, 2, '', false), String::formatoNumero($total_kilos_total, 2, '', false), '',
            ), false);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(6);
      $pdf->Row(array(
              'Entro', String::formatoNumero($total_kilos_neto, 2, '', false),
            ), false);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(6);
      $pdf->Row(array(
              'Empacado', String::formatoNumero($total_kilos_total, 2, '', false),
            ), false);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(6);
      $pdf->Row(array(
              'Industrial', String::formatoNumero($total_kilos_neto-$total_kilos_total, 2, '', false),
            ), false);


      // Listado de pallets
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetY($pdf->GetY()+2);

      $aligns = array('C', 'L', 'C', 'L', 'L', 'C');
      $widths = array(25, 20, 25, 25, 100);
      $header = array('Fecha P', 'Pallet', 'Fecha V', 'Venta', 'Cliente');

      $total_pcajas = 0;
      $total_pkilos = 0;

      foreach($data['pallets'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $boleta->fecha,
            $boleta->folio,
            $boleta->fecha_venta,
            $boleta->serie.$boleta->foliov,
            $boleta->nombre_fiscal,
          ), false);
      }


      $pdf->Output('reporte_rastreabilidad_'.$fecha->format('d/m/Y').'.pdf', 'I');
   }

   /**
   * REPORTE DE RENDIMIENTO
   * @return [type] [description]
   */
  public function rrl_data()
  {
      $response = array('lotes' => array(), 'rendimientos' => array());
      $sql1 = $sql2 = '';

      if (empty($_GET['ffecha1'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1'];
        $sql1 .= " AND Date(b.fecha_tara) = '".$_GET['ffecha1']."'";
        $sql2 .= " AND Date(rr.fecha) = '".$_GET['ffecha1']."'";
      }
      //Filtros de area
      if ($this->input->get('farea') != ''){
        $sql1 .= " AND b.id_area = " . $_GET['farea'];
        $sql2 .= " AND a.id_area = " . $_GET['farea'];
      }else {
        $sql1 .= " AND b.id_area = 0";
        $sql2 .= " AND a.id_area = 0";
      }

      // Obtenemos la lista de lotes con los totales
      $query = $this->db->query(
        "SELECT COALESCE(b.no_lote, r.no_lote) AS no_lote, COALESCE(b.btotal_cajas, 0) AS btotal_cajas, COALESCE(b.btotal_kilos, 0) AS btotal_kilos,
          COALESCE(b.bcajas_inds, 0) AS bcajas_inds, COALESCE(b.bkilos_inds, 0) AS bkilos_inds,
          COALESCE(r.rtotal_cajas, 0) AS rtotal_cajas, COALESCE(r.rtotal_kilos, 0) AS rtotal_kilos
        FROM
          (
            SELECT b.no_lote, Sum(b.total_cajas) AS btotal_cajas, Sum(b.kilos_neto) AS btotal_kilos,
              COALESCE(Sum(bc.cajas), 0) AS bcajas_inds, COALESCE(Sum(bc.kilos), 0) AS bkilos_inds
            FROM bascula AS b
              LEFT JOIN (
                SELECT id_bascula, cajas, kilos FROM bascula_compra WHERE id_calidad In(3, 4)
              ) bc ON b.id_bascula = bc.id_bascula
            WHERE b.status = true AND b.tipo = 'en' AND b.accion IN('sa', 'p', 'b')
              {$sql1}
            GROUP BY b.no_lote
            ORDER BY b.no_lote
          ) b FULL JOIN
          (
            SELECT rr.lote_ext AS no_lote, Sum(rrc.rendimiento) AS rtotal_cajas,
              (Sum(rrc.rendimiento*rrc.kilos)) AS rtotal_kilos
            FROM rastria_rendimiento AS rr
              INNER JOIN rastria_rendimiento_clasif AS rrc ON rr.id_rendimiento = rrc.id_rendimiento
              INNER JOIN areas AS a ON a.id_area = rr.id_area
            WHERE rr.status = 't' {$sql2}
            GROUP BY rr.id_rendimiento
            ORDER BY rr.id_rendimiento
          ) r ON b.no_lote = r.no_lote");
      if($query->num_rows() > 0)
        $response['lotes'] = $query->result();
      $query->free_result();

      // Obtenemos los rendimientos en los lotes de ese dia
      $query = $this->db->query(
        "SELECT c.id_clasificacion,
          Sum(rrc.rendimiento) AS rendimiento,
          Sum(rrc.rendimiento*rrc.kilos) AS kilos_total,
          c.nombre AS clasificacion,
          cas.nombre AS size, a.nombre AS area
        FROM rastria_rendimiento AS rr
          INNER JOIN rastria_rendimiento_clasif AS rrc ON rr.id_rendimiento = rrc.id_rendimiento
          INNER JOIN clasificaciones AS c ON c.id_clasificacion = rrc.id_clasificacion
          INNER JOIN unidades AS u ON u.id_unidad = rrc.id_unidad
          INNER JOIN calibres AS ca ON ca.id_calibre = rrc.id_calibre
          INNER JOIN etiquetas AS e ON e.id_etiqueta = rrc.id_etiqueta
          INNER JOIN calibres AS cas ON cas.id_calibre = rrc.id_size
          INNER JOIN areas AS a ON a.id_area = rr.id_area
        WHERE rr.status = 't' {$sql2}
        GROUP BY c.id_clasificacion, cas.nombre, a.nombre
        ORDER BY c.nombre ASC");
      // "SELECT c.id_clasificacion,
      //     Sum(rrc.rendimiento) AS rendimiento,
      //     Sum(rrc.rendimiento*rrc.kilos) AS kilos_total,
      //     c.nombre AS clasificacion, u.nombre AS unidad,
      //     ca.nombre AS calibre, e.nombre AS etiqueta,
      //     cas.nombre AS size, a.nombre AS area
      //   FROM rastria_rendimiento AS rr
      //     INNER JOIN rastria_rendimiento_clasif AS rrc ON rr.id_rendimiento = rrc.id_rendimiento
      //     INNER JOIN clasificaciones AS c ON c.id_clasificacion = rrc.id_clasificacion
      //     INNER JOIN unidades AS u ON u.id_unidad = rrc.id_unidad
      //     INNER JOIN calibres AS ca ON ca.id_calibre = rrc.id_calibre
      //     INNER JOIN etiquetas AS e ON e.id_etiqueta = rrc.id_etiqueta
      //     INNER JOIN calibres AS cas ON cas.id_calibre = rrc.id_size
      //     INNER JOIN areas AS a ON a.id_area = rr.id_area
      //   WHERE rr.status = 't' {$sql2}
      //   GROUP BY c.id_clasificacion, u.nombre, ca.nombre, e.nombre, cas.nombre, a.nombre
      //   ORDER BY c.nombre ASC"
      if($query->num_rows() > 0){
        $response['rendimientos'] = $query->result();
      }
      $query->free_result();


      return $response;
   }

   /**
    * Reporte de rendimientos de fruta
    * @return void
    */
   public function rrl_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->rrl_data();
      // echo "<pre>";
      //   var_dump($data);
      // echo "</pre>";exit;

      $fecha = new DateTime($_GET['ffecha1']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE RENDIMIENTO";
      if(isset($data['rendimientos'][0]))
        $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} | AREA: {$data['rendimientos'][0]->area}\n";
      // $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
      // $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

      $pdf->AliasNbPages();


      // Listado de boletas
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'R', 'R', 'R', 'R', 'R', 'R');
      $widths = array(20, 20, 30, 30, 25, 30, 30);
      $header = array('LOTE', 'B CAJAS', 'B K NETOS', 'B K INDUSTRIAL', 'REND BULTOS', 'REND K NETOS', 'K INDUSTRIAL');

      $totales = array(0,0,0,0,0,0);

      foreach($data['lotes'] as $key => $lote)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $lote->no_lote,
            $lote->btotal_cajas,
            String::formatoNumero($lote->btotal_kilos-$lote->bkilos_inds, 2, '', false),
            String::formatoNumero($lote->bkilos_inds, 2, '', false),
            String::formatoNumero($lote->rtotal_cajas, 2, '', false),
            String::formatoNumero($lote->rtotal_kilos, 2, '', false),
            String::formatoNumero($lote->btotal_kilos-$lote->bkilos_inds-$lote->rtotal_kilos, 2, '', false),
          ), false);

        $totales[0] += $lote->btotal_cajas;
        $totales[1] += $lote->btotal_kilos-$lote->bkilos_inds;
        $totales[2] += $lote->bkilos_inds;
        $totales[3] += $lote->rtotal_cajas;
        $totales[4] += $lote->rtotal_kilos;
        $totales[5] += $lote->btotal_kilos-$lote->bkilos_inds-$lote->rtotal_kilos;
      }

      //total general
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(20, 30, 30, 25, 30, 30));
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(26);
      $pdf->Row(array(
              String::formatoNumero($totales[0], 2, '', false),
              String::formatoNumero($totales[1], 2, '', false),
              String::formatoNumero($totales[2], 2, '', false),
              String::formatoNumero($totales[3], 2, '', false),
              String::formatoNumero($totales[4], 2, '', false),
              String::formatoNumero($totales[5], 2, '', false),
            ), false);

      // Listado de Rendimientos
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetY($pdf->GetY()+2);

      $aligns = array('L', 'L', 'R', 'R', 'R', 'C');
      $widths = array(60, 80, 20, 30);
      $header = array('Clasificacion', 'Otros', 'Rendimiento', 'T Kilos');

      $total_rendimiento = 0;
      $total_kilos_total = 0;

      foreach($data['rendimientos'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $boleta->clasificacion,
            // $boleta->unidad.' '.$boleta->calibre.' '.$boleta->size.' '.$boleta->etiqueta,
            $boleta->size,
            String::formatoNumero($boleta->rendimiento, 2, '', false),
            String::formatoNumero($boleta->kilos_total, 2, '', false),
          ), false);

        $total_rendimiento += $boleta->rendimiento;
        $total_kilos_total += $boleta->kilos_total;
      }

      //total general
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(20, 30));
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(146);
      $pdf->Row(array(
              String::formatoNumero($total_rendimiento, 2, '', false), String::formatoNumero($total_kilos_total, 2, '', false)
            ), false);

      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetWidths(array(30, 30));
      $pdf->SetX(6);
      $pdf->Row(array(
              'B K NETOS', String::formatoNumero($totales[1], 2, '', false),
            ), false);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(6);
      $pdf->Row(array(
              'REND K NETOS', String::formatoNumero($totales[4], 2, '', false),
            ), false);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(6);
      $pdf->Row(array(
              'K INDUSTRIAL', String::formatoNumero($totales[2]+$totales[5], 2, '', false),
            ), false);

      $pdf->Output('reporte_rastreabilidad_'.$fecha->format('d/m/Y').'.pdf', 'I');
   }


   /**
    * Obtiene los lotes que pintara en el reporte rpl.
    *
    * @param  int $idLote
    * @return array
    */
   private function buildLotesRpl($idLote)
   {
      $data = $this->getLoteInfo($idLote);

      $noLoteRecibido = $data['info']->lote;

      $noLoteMax = ceil($noLoteRecibido / 4) * 4;

      $noLoteMin = $noLoteMax - 3;

      $lotes = array();

      for ($i = $noLoteMin; $i <= $noLoteMax; $i++)
      {
        $lote = $this->db->select('id_rendimiento')
          ->from('rastria_rendimiento')
          ->where('lote', $i)
          ->where("DATE(fecha) = '{$data['info']->fecha}'")
          ->where('id_area', $data['info']->id_area)
          ->get();

        if ($lote->num_rows() == 1)
        {
          $idLote = $lote->row()->id_rendimiento;

          $lotes[] = $this->getLoteInfo($idLote);
        }

      }

      return $lotes;
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Rendimiento por Lote
    *
    * @return void
    */
   public function rpl_pdf($id_rendimiento)
   {
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->show_head = false;

      $pdf->AliasNbPages();
      $pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $lotes = $this->buildLotesRpl($id_rendimiento);

      $x = 1;

      foreach ($lotes as $key => $lote)
      {
        $x = $key % 2 === 0 ? 1 : 108;
        $y = $key === 0 || $key === 1 ? 25 : 130;

        $kilos = floatval(
          $this->db->select('SUM(kilos_neto) AS kilos')
          ->from('bascula')
          ->where("DATE(fecha_bruto) = '{$lote['info']->fecha}'")
          ->where("no_lote = {$lote['info']->lote}")
          ->get()->row()->kilos
          );

        $fecha = new DateTime($lote['info']->fecha);

        $pdf->SetXY($x + 25, $y);
        $pdf->Image(APPPATH.'images/logo.png');

        $pdf->SetTextColor(0,0,0);
        $pdf->SetX($x);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(107));
        $pdf->SetFillColor(200,200,200);
        $pdf->Row(array('Fecha: ' . $fecha->format('d/m/Y') . '   Lote ' .  $fecha->format("W") . (String::obtenerDiaSemana($fecha->format('Y-m-d')) + 1) . '-' . $lote['info']->lote_ext), true);
        $pdf->SetX($x);
        $pdf->SetAligns(array('L', 'C', 'C', 'C', 'C', 'C'));
        $pdf->SetWidths(array(50, 11, 12, 12, 12, 10));
        $pdf->Row(array('CLASIF.', 'EXIST', 'LINEA1', 'LINEA2', 'TOTAL', 'RD'), true);

        $kilos_reales = 0;
        foreach ($lote['clasificaciones'] as $key2 => $clasifi)
        {
          if ($key2 < 13)
          {

            $pdf->SetX($x);

            $clasificacion = $this->acomodaStringClasificacion($clasifi->clasificacion);

            $pdf->Row(array(
              strlen($clasificacion) > 25 ? substr($clasificacion, 0, 25).'...' : $clasificacion,
              $clasifi->existente,
              $clasifi->linea1,
              $clasifi->linea2,
              $clasifi->total,
              $clasifi->rendimiento,
            ), false);
            $kilos_reales += $clasifi->kilos;
          }
          else
          {
            break;
          }
        }

        $pdf->SetXY($x + 56, $y + 17);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(50, 4, "Kilos=> E:{$kilos} | S:{$kilos_reales}", 0, 0, 'R', 1);

      }

      $pdf->Output('rendimiento_lote_'.$fecha->format('d/m/Y').'.pdf', 'I');
   }

   private function acomodaStringClasificacion($clasifi)
   {
      $arrayPalabras = explode(' ', $clasifi);

      $newArrayPalabras = array();

      foreach ($arrayPalabras as $key => $palabra)
      {
        if ($key === 0)
        {
          array_push($newArrayPalabras, strtoupper(substr($arrayPalabras[0], 0 , 1)).'.');
        }
        else
        {
          $abreviacion = '';

          switch ($palabra)
          {
            case 'LIMON':
              $abreviacion = 'LMON.';
              break;
            case 'ALIMONADO':
              $abreviacion = 'ALIM.';
              break;
            case 'VERDE':
              $abreviacion = 'VER.';
              break;
            case 'INDUSTRIAL':
              $abreviacion = 'INDUS.';
              break;
            default:
              $abreviacion = $palabra;
              break;
          }

          array_push($newArrayPalabras, $abreviacion);
        }
      }

      return implode(' ', $newArrayPalabras);
   }

}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */