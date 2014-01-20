<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <?php if ($_GET['w'] === 'c'){ ?>
          <a href="<?php echo base_url('panel/compras_ordenes/'); ?>">Ordenes de Compras</a> <span class="divider">/</span>
        <?php } else { ?>
          <a href="<?php echo base_url('panel/compras_ordenes/requisicion'); ?>">Ordenes de Requisicion</a> <span class="divider">/</span>
        <?php } ?>
      </li>
      <li>Modificar | Ver</li>
    </ul>
  </div>

  <?php

    $badgeTitle = 'PENDIENTE';
    $badgeStyle = '';

    $readonly = '';
    $disabled = '';

    $txtButton  = 'Guardar';
    $showButton = true;

    $method     = '';
    $htmlProdOk = '';
    $prodOk = false;

    if ($orden['info'][0]->status === 'p' AND $orden['info'][0]->autorizado === 'f' AND ! isset($_GET['mod']))
    {
      $badgeTitle = 'NO AUTORIZADO';
      $badgeStyle = '-warning';
      $readonly = 'readonly';
      $disabled = 'disabled';
      $txtButton = 'Autorizar';
      $method = '&m=a';
    }
    else
    {
      if ($orden['info'][0]->status === 'p' AND $orden['info'][0]->autorizado === 't' AND ! isset($_GET['mod']))
      {
        $badgeTitle = 'PENDIENTE';
        $badgeStyle = '-info';
        $readonly = 'readonly';
        $disabled = 'disabled';
        $txtButton = 'Dar Entrada';
        $method = '&m=e';
        // $htmlProdOk = '<input type="checkbox" value="1" class="prodOk"><input type="hidden" name="isProdOk[]" value="0" id="idProdOk">';
        $prodOk = true;
      }
      else if ($orden['info'][0]->status === 'a' AND $orden['info'][0]->autorizado === 't')
      {
        $badgeTitle = 'ACEPTADA';
        $badgeStyle = '-success';
        $readonly = '';
        $disabled = '';
        $showButton = true;
      }
      else if ($orden['info'][0]->status === 'f' AND $orden['info'][0]->autorizado === 't')
      {
        $badgeTitle = 'FACTURADA';
        $badgeStyle = '-success';
        $readonly = 'readonly';
        $disabled = 'disabled';
        $showButton = false;
      }
      else if ($orden['info'][0]->status === 'ca')
      {
        $badgeTitle = 'CANCELADA';
        $badgeStyle = '-warning';
        $readonly = 'readonly';
        $disabled = 'disabled';
        $showButton = false;
      }
      else if ($orden['info'][0]->status === 'r' AND $orden['info'][0]->autorizado === 't')
      {
        $badgeTitle = 'RECHAZADA';
        $badgeStyle = '-warning';
      }
    }
  ?>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-shopping-cart"></i> <?php echo $seo['titulo']; ?> <span class="badge badge<?php echo $badgeStyle ?>"><?php echo $badgeTitle ?></span></h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/compras_ordenes/modificar?'.String::getVarsLink(array('m', 'msg', 'print')).$method); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $orden['info'][0]->empresa) ?>" autofocus <?php echo $readonly ?>><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $orden['info'][0]->id_empresa) ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="proveedor">Proveedor</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="proveedor" class="span11" id="proveedor" value="<?php echo set_value('proveedor', $orden['info'][0]->proveedor) ?>" <?php echo $readonly ?>><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                </div>
                  <input type="hidden" name="proveedorId" id="proveedorId" value="<?php echo set_value('proveedorId', $orden['info'][0]->id_proveedor) ?>">
              </div>

              <div class="control-group">
                <label class="control-label" for="solicito">Solicito</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="solicito" class="span11" id="solicito" value="<?php echo set_value('solicito', $orden['info'][0]->empleado_solicito) ?>" placeholder="">
                  </div>
                </div>
                  <input type="hidden" name="solicitoId" id="solicitoId" value="<?php echo set_value('solicitoId', $orden['info'][0]->id_solicito) ?>">
              </div>

              <div class="control-group">
                <label class="control-label" for="departamento">Departamento</label>
                <div class="controls">
                  <select name="departamento" class="span12" id="departamento" <?php echo $disabled ?>>
                    <option></option>
                    <?php foreach ($departamentos as $key => $depa) { ?>
                      <option value="<?php echo $depa->id_departamento ?>" <?php echo set_select('departamento', $depa->id_departamento, $orden['info'][0]->id_departamento == $depa->id_departamento ? true : false); ?> ><?php echo $depa->nombre ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="cliente">Cliente</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="cliente" class="span11" id="cliente" value="<?php echo set_value('cliente', $orden['info'][0]->cliente) ?>" <?php echo $readonly ?>>
                  </div>
                </div>
                  <input type="hidden" name="clienteId" id="clienteId" value="<?php echo set_value('clienteId', $orden['info'][0]->id_cliente) ?>">
              </div>

            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', isset($orden['info'][0]->fecha) ? substr(str_replace(' ', 'T', $orden['info'][0]->fecha), 0, 16) : $fecha); ?>" <?php echo $readonly ?>>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="tipoOrden">Tipo de Orden</label>
                <div class="controls">
                  <select name="tipoOrden" class="span9" id="tipoOrden" <?php echo $disabled ?>>
                    <option value="p" <?php echo set_select('tipoOrden', 'p', $orden['info'][0]->tipo_orden === 'p' ? true : false); ?>>Productos</option>
                    <option value="d" <?php echo set_select('tipoOrden', 'd', $orden['info'][0]->tipo_orden === 'd' ? true : false); ?>>Descripciones</option>
                    <option value="f" <?php echo set_select('tipoOrden', 'f', $orden['info'][0]->tipo_orden === 'f' ? true : false); ?>>Fletes</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $orden['info'][0]->folio); ?>" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="tipoPago">Tipo de Pago</label>
                <div class="controls">
                  <select name="tipoPago" class="span9" id="tipoPago" <?php echo $disabled ?>>
                    <option value="cr" <?php echo set_select('tipoPago', 'cr', $orden['info'][0]->tipo_pago === 'cr' ? true : false); ?>>Credito</option>
                    <option value="co" <?php echo set_select('tipoPago', 'co', $orden['info'][0]->tipo_pago === 'co' ? true : false); ?>>Contado</option>
                  </select>
                </div>
              </div>

              <?php if ($showButton){ ?>
                  <div class="control-group">
                    <div class="controls">
                      <div class="well span9">
                          <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;"><?php echo $txtButton ?></button><br><br>
                          <?php if ($this->usuarios_model->tienePrivilegioDe("", "compras_ordenes/autorizar/") && isset($_GET['mod'])) { ?>
                            <label style="font-weight: bold;"><input type="checkbox" name="autorizar" value="1"> AUTORIZAR ENTRADA</label>
                          <?php } ?>
                      </div>
                    </div>
                  </div>
              <?php } ?>

            </div>
          </div>

          <div class="row-fluid">  <!-- Box Productos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-barcode"></i> Productos</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">

                  <div class="span12 mquit">
                    <div class="span3">
                      <!-- data-next="fcodigo" -->
                      <input type="text" class="span12" id="fcodigo" placeholder="Codigo">
                    </div><!--/span3s -->
                    <div class="span6">
                      <div class="input-append span12">
                        <input type="text" class="span12" id="fconcepto" placeholder="Producto / Descripción">
                        <!-- <a href="" rel="superbox-70x550" class="btn btn-info" type="button" data-rel="tooltip" data-title="Agregar Producto"><i class="icon-plus" ></i></a> -->
                      </div>
                      <input type="hidden" class="span1" id="fconceptoId">
                    </div><!--/span3s -->
                    <div class="span1">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant.">
                    </div><!--/span3s -->
                    <div class="span2">
                      <input type="text" class="span12 vpositive" id="fprecio" placeholder="Precio Unitario">
                    </div><!--/span3s -->

                  </div><!--/span12 -->
                  <br><br>
                  <div class="span12 mquit">
                    <div class="span3">
                      <label for="fpresentacion" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">PRESENTACION</label>
                      <select class="span12" id="fpresentacion">
                      </select>
                    </div><!--/span3 -->
                     <div class="span2">
                      <label for="funidad" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">UNIDAD</label>
                      <select class="span12" id="funidad">
                        <?php foreach ($unidades as $key => $unidad) { ?>
                          <option value="<?php echo $unidad->id_unidad ?>"><?php echo $unidad->nombre ?></option>
                        <?php } ?>
                      </select>
                    </div><!--/span2 -->
                    <div class="span2">
                      <label for="ftraslado" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">IVA</label>
                      <select class="span12" id="ftraslado">
                        <option value="0">0%</option>
                        <option value="11">11%</option>
                        <option value="16">16%</option>
                      </select>
                    </div><!--/span2 -->
                    <div class="span2 offset3">
                      <?php if ($showButton){ ?>
                          <button type="button" class="btn btn-success span12" id="btnAddProd">Agregar</button>
                      <?php } ?>
                    </div><!--/span2 -->
                  </div><!--/span12 -->

                </div><!--/row-fluid -->
                <br>
                <div class="row-fluid">
                  <div class="span12 mquit">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                      <thead>
                        <tr>
                          <th>CODIGO</th>
                          <th>PRODUCTO</th>
                          <th>PRESEN.</th>
                          <th>UNIDAD</th>
                          <th>CANT.</th>
                          <th>FALTANTES</th>
                          <th>P.U.</th>
                          <th>IVA</th>
                          <th>RET 4%</th>
                          <th>IMPORTE</th>
                          <th>DESCRIP</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (isset($_POST['concepto'])) {
                              $subtotal = $iva = $total = $retencion = 0;
                              foreach ($_POST['concepto'] as $key => $concepto) {

                                $subtotal += $_POST['importe'][$key];
                                $iva      += $_POST['trasladoTotal'][$key];
                                $retencion+= $_POST['retTotal'][$key];
                                $total    += $_POST['total'][$key];
                            ?>
                            <tr>
                              <td style="width: 70px;">
                                <?php echo $_POST['codigo'][$key] ?>
                                <input type="hidden" name="codigo[]" value="<?php echo $_POST['codigo'][$key] ?>" class="span12">
                              </td>
                              <td>
                                  <?php echo $concepto ?>
                                  <input type="hidden" name="concepto[]" value="<?php echo $concepto ?>" id="concepto" class="span12">
                                  <input type="hidden" name="productoId[]" value="<?php echo $_POST['productoId'][$key] ?>" id="productoId" class="span12">
                              </td>
                              <td style="width: 160px;">
                                <select name="presentacion[]">
                                  <option value="<?php echo $_POST['presentacion'][$key]?>" data-cantidad="<?php echo $_POST['presentacionCant'][$key] ?>"><?php echo $_POST['presentacionText'][$key] ?></option>
                                </select>
                                <input type="hidden" name="presentacionCant[]" value="<?php echo $_POST['presentacionCant'][$key] ?>" id="presentacionCant" class="span12">
                                <input type="hidden" name="presentacionText[]" value="<?php echo $_POST['presentacionText'][$key] ?>" id="presentacionText" class="span12">
                              </td>
                              <td style="width: 120px;">
                                <select name="unidad[]" id="unidad" class="span12">
                                  <?php foreach ($unidades as $unidad) { ?>
                                    <option value="<?php echo $unidad->id_unidad ?>" <?php echo $_POST['unidad'][$key] == $unidad->id_unidad ? 'selected' : ''; ?>><?php echo $unidad->nombre ?></option>
                                  <?php } ?>
                                </select>
                              </td>
                              <td style="width: 65px;">
                                  <input type="number" name="cantidad[]" value="<?php echo $_POST['cantidad'][$key] ?>" id="cantidad" class="span12 vpositive" min="1">
                              </td>
                              <td style="width: 65px;">
                                  <input type="number" name="faltantes[]" value="<?php echo $_POST['faltantes'][$key] ?>" id="faltantes" class="span12 vpositive" min="0">
                              </td>
                              <td style="width: 90px;">
                                  <input type="text" name="valorUnitario[]" value="<?php echo $_POST['valorUnitario'][$key] ?>" id="valorUnitario" class="span12 vpositive">
                              </td>
                              <td style="width: 66px;">
                                  <select name="traslado[]" id="traslado" class="span12">
                                    <option value="0" <?php echo $_POST['traslado'][$key] === '0' ? 'selected' : '' ?>>0%</option>
                                    <option value="11" <?php echo $_POST['traslado'][$key] === '11' ? 'selected' : ''?>>11%</option>
                                    <option value="16" <?php echo $_POST['traslado'][$key] === '16' ? 'selected' : ''?>>16%</option>
                                  </select>
                                  <input type="hidden" name="trasladoTotal[]" value="<?php echo $_POST['trasladoTotal'][$key] ?>" id="trasladoTotal" class="span12">
                                  <input type="hidden" name="trasladoPorcent[]" value="<?php echo $_POST['trasladoPorcent'][$key] ?>" id="trasladoPorcent" class="span12">
                              </td>
                              <td style="width: 66px;">
                                  <input type="text" name="retTotal[]" value="<?php echo $_POST['retTotal'][$key] ?>" id="retTotal" class="span12" readonly>
                              </td>
                              <td>
                                  <span><?php echo String::formatoNumero($_POST['importe'][$key]) ?></span>
                                  <input type="hidden" name="importe[]" value="<?php echo $_POST['importe'][$key] ?>" id="importe" class="span12 vpositive">
                                  <input type="hidden" name="total[]" value="<?php echo $_POST['total'][$key] ?>" id="total" class="span12 vpositive">
                              </td>
                              <td>
                                  <input type="text" name="observacion[]" value="<?php echo $_POST['observacion'][$key] ?>" id="observacion" class="span12 vpositive">
                              </td>
                              <td style="width: 35px;"><button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button></td>
                            </tr>
                          <?php  }} else {
                               $subtotal = $iva = $total = $retencion = 0;
                               foreach ($orden['info'][0]->productos as $key => $prod) {
                                  $subtotal += $prod->importe;
                                  $iva      += $prod->iva;
                                  $retencion+= $prod->retencion_iva;
                                  $total    += $prod->total;

                                  if ($prod->id_presentacion !== null)
                                  {
                                    $cantidad = $prod->cantidad / $prod->presen_cantidad;
                                    $pu       = $prod->precio_unitario * $prod->presen_cantidad;
                                  }
                                  else
                                  {
                                    $cantidad = $prod->cantidad;
                                    $pu       = $prod->precio_unitario;
                                  }

                                  $readonly = $prod->status === 'a' ? '' : '';
                                  $disabled = $prod->status === 'a' ? '' : '';

                                  $redBg    = $prod->status === 'r' ? 'background-color: #FFE5E5;' : '';

                                  $htmlProdOk = '';
                                  if ( ! isset($_GET['mod']))
                                  {
                                    if ($prod->status === 'a')
                                    {
                                      if ($prodOk)
                                      {
                                        $htmlProdOk = '<input type="checkbox" value="1" class="prodOk" checked><input type="hidden" name="isProdOk[]" value="1" id="idProdOk">';
                                      }
                                      else
                                      {
                                        $htmlProdOk = '<input type="hidden" name="isProdOk[]" value="1" id="idProdOk">';
                                      }
                                    }
                                    else
                                    {
                                      if ($prodOk)
                                      {
                                        $htmlProdOk = '<input type="checkbox" value="1" class="prodOk"><input type="hidden" name="isProdOk[]" value="0" id="idProdOk">';
                                      }
                                      else
                                      {
                                        $htmlProdOk = '<input type="hidden" name="isProdOk[]" value="0" id="idProdOk">';
                                      }
                                    }
                                  }
                                ?>
                               <tr>
                                 <td style="width: 70px;<?php echo $redBg ?>">
                                   <?php echo $htmlProdOk ?>
                                   <?php echo $prod->codigo?>
                                   <input type="hidden" name="codigo[]" value="<?php echo $prod->codigo || '' ?>" class="span12">
                                 </td>
                                 <td style="<?php echo $redBg ?>">
                                     <?php echo $prod->descripcion ?>
                                     <input type="hidden" name="concepto[]" value="<?php echo $prod->descripcion ?>" id="concepto" class="span12">
                                     <input type="hidden" name="productoId[]" value="<?php echo $prod->id_producto ?>" id="productoId" class="span12">
                                 </td>
                                 <td style="width: 160px;<?php echo $redBg ?>">
                                    <?php if ($prod->id_presentacion){ ?>
                                      <select name="presentacion[]" <?php echo $readonly ?>>
                                        <option value="<?php echo $prod->id_presentacion ?>" data-cantidad="<?php echo $prod->presen_cantidad ?>"><?php echo $prod->presentacion .' '.$prod->presen_cantidad .' '.$prod->abreviatura ?></option>
                                      </select>
                                      <input type="hidden" name="presentacionCant[]" value="<?php echo $prod->presen_cantidad ?>" id="presentacionCant" class="span12">
                                      <input type="hidden" name="presentacionText[]" value="<?php echo $prod->presentacion .' '.$prod->presen_cantidad .' '.$prod->abreviatura ?>" id="presentacionText" class="span12">
                                    <?php } else { ?>
                                      <select name="presentacion[]" <?php echo $readonly ?>>
                                        <option value="" data-cantidad=""></option>
                                      </select>
                                      <input type="hidden" name="presentacionCant[]" value="" id="presentacionCant" class="span12">
                                      <input type="hidden" name="presentacionText[]" value="" id="presentacionText" class="span12">
                                    <?php } ?>
                                 </td>
                                 <td style="width: 120px;<?php echo $redBg ?>">
                                   <select name="unidad[]" id="unidad" class="span12" <?php echo $disabled ?>>
                                     <?php foreach ($unidades as $unidad) { ?>
                                       <option value="<?php echo $unidad->id_unidad ?>" <?php echo $prod->id_unidad == $unidad->id_unidad ? 'selected' : ''; ?>><?php echo $unidad->nombre ?></option>
                                     <?php } ?>
                                   </select>
                                 </td>
                                 <td style="width: 65px;<?php echo $redBg ?>">
                                     <input type="number" name="cantidad[]" value="<?php echo $cantidad ?>" id="cantidad" class="span12 vpositive" min="1" <?php echo $readonly ?>>
                                 </td>
                                 <td style="width: 65px;<?php echo $redBg ?>">
                                     <input type="number" name="faltantes[]" value="<?php echo $prod->faltantes ?>" id="faltantes" class="span12 vpositive" min="0" <?php echo $readonly ?>>
                                 </td>
                                 <td style="width: 90px;<?php echo $redBg ?>">
                                     <input type="text" name="valorUnitario[]" value="<?php echo $pu ?>" id="valorUnitario" class="span12 vpositive" <?php echo $readonly ?>>
                                 </td>
                                 <td style="width: 66px;<?php echo $redBg ?>">
                                     <select name="traslado[]" id="traslado" class="span12" <?php echo $disabled ?>>
                                       <option value="0" <?php echo $prod->porcentaje_iva === '0' ? 'selected' : '' ?>>0%</option>
                                       <option value="11" <?php echo $prod->porcentaje_iva === '11' ? 'selected' : ''?>>11%</option>
                                       <option value="16" <?php echo $prod->porcentaje_iva === '16' ? 'selected' : ''?>>16%</option>
                                     </select>
                                     <input type="hidden" name="trasladoTotal[]" value="<?php echo $prod->iva ?>" id="trasladoTotal" class="span12">
                                     <input type="hidden" name="trasladoPorcent[]" value="<?php echo $prod->porcentaje_iva ?>" id="trasladoPorcent" class="span12">
                                 </td>
                                 <td style="width: 66px;<?php echo $redBg ?>">
                                     <input type="text" name="retTotal[]" value="<?php echo $prod->retencion_iva ?>" id="retTotal" class="span12" readonly>
                                 </td>
                                 <td style="<?php echo $redBg ?>">
                                     <span><?php echo String::formatoNumero($prod->importe) ?></span>
                                     <input type="hidden" name="importe[]" value="<?php echo $prod->importe ?>" id="importe" class="span12 vpositive">
                                     <input type="hidden" name="total[]" value="<?php echo $prod->total ?>" id="total" class="span12 vpositive">
                                 </td>
                                 <td style="<?php echo $redBg ?>">
                                    <input type="text" name="observacion[]" value="<?php echo $prod->observacion ?>" id="observacion" class="span12" <?php echo $readonly ?>>
                                </td>
                                 <td style="width: 35px;<?php echo $redBg ?>">
                                  <?php if ($showButton){ ?>
                                    <button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button>
                                  <?php } ?>
                                </td>
                               </tr>
                         <?php  }} ?>
                      </tbody>
                    </table>
                  </div>
                </div>
               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

          <div class="row-fluid">
            <div class="span12">
              <table class="table">
                <thead>
                  <tr>
                    <th style="background-color:#FFF !important;">TOTAL CON LETRA</th>
                    <th style="background-color:#FFF !important;">TOTALES</th>
                    <th style="background-color:#FFF !important;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td rowspan="7">
                        <textarea name="totalLetra" rows="5" class="nokey" style="width:98%;max-width:98%;" id="totalLetra" readonly><?php echo set_value('totalLetra', String::num2letras($total));?></textarea>
                    </td>
                  </tr>
                  <tr>
                    <td><em>Subtotal</em></td>
                    <td id="importe-format"><?php echo String::formatoNumero(set_value('totalImporte', $subtotal))?></td>
                    <input type="hidden" name="totalImporte" id="totalImporte" value="<?php echo set_value('totalImporte', $subtotal); ?>">
                  </tr>
                  <tr>
                    <td>IVA</td>
                    <td id="traslado-format"><?php echo String::formatoNumero(set_value('totalImpuestosTrasladados', $iva))?></td>
                    <input type="hidden" name="totalImpuestosTrasladados" id="totalImpuestosTrasladados" value="<?php echo set_value('totalImpuestosTrasladados', $iva); ?>">
                  </tr>
                  <tr>
                    <td>RET.</td>
                    <td id="retencion-format"><?php echo String::formatoNumero(set_value('totalRetencion', $retencion))?></td>
                    <input type="hidden" name="totalRetencion" id="totalRetencion" value="<?php echo set_value('totalRetencion', $retencion); ?>">
                  </tr>
                  <tr style="font-weight:bold;font-size:1.2em;">
                    <td>TOTAL</td>
                    <td id="total-format"><?php echo String::formatoNumero(set_value('totalOrden', $total))?></td>
                    <input type="hidden" name="totalOrden" id="totalOrden" value="<?php echo set_value('totalOrden', $total); ?>">
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

</div>

<?php if (isset($print)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/compras_ordenes/imprimir/?id=' . $_GET['id'].'&p=true'."'") ?>, '_blank');
    win.focus();
  </script>
<?php } ?>

<?php if (isset($print_faltantes)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/compras_ordenes/imprimir_recibo_faltantes/?id=' . $_GET['id'].'&p=true'."'") ?>, '_blank');
    win.focus();
  </script>
<?php } ?>


<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->