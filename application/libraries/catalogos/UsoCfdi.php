<?php
// namespace Catalogos;
// use Illuminate\Support\Collection;

class UsoCfdi {
  use Catalogos;

  protected $collectionUsoCfdi;

  private $usoCfdiOld = [];

  private $usoCfdi = [
    'G01' => ['key' => 'G01', 'value' => 'Adquisición de mercancias', 'fisica' => true, 'moral' => true],
    'G02' => ['key' => 'G02', 'value' => 'Devoluciones, descuentos o bonificaciones', 'fisica' => true, 'moral' => true],
    'G03' => ['key' => 'G03', 'value' => 'Gastos en general', 'fisica' => true, 'moral' => true],
    'I01' => ['key' => 'I01', 'value' => 'Construcciones', 'fisica' => true, 'moral' => true],
    'I02' => ['key' => 'I02', 'value' => 'Mobilario y equipo de oficina por inversiones', 'fisica' => true, 'moral' => true],
    'I03' => ['key' => 'I03', 'value' => 'Equipo de transporte', 'fisica' => true, 'moral' => true],
    'I04' => ['key' => 'I04', 'value' => 'Equipo de computo y accesorios', 'fisica' => true, 'moral' => true],
    'I05' => ['key' => 'I05', 'value' => 'Dados, troqueles, moldes, matrices y herramental', 'fisica' => true, 'moral' => true],
    'I06' => ['key' => 'I06', 'value' => 'Comunicaciones telefónicas', 'fisica' => true, 'moral' => true],
    'I07' => ['key' => 'I07', 'value' => 'Comunicaciones satelitales', 'fisica' => true, 'moral' => true],
    'I08' => ['key' => 'I08', 'value' => 'Otra maquinaria y equipo', 'fisica' => true, 'moral' => true],
    'D01' => ['key' => 'D01', 'value' => 'Honorarios médicos, dentales y gastos hospitalarios.', 'fisica' => true, 'moral' => false],
    'D02' => ['key' => 'D02', 'value' => 'Gastos médicos por incapacidad o discapacidad', 'fisica' => true, 'moral' => false],
    'D03' => ['key' => 'D03', 'value' => 'Gastos funerales.', 'fisica' => true, 'moral' => false],
    'D04' => ['key' => 'D04', 'value' => 'Donativos.', 'fisica' => true, 'moral' => false],
    'D05' => ['key' => 'D05', 'value' => 'Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación).', 'fisica' => true, 'moral' => false],
    'D06' => ['key' => 'D06', 'value' => 'Aportaciones voluntarias al SAR.', 'fisica' => true, 'moral' => false],
    'D07' => ['key' => 'D07', 'value' => 'Primas por seguros de gastos médicos.', 'fisica' => true, 'moral' => false],
    'D08' => ['key' => 'D08', 'value' => 'Gastos de transportación escolar obligatoria.', 'fisica' => true, 'moral' => false],
    'D09' => ['key' => 'D09', 'value' => 'Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones.', 'fisica' => true, 'moral' => false],
    'D10' => ['key' => 'D10', 'value' => 'Pagos por servicios educativos (colegiaturas)', 'fisica' => true, 'moral' => false],
    'P01' => ['key' => 'P01', 'value' => 'Por definir', 'fisica' => true, 'moral' => true],
  ];

  /**
   * Constructor.
   *
   * @param  Illuminate\Support\Collection $collection
   * @return void
   */
  function __construct()
  {
    $this->collectionUsoCfdi = new Collection($this->usoCfdi);
  }

  public function withTrashed()
  {
    $this->collectionUsoCfdi = $this->collectionUsoCfdi->merge($this->usoCfdiOld);
    return $this;
  }

  /**
   * Obtiene la coleccion de las UsoCfdi de cfdi
   * @param  [type] $tipo fisica|moral
   * @return [type]       [description]
   */
  public function get($tipo=null)
  {
    $uso_cfdi = $this->collectionUsoCfdi;

    if (!is_null($tipo)) {
      $uso_cfdi = $this->collectionUsoCfdi->filter(function($item, $key) use ($tipo) {
        return $item[$tipo];
      });
    }

    return $uso_cfdi;
  }

  /**
   * Filtra las UsoCfdi de los cfdi
   * @param  Request $request
   * @return Illuminate\Support\Collection
   */
  public function search($key)
  {
    $uso_cfdi = $this->collectionUsoCfdi->get($key);
    // if ($this->clTxt($request->input('term')) != '') {
    //   $uso_cfdi = $uso_cfdi->filter(function($item, $key) use ($request) {
    //     $tipo = true;
    //     if ($request->input('tipo') != '')
    //       $tipo = $item[$request->tipo];
    //     return (strpos($this->clTxt($item['value']), $this->clTxt($request->term)) !== false && $tipo);
    //   });
    // }
    return $uso_cfdi;
  }

}
