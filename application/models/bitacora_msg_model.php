<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bitacora_msg_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

	protected $descripciones = array(
    'facturacion_insert'         => 'Se agrego :accion :folio :empresa',
    'facturacion_cancel'         => 'Se cancelo :accion :folio :empresa',
    'facturacion_update'         => 'Se modifico :accion :folio :empresa',
    'proveedores_insert'         => 'Se agrego :accion :folio :empresa',
    'proveedores_cancel'         => 'Se elimino :accion :folio :empresa',
    'proveedores_update'         => 'Se modifico :accion :folio :empresa',
    'empresas_insert'            => 'Se agrego :accion :folio :empresa',
    'empresas_cancel'            => 'Se elimino :accion :folio :empresa',
    'empresas_update'            => 'Se modifico :accion :folio :empresa',
    'clientes_insert'            => 'Se agrego :accion :folio :empresa',
    'clientes_cancel'            => 'Se elimino :accion :folio :empresa',
    'clientes_update'            => 'Se modifico :accion :folio :empresa',
    'compras_requisicion_insert' => 'Se agrego :accion :folio :empresa',
    'compras_requisicion_cancel' => 'Se cancelo :accion :folio :empresa',
    'compras_requisicion_update' => 'Se modifico :accion :folio :empresa',
    'compras_ordenes_insert'     => 'Se agrego :accion :folio :empresa',
    'compras_ordenes_cancel'     => 'Se cancelo :accion :folio :empresa',
    'compras_ordenes_update'     => 'Se :accion :folio :empresa',
    'compras_insert'             => 'Se agrego :accion :folio :empresa',
    'compras_cancel'             => 'Se cancelo :accion :folio :empresa',
    'compras_update'             => 'Se modifico :accion :folio :empresa',
    'rastria_pallets_insert'     => 'Se agrego :accion :folio :empresa',
    'rastria_pallets_cancel'     => 'Se elimino :accion :folio :empresa',
    'rastria_pallets_update'     => 'Se modifico :accion :folio :empresa',
    'compras_abonos_insert'      => 'Se agrego :accion :folio :empresa',
    'compras_abonos_cancel'      => 'Se elimino :accion :folio :empresa',
    'banco_movimientos_insert'   => 'Se agrego :accion :folio :empresa',
    'banco_movimientos_cancel'   => 'Se :accion :folio :empresa',
    'facturacion_abonos_insert'  => 'Se agrego :accion :folio :empresa',
    'facturacion_abonos_cancel'  => 'Se elimino :accion :folio :empresa',
		);

  protected $secciones = array(
    'nota de remision',
    'facturas',
    'proveedores',
    'empresas',
    'clientes',
    'ordenes de compra',
    'compras',
    'pallets',
    'cuentas por pagar',
    'banco',
    'cuentas por cobrar',

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
    // Ordenes de requisicion
    'compras_requisicion_update' => array(
        'campos' => array(
          'id_empresa'         => 'Empresa',
          'id_departamento'    => 'Departamento',
          'id_cliente'         => 'Cliente',
          'fecha_creacion'     => 'Fecha',
          'tipo_pago'          => 'Tipo de pago',
          'tipo_orden'         => 'Tipo de orden',
          'solicito'           => 'Solicito',
          'descripcion'        => 'Descripcion',
          'id_autorizo'        => 'Autorizo',
          'fecha_autorizacion' => 'Fecha autorizacion',
        ),
        'campos_ids' => array(
          'id_empresa'      => "SELECT nombre_fiscal as dato FROM empresas WHERE id_empresa = ?",
          'id_departamento' => "SELECT nombre as dato FROM compras_departamentos WHERE id_departamento = ?",
          'id_cliente'      => "SELECT nombre_fiscal as dato FROM clientes WHERE id_cliente = ?",
          'id_autorizo'     => "SELECT nombre as dato FROM usuarios WHERE id = ?",
        )
      ),
    'compras_requisicion_productos' => array(
        'campos' => array(
          'id_proveedor'    => 'Proveedor',
          'id_producto'     => 'Producto',
          'id_presentacion' => 'Presentacion',
          'descripcion'     => 'Descripción',
          'cantidad'        => 'Cantidad',
          'precio_unitario' => 'Precio',
          'importe'         => 'Importe',
          'iva'             => 'IVA',
          'retencion_iva'   => 'Retencion IVA',
          'total'           => 'Total',
          'observacion'     => 'Observacion',
          'ieps'            => 'IEPS',
          'tipo_cambio'     => 'Tipo de cambio',
        ),
        'campos_ids' => array(
          'id_proveedor'    => "SELECT nombre_fiscal as dato FROM proveedores WHERE id_proveedor = ?",
          'id_producto'     => "SELECT nombre as dato FROM productos WHERE id_producto = ?",
          'id_presentacion' => "SELECT nombre as dato FROM productos_presentaciones WHERE id_presentacion = ?",
        )
      ),
    // Ordenes de compra
    'compras_ordenes_update' => array(
        'campos' => array(
          'id_empresa'         => 'Empresa',
          'id_departamento'    => 'Departamento',
          'id_cliente'         => 'Cliente',
          'fecha_creacion'     => 'Fecha',
          'tipo_pago'          => 'Tipo de pago',
          'tipo_orden'         => 'Tipo de orden',
          'solicito'           => 'Solicito',
          'descripcion'        => 'Descripcion',
          'id_autorizo'        => 'Autorizo',
          'fecha_autorizacion' => 'Fecha autorizacion',
        ),
        'campos_ids' => array(
          'id_empresa'      => "SELECT nombre_fiscal as dato FROM empresas WHERE id_empresa = ?",
          'id_departamento' => "SELECT nombre as dato FROM compras_departamentos WHERE id_departamento = ?",
          'id_cliente'      => "SELECT nombre_fiscal as dato FROM clientes WHERE id_cliente = ?",
          'id_autorizo'     => "SELECT nombre as dato FROM usuarios WHERE id = ?",
        )
      ),
    'compras_ordenes_productos' => array(
        'campos' => array(
          'id_producto'     => 'Producto',
          'id_presentacion' => 'Presentacion',
          'descripcion'     => 'Descripción',
          'cantidad'        => 'Cantidad',
          'precio_unitario' => 'Precio',
          'importe'         => 'Importe',
          'iva'             => 'IVA',
          'retencion_iva'   => 'Retencion IVA',
          'total'           => 'Total',
          'observacion'     => 'Observacion',
          'ieps'            => 'IEPS',
          'tipo_cambio'     => 'Tipo de cambio',
        ),
        'campos_ids' => array(
          'id_producto'     => "SELECT nombre as dato FROM productos WHERE id_producto = ?",
          'id_presentacion' => "SELECT nombre as dato FROM productos_presentaciones WHERE id_presentacion = ?",
        )
      ),
    // Compras
    'compras_update' => array(
        'campos' => array(
          'subtotal'      => 'Subtotal',
          'importe_iva'   => 'IVA',
          'importe_ieps'  => 'IEPS',
          'retencion_iva' => 'Ret IVA',
          'total'         => 'Total',
          'fecha'         => 'Fecha',
          'serie'         => 'Serie',
          'folio'         => 'Folio',
        ),
        'campos_ids' => array()
      ),
    // Pallets
    'rastria_pallets_update' => array(
        'campos' => array(
          'folio'        => 'Folio',
          'no_cajas'     => 'No Cajas',
          'no_hojas'     => 'No Hojas',
          'kilos_pallet' => 'Kilos pallet',
          'calibre_fijo' => 'Calibre',
          'id_cliente'   => 'Cliente',
        ),
        'campos_ids' => array(
          'id_cliente' => "SELECT nombre_fiscal as dato FROM clientes WHERE id_cliente = ?",
          )
      ),
    'rastria_pallets_rendimiento' => array(
        'campos' => array(
          'id_clasificacion' => 'Clasificacion',
          'id_unidad'        => 'Unidad',
          'id_calibre'       => 'Calibre',
          'id_etiqueta'      => 'Etiqueta',
          'id_size'          => 'Tamaño',
          'cajas'            => 'Cajas',
          'kilos'            => 'Klios',
        ),
        'campos_ids' => array(
          'id_clasificacion' => "SELECT nombre as dato FROM clasificaciones WHERE id_clasificacion = ?",
          'id_unidad'        => "SELECT nombre as dato FROM unidades WHERE id_unidad = ?",
          'id_calibre'       => "SELECT nombre as dato FROM calibres WHERE id_calibre = ?",
          'id_etiqueta'      => "SELECT nombre as dato FROM etiquetas WHERE id_etiqueta = ?",
          'id_size'          => "SELECT nombre as dato FROM calibres WHERE id_calibre = ?",
        )
      ),

    );


  public function getSecciones()
  {
    return $this->secciones;
  }
}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */