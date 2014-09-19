<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bitacora_msg_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

	protected $descripciones = array(
    'facturacion_insert' => 'Se agrego :accion :folio :empresa',
    'facturacion_cancel' => 'Se cancelo :accion :folio :empresa',
    'facturacion_update' => 'Se modifico :accion :folio :empresa',
    'proveedores_insert' => 'Se agrego :accion :folio :empresa',
    'proveedores_cancel' => 'Se elimino :accion :folio :empresa',
    'proveedores_update' => 'Se modifico :accion :folio :empresa',
    'empresas_insert'    => 'Se agrego :accion :folio :empresa',
    'empresas_cancel'    => 'Se elimino :accion :folio :empresa',
    'empresas_update'    => 'Se modifico :accion :folio :empresa',
    'clientes_insert'    => 'Se agrego :accion :folio :empresa',
    'clientes_cancel'    => 'Se elimino :accion :folio :empresa',
    'clientes_update'    => 'Se modifico :accion :folio :empresa',
		);

  protected $secciones = array(
    'nota de remision',
    'proveedores',
    'empresas',
    'clientes',

    );

  protected $campos = array(
    // Ventas remision
    'facturacion_update' => array(
        'campos' => array(
          'id_empresa'          => 'Empresa',
          'id_cliente'          => 'Cliente',
          'serie'               => 'Serie',
          'folio'               => 'Folio',
          'fecha'               => 'Fecha',
          'subtotal'            => 'Subtotal',
          'importe_iva'         => 'IVA',
          'retencion_iva'       => 'Ret IVA',
          'total'               => 'Total',
          'total_letra'         => 'Total letra',
          'forma_pago'          => 'Forma de pago',
          'metodo_pago'         => 'Metodo de pago',
          'metodo_pago_digitos' => 'Digitos',
          'condicion_pago'      => 'Condicion de pago',
          'plazo_credito'       => 'Plazo de credito',
          'observaciones'       => 'Observaciones',
          'sin_costo'           => 'Sin costo',
        ),
        'campos_ids' => array(
          'id_empresa'   => "SELECT nombre_fiscal as dato FROM empresas WHERE id_empresa = ?",
          'id_cliente'   => "SELECT nombre_fiscal as dato FROM clientes WHERE id_cliente = ?",
        )
      ),
    'facturacion_productos_ventas' => array(
        'campos' => array(
          'cantidad'        => 'Cantidad',
          'descripcion'     => 'Descripción',
          'precio_unitario' => 'Precio',
          'importe'         => 'Importe',
          'iva'             => 'IVA',
          'unidad'          => 'Unidad',
          'retencion_iva'   => 'Ret IVA',
          'certificado'     => 'Certificado',
          // 'clase'           => 'Clase',
          // 'peso'            => 'Peso',
        ),
        'campos_ids' => array()
      ),
    // Proveedores
    'proveedores_update' => array(
        'campos' => array(
          'nombre_fiscal'  => 'Nombre fiscal',
          'calle'          => 'Calle',
          'no_exterior'    => 'No exterior',
          'no_interior'    => 'No interior',
          'colonia'        => 'Colonia',
          'localidad'      => 'Localidad',
          'municipio'      => 'Municipio',
          'estado'         => 'Estado',
          'cp'             => 'CP',
          'telefono'       => 'Telefono',
          'celular'        => 'Celular',
          'email'          => 'Email',
          'cuenta_cpi'     => 'Cuenta Compaq',
          'tipo_proveedor' => 'Tipo proveedor',
          'rfc'            => 'RFC',
          'curp'           => 'CURP',
          'regimen_fiscal' => 'Regimen fiscal',
          'condicion_pago' => 'Condicion de pago',
          'dias_credito'   => 'Dias credito',
          'id_empresa'     => 'Empresa',
        ),
        'campos_ids' => array(
          'id_empresa'   => "SELECT nombre_fiscal as dato FROM empresas WHERE id_empresa = ?",
        )
      ),
    // Empresas
    'empresas_update' => array(
        'campos' => array(
          'nombre_fiscal'  => 'Nombre fiscal',
          'calle'          => 'Calle',
          'no_exterior'    => 'No exterior',
          'no_interior'    => 'No interior',
          'colonia'        => 'Colonia',
          'localidad'      => 'Localidad',
          'municipio'      => 'Municipio',
          'estado'         => 'Estado',
          'cp'             => 'CP',
          'rfc'            => 'RFC',
          'telefono'       => 'Telefono',
          'email'          => 'Email',
          'pag_web'        => 'Pagina',
          'regimen_fiscal' => 'Regimen fiscal',
        ),
        'campos_ids' => array()
      ),
    // Clientes
    'clientes_update' => array(
        'campos' => array(
          'nombre_fiscal'  => 'Nombre fiscal',
          'calle'          => 'Calle',
          'no_exterior'    => 'No exterior',
          'no_interior'    => 'No interior',
          'colonia'        => 'Colonia',
          'localidad'      => 'Localidad',
          'municipio'      => 'Municipio',
          'estado'         => 'Estado',
          'cp'             => 'CP',
          'rfc'            => 'RFC',
          'telefono'       => 'Telefono',
          'email'          => 'Email',
          'pag_web'        => 'Pagina',
          'regimen_fiscal' => 'Regimen fiscal',
        ),
        'campos_ids' => array()
      ),
    );


  public function getSecciones()
  {
    return $this->secciones;
  }
}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */