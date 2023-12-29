<div id="content" class="span<?php echo isset($_GET['idf']) ? '12' : '10' ?>">

  <?php
    $titulo = 'Modificar receta';
    if (! isset($_GET['idf'])){ ?>
    <div>
      <ul class="breadcrumb">
        <li>
          <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
        </li>
        <li>
          <a href="<?php echo base_url('panel/recetas/'); ?>">Recetas</a> <span class="divider">/</span>
        </li>
        <li>Agregar</li>
      </ul>
    </div>
  <?php } ?>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> <?php echo $titulo; ?></h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal modificar-receta" action="<?php echo base_url('panel/recetas/modificar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo $receta['info']->empresa ?>" data-next="tipo" autofocus><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo $receta['info']->id_empresa ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="empresa_ap">Empresa Aplicación </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa_ap" class="span11" id="empresa_ap" value="<?php echo $receta['info']->empresa_ap ?>" data-next="tipo">
                  </div>
                  <input type="hidden" name="empresaId_ap" id="empresaId_ap" value="<?php echo $receta['info']->id_empresa_ap ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="formula">Formula </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="formula" class="span11" id="formula" value="<?php echo $receta['info']->formula ?>" placeholder="Selecciona una formula">
                    <input type="hidden" name="formulaId" id="formulaId" value="<?php echo $receta['info']->id_formula ?>">
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="area">Cultivo</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="area" class="span11" id="area" value="<?php echo $receta['info']->area ?>" placeholder="Limon, Piña" readonly>
                  </div>
                  <input type="hidden" name="areaId" id="areaId" value="<?php echo $receta['info']->id_area ?>">
                </div>
              </div>

              <div class="control-group" id="ranchosGrup">
                <label class="control-label" for="rancho">Areas / Ranchos </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="rancho" class="span11" id="rancho" value="<?php echo set_value('rancho') ?>" placeholder="Milagro A, Linea 1">
                  </div>
                </div>
                <ul class="tags" id="tagsRanchoIds">
                <?php if (isset($receta['info']->rancho)) {
                foreach ($receta['info']->rancho as $key => $rancho) { ?>
                  <li class=""><span class="tag"><?php echo $rancho->nombre ?></span>
                    <input type="hidden" name="ranchoId[]" class="ranchoId" value="<?php echo $rancho->id_rancho ?>">
                    <input type="hidden" name="ranchoText[]" class="ranchoText" value="<?php echo $rancho->nombre ?>">
                  </li>
                <?php }} ?>
                </ul>
              </div>

              <div class="control-group" id="centrosCostosGrup">
                <label class="control-label" for="centroCosto">Centro de costo </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="centroCosto" class="span11" id="centroCosto" value="<?php echo set_value('centroCosto') ?>" placeholder="Mantenimiento, Gasto general">
                  </div>
                </div>
                <ul class="tags" id="tagsCCIds">
                <?php if (isset($receta['info']->centroCosto)) {
                foreach ($receta['info']->centroCosto as $key => $centroCosto) { ?>
                  <li class="">
                    <span class="tag"><?php echo $centroCosto->nombre ?></span>
                    <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCosto->id_centro_costo ?>">
                    <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $centroCosto->nombre ?>">

                    <input type="hidden" name="centroCostoHec[]" class="centroCostoHec" value="<?php echo $centroCosto->hectareas ?>">
                    <input type="hidden" name="centroCostoNoplantas[]" class="centroCostoNoplantas" value="<?php echo $centroCosto->no_plantas ?>">
                  </li>
                <?php }} ?>
                </ul>
              </div>

              <div class="control-group">
                <label class="control-label" for="objetivo">Objetivo </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="objetivo" class="span11" id="objetivo" value="<?php echo $receta['info']->objetivo ?>">
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="solicito">Solicito</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="solicito" class="span11" id="solicito" value="<?php echo $receta['info']->solicito ?>" placeholder="" required>
                  </div>
                </div>
                  <input type="hidden" name="solicitoId" id="solicitoId" value="<?php echo $receta['info']->id_solicito ?>" required>
              </div>

              <div class="control-group">
                <label class="control-label" for="autorizo">Autoriza</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="autorizo" class="span11" id="autorizo" value="<?php echo $receta['info']->autorizo ?>" placeholder="" required>
                  </div>
                </div>
                  <input type="hidden" name="autorizoId" id="autorizoId" value="<?php echo $receta['info']->id_autorizo ?>" required>
              </div>

            </div>

            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="tipo">Tipo de Orden</label>
                <div class="controls">
                  <select name="tipo" class="span9" id="tipo" data-next="formula">
                    <option value="kg" <?php echo set_select('tipo', 'kg', ($receta['info']->tipo==='kg')); ?>>Kg</option>
                    <option value="lts" <?php echo set_select('tipo', 'lts', ($receta['info']->tipo==='lts')); ?>>Lts</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio_formula">Folio Formula</label>
                <div class="controls">
                  <input type="text" name="folio_formula" class="span9" id="folio_formula" value="<?php echo $receta['info']->folio_formula ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio Receta</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo $receta['info']->folio ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio_hoja">Folio Receta Hoja</label>
                <div class="controls">
                  <input type="text" name="folio_hoja" class="span9" id="folio_hoja" value="<?php echo $receta['info']->folio_hoja; ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="date" name="fecha" class="span9" id="fecha" value="<?php echo $receta['info']->fecha ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fecha_aplicacion">Fecha Aplicación</label>
                <div class="controls">
                  <input type="date" name="fecha_aplicacion" class="span9" id="fecha_aplicacion" value="<?php echo $receta['info']->fecha_aplicacion ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="calendario">Calendario</label>
                <div class="controls">
                  <select name="calendario" class="span9" id="calendario" data-next="formula" required>
                    <?php foreach ($calendarios as $key => $value): ?>
                    <option value="<?php echo $value->id ?>" <?php echo ($value->id == $receta['info']->id_recetas_calendario? 'selected': '') ?>><?php echo $value->nombre ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <div class="row-fluid">
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> Programa de Aplicación</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">
                  <div class="span3">
                    Etapa
                    <!-- <input type="text" name="a_etapa" class="span12 datosapl" id="a_etapa" value="<?php echo $receta['info']->a_etapa ?>"> -->
                    <select name="a_etapa" class="span12 datosapl" id="a_etapa">
                      <option value=""></option>
                      <option value="Prep. Terreno" <?php echo set_select('a_etapa', 'Prep. Terreno', ($receta['info']->a_etapa==='Prep. Terreno')); ?>>Prep. Terreno</option>
                      <option value="Estab. Cultivo" <?php echo set_select('a_etapa', 'Estab. Cultivo', ($receta['info']->a_etapa==='Estab. Cultivo')); ?>>Estab. Cultivo</option>
                      <option value="D/Planta" <?php echo set_select('a_etapa', 'D/Planta', ($receta['info']->a_etapa==='D/Planta')); ?>>D/Planta</option>
                      <option value="D/Fruta" <?php echo set_select('a_etapa', 'D/Fruta', ($receta['info']->a_etapa==='D/Fruta')); ?>>D/Fruta</option>
                      <option value="Semillero" <?php echo set_select('a_etapa', 'Semillero', ($receta['info']->a_etapa==='Semillero')); ?>>Semillero</option>
                    </select>
                  </div>

                  <div class="span3">
                    Ciclo
                    <!-- <input type="text" name="a_ciclo" class="span12 datosapl" id="a_ciclo" value="<?php echo $receta['info']->a_ciclo ?>"> -->
                    <select name="a_ciclo" class="span12 datosapl" id="a_ciclo">
                      <option value=""></option>
                      <option value="1" <?php echo set_select('a_ciclo', '1', ($receta['info']->a_ciclo==='1')); ?>>1</option>
                      <option value="2" <?php echo set_select('a_ciclo', '2', ($receta['info']->a_ciclo==='2')); ?>>2</option>
                      <option value="3" <?php echo set_select('a_ciclo', '3', ($receta['info']->a_ciclo==='3')); ?>>3</option>
                      <option value="4" <?php echo set_select('a_ciclo', '4', ($receta['info']->a_ciclo==='4')); ?>>4</option>
                      <option value="5" <?php echo set_select('a_ciclo', '5', ($receta['info']->a_ciclo==='5')); ?>>5</option>
                      <option value="6" <?php echo set_select('a_ciclo', '6', ($receta['info']->a_ciclo==='6')); ?>>6</option>
                      <option value="7" <?php echo set_select('a_ciclo', '7', ($receta['info']->a_ciclo==='7')); ?>>7</option>
                      <option value="8" <?php echo set_select('a_ciclo', '8', ($receta['info']->a_ciclo==='8')); ?>>8</option>
                      <option value="9" <?php echo set_select('a_ciclo', '9', ($receta['info']->a_ciclo==='9')); ?>>9</option>
                      <option value="10" <?php echo set_select('a_ciclo', '10', ($receta['info']->a_ciclo==='10')); ?>>10</option>
                    </select>
                  </div>

                  <div class="span3">
                    DDS
                    <input type="text" name="a_dds" class="span12 datosapl" id="a_dds" value="<?php echo $receta['info']->a_dds ?>">
                  </div>

                  <div class="span3">
                    Turno
                    <!-- <input type="text" name="a_turno" class="span12 datosapl" id="a_turno" value="<?php echo $receta['info']->a_turno ?>"> -->
                    <select name="a_turno" class="span12 datosapl" id="a_turno">
                      <option value=""></option>
                      <option value="Por el Dia (7am / 4pm)" <?php echo set_select('a_turno', 'Por el Dia (7am / 4pm)', ($receta['info']->a_turno==='Por el Dia (7am / 4pm)')); ?>>Por el Dia (7am / 4pm)</option>
                      <option value="Amanecer (6am / 9am)" <?php echo set_select('a_turno', 'Amanecer (6am / 9am)', ($receta['info']->a_turno==='Amanecer (6am / 9am)')); ?>>Amanecer (6am / 9am)</option>
                      <option value="Tard-Noch (5pm / 10pm)" <?php echo set_select('a_turno', 'Tard-Noch (5pm / 10pm)', ($receta['info']->a_turno==='Tard-Noch (5pm / 10pm)')); ?>>Tard-Noch (5pm / 10pm)</option>
                      <option value="Madrudada (4am / 7am)" <?php echo set_select('a_turno', 'Madrudada (4am / 7am)', ($receta['info']->a_turno==='Madrudada (4am / 7am)')); ?>>Madrudada (4am / 7am)</option>
                    </select>
                  </div>
                </div>

                <div class="row-fluid">
                  <div class="span3">
                    Via
                    <!-- <input type="text" name="a_via" class="span12 datosapl" id="a_via" value="<?php echo $receta['info']->a_via ?>"> -->
                    <select name="a_via" class="span12 datosapl" id="a_via">
                      <option value=""></option>
                      <option value="Elaboración de Solución" <?php echo set_select('a_via', 'Elaboración de Solución', ($receta['info']->a_via==='Elaboración de Solución')); ?>>Elaboración de Solución</option>
                      <option value="Elaboración de Composta" <?php echo set_select('a_via', 'Elaboración de Composta', ($receta['info']->a_via==='Elaboración de Composta')); ?>>Elaboración de Composta</option>
                      <option value="Aplicación Foliar" <?php echo set_select('a_via', 'Aplicación Foliar', ($receta['info']->a_via==='Aplicación Foliar')); ?>>Aplicación Foliar</option>
                      <option value="Aplicación Solida al Suelo" <?php echo set_select('a_via', 'Aplicación Solida al Suelo', ($receta['info']->a_via==='Aplicación Solida al Suelo')); ?>>Aplicación Solida al Suelo</option>
                      <option value="Aplicación en Sistema Riego" <?php echo set_select('a_via', 'Aplicación en Sistema Riego', ($receta['info']->a_via==='Aplicación en Sistema Riego')); ?>>Aplicación en Sistema Riego</option>
                      <option value="Aplicación en Drench a Piso" <?php echo set_select('a_via', 'Aplicación en Drench a Piso', ($receta['info']->a_via==='Aplicación en Drench a Piso')); ?>>Aplicación en Drench a Piso</option>
                      <option value="Aplicación en Drench a Planta" <?php echo set_select('a_via', 'Aplicación en Drench a Planta', ($receta['info']->a_via==='Aplicación en Drench a Planta')); ?>>Aplicación en Drench a Planta</option>
                    </select>
                  </div>

                  <div class="span3">
                    Aplicación
                    <input type="text" name="a_aplic" class="span12 datosapl" id="a_aplic" value="<?php echo $receta['info']->a_aplic ?>">
                  </div>

                  <div class="span3">
                    Equipo
                    <!-- <input type="text" name="a_equipo" class="span12 datosapl" id="a_equipo" value="<?php echo $receta['info']->a_equipo ?>"> -->
                    <select name="a_equipo" class="span12 datosapl" id="a_equipo">
                      <option value=""></option>
                      <option value="Pileta-Raquis" <?php echo set_select('a_equipo', 'Pileta-Raquis', ($receta['info']->a_equipo==='Pileta-Raquis')); ?>>Pileta-Raquis</option>
                      <option value="Pileta-Bioles" <?php echo set_select('a_equipo', 'Pileta-Bioles', ($receta['info']->a_equipo==='Pileta-Bioles')); ?>>Pileta-Bioles</option>
                      <option value="Rotoplas" <?php echo set_select('a_equipo', 'Rotoplas', ($receta['info']->a_equipo==='Rotoplas')); ?>>Rotoplas</option>
                      <option value="Biodigestor" <?php echo set_select('a_equipo', 'Biodigestor', ($receta['info']->a_equipo==='Biodigestor')); ?>>Biodigestor</option>
                      <option value="Boom" <?php echo set_select('a_equipo', 'Boom', ($receta['info']->a_equipo==='Boom')); ?>>Boom</option>
                      <option value="Aspersora" <?php echo set_select('a_equipo', 'Aspersora', ($receta['info']->a_equipo==='Aspersora')); ?>>Aspersora</option>
                      <option value="Aguilón" <?php echo set_select('a_equipo', 'Aguilón', ($receta['info']->a_equipo==='Aguilón')); ?>>Aguilón</option>
                      <option value="Tambo" <?php echo set_select('a_equipo', 'Tambo', ($receta['info']->a_equipo==='Tambo')); ?>>Tambo</option>
                      <option value="Garrafa" <?php echo set_select('a_equipo', 'Garrafa', ($receta['info']->a_equipo==='Garrafa')); ?>>Garrafa</option>
                      <option value="Motobomba" <?php echo set_select('a_equipo', 'Motobomba', ($receta['info']->a_equipo==='Motobomba')); ?>>Motobomba</option>
                      <option value="Termo" <?php echo set_select('a_equipo', 'Termo', ($receta['info']->a_equipo==='Termo')); ?>>Termo</option>
                      <option value="Mochila" <?php echo set_select('a_equipo', 'Mochila', ($receta['info']->a_equipo==='Mochila')); ?>>Mochila</option>
                      <option value="Avioneta" <?php echo set_select('a_equipo', 'Avioneta', ($receta['info']->a_equipo==='Avioneta')); ?>>Avioneta</option>
                      <option value="Dron" <?php echo set_select('a_equipo', 'Dron', ($receta['info']->a_equipo==='Dron')); ?>>Dron</option>
                      <option value="Sistema Riego" <?php echo set_select('a_equipo', 'Sistema Riego', ($receta['info']->a_equipo==='Sistema Riego')); ?>>Sistema Riego</option>
                      <option value="Cama-Humus" <?php echo set_select('a_equipo', 'Cama-Humus', ($receta['info']->a_equipo==='Cama-Humus')); ?>>Cama-Humus</option>
                    </select>
                  </div>

                  <div class="span3 opcVolumenlts" style="display: none;">
                    Volumen (lts)
                    <select name="a_volumen" class="span12 datosapl" id="a_volumen" data-value="<?php echo $receta['info']->a_volumen ?>">
                      <option value=""></option>
                    </select>
                  </div>
                </div>

                <div class="row-fluid">
                  <div class="span11">
                    Observaciones
                    <input type="text" name="a_observaciones" class="span12 datosapl" id="a_observaciones" value="<?php echo $receta['info']->a_observaciones ?>" data-next="dosis_planta|ha_bruta">
                  </div>
                </div>

              </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

          <div class="row-fluid">
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> <span class="titulo-box-kglts">Datos Kg</span></h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="span3 datos-kg">
                  Dosis Planta
                  <input type="number" step="any" name="dosis_planta" class="span12 sikey datoskl" id="dosis_planta" value="<?php echo $receta['info']->dosis_planta ?>" data-next="planta_ha">
                </div>

                <div class="span3 datos-lts">
                  Ha Bruta
                  <input type="number" step="any" name="ha_bruta" class="span12 sikey datoskl" id="ha_bruta" value="<?php echo $receta['info']->ha_bruta ?>" data-next="planta_ha">
                </div>

                <div class="span3">
                  Plantas x Ha
                  <input type="number" step="any" name="planta_ha" class="span12 sikey datoskl" id="planta_ha" value="<?php echo $receta['info']->planta_ha ?>" data-next="ha_neta|no_plantas">
                </div>

                <div class="span3">
                  Ha Netas
                  <input type="number" step="any" name="ha_neta" class="span12 sikey datoskl" id="ha_neta" value="<?php echo $receta['info']->ha_neta ?>" data-next="fconcepto">
                </div>

                <div class="span3">
                  No plantas
                  <input type="number" step="any" name="no_plantas" class="span12 sikey datoskl" id="no_plantas" value="<?php echo $receta['info']->no_plantas ?>" data-next="carga1" style="display: none;"> <!-- readonly -->
                </div>

                <div class="span3 datos-kg">
                  Kg Total
                  <input type="number" step="any" name="kg_totales" class="span12 sikey datoskl" id="kg_totales" value="<?php echo $receta['info']->kg_totales ?>"> <!-- readonly -->
                </div>

                <div class="span3 datos-lts">
                  Carga 1
                  <input type="number" step="any" name="carga1" class="span12 sikey datoskl" id="carga1" value="<?php echo $receta['info']->carga1 ?>" data-next="dosis_equipo">
                </div>

                <div class="span3 datos-lts">
                  Dosis Equipo Carga 1
                  <input type="number" step="any" name="dosis_equipo" class="span12 sikey datoskl" id="dosis_equipo" value="<?php echo $receta['info']->dosis_equipo ?>" data-next="carga2">
                </div>

                <div class="span3 datos-lts">
                  Carga 2
                  <input type="number" step="any" name="carga2" class="span12 sikey datoskl" id="carga2" value="<?php echo $receta['info']->carga2 ?>" data-next="ph">
                </div>

                <div class="span3 datos-lts">
                  Lts de Cargas Extras
                  <input type="number" step="any" name="dosis_equipo_car2" class="span12 sikey datoskl" id="dosis_equipo_car2" value="<?php echo $receta['info']->dosis_equipo_car2 ?>"> <!-- readonly -->
                </div>

                <div class="span3 datos-lts">
                  PH
                  <input type="number" step="any" name="ph" class="span12 sikey datoskl" id="ph" value="<?php echo $receta['info']->ph ?>" data-next="fconcepto">
                </div>
              </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->


          <div class="row-fluid" id="productos">  <!-- Box Productos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-barcode"></i> Productos <span id="show_info_prod" style="display:none;"><i class="icon-hand-right"></i> <span>Existencia: 443 | Stok: 43</span></span></h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">

                <div class="row-fluid">

                  <div class="span12 mquit">
                    <div class="span3">
                      <!-- data-next="fcodigo" -->
                      <input type="text" class="span12" id="fcodigo" placeholder="Codigo" data-next="fcodigo">
                    </div><!--/span3s -->
                    <div class="span6">
                      <div class="input-append span12">
                        <input type="text" class="span10" id="fconcepto" placeholder="Producto / Descripción">
                        <a href="<?php echo base_url('panel/productos').'?modal=true' ?>" rel="superbox-70x550" class="btn btn-info" type="button" data-rel="tooltip" data-title="Agregar Producto"><i class="icon-plus" ></i></a>
                      </div>
                      <input type="hidden" class="span1" id="fconceptoId">
                    </div><!--/span3s -->

                  </div><!--/span12 -->
                  <br><br>
                  <div class="span12 mquit">
                    <div class="span3">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant." data-next="fprecio">
                    </div><!--/span3s -->

                    <div class="span3">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fprecio" min="0.01" placeholder="Precio" data-next="btnAddProd">
                    </div><!--/span3s -->

                    <div class="span2 offset3">
                      <button type="button" class="btn btn-success span12" id="btnAddProd">Agregar</button>
                    </div><!--/span2 -->
                  </div>

                </div><!--/row-fluid -->
                <br>
                <div class="row-fluid">
                  <div class="span12 mquit">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                      <thead>
                        <tr>
                          <th rowspan="2" style="vertical-align: middle;">%</th>
                          <th rowspan="2" style="vertical-align: middle;">PRODUCTO</th>
                          <th rowspan="2" style="vertical-align: middle;">CANT.</th>
                          <th rowspan="2" class="tipostyle" style="vertical-align: middle;display: none;">CARGA 1</th>
                          <th rowspan="2" class="tipostyle" style="vertical-align: middle;display: none;">CARGA 2</th>
                          <th rowspan="2" style="vertical-align: middle;">APLI TOTAL</th>
                          <th rowspan="2" style="vertical-align: middle;">PRECIO</th>
                          <th rowspan="2" style="vertical-align: middle;">IMPORTE</th>
                          <th rowspan="2" style="vertical-align: middle;">OPC</th>
                        </tr>
                      </thead>
                      <tbody class="bodyproducs">
                        <?php if (isset($receta['info']->productos)) {
                        foreach ($receta['info']->productos as $key => $prod) { ?>

                          <tr class="rowprod">
                            <td style="width: 50px;">
                              <span class="percent"><?php echo $prod->percent ?></span>
                              <input type="hidden" name="percent[]" value="<?php echo $prod->percent ?>" id="percent">
                            </td>
                            <td>
                              <?php echo $prod->producto ?>
                              <input type="hidden" name="concepto[]" value="<?php echo $prod->producto ?>" id="concepto" class="span12">
                              <input type="hidden" name="productoId[]" value="<?php echo $prod->id_producto ?>" id="productoId" class="span12">
                              <input type="hidden" name="dosis_ha[]" value="<?php echo $prod->dosis_ha ?>" id="dosis_ha" class="span12">
                            </td>
                            <td style="width: 80px;">
                              <input type="number" step="any" name="cantidad[]" value="<?php echo $prod->dosis_mezcla ?>" id="cantidad" class="span12 vpositive" min="0">
                            </td>
                            <td class="tipostyle" style="width: 80px;display: none;">
                                <input type="number" step="any" name="pcarga1[]" value="<?php echo $prod->dosis_carga1 ?>" id="pcarga1" class="span12 vpositive" min="0"> <!-- readonly -->
                            </td>
                            <td class="tipostyle" style="width: 80px;display: none;">
                                <input type="number" step="any" name="pcarga2[]" value="<?php echo $prod->dosis_carga2 ?>" id="pcarga2" class="span12 vpositive" min="0"> <!-- readonly -->
                            </td>
                            <td style="width: 130px;">
                              <input type="number" step="any" name="aplicacion_total[]" value="<?php echo $prod->aplicacion_total ?>" id="aplicacion_total" class="span12 vpositive" min="0"> <!-- readonly -->
                            </td>
                            <td style="width: 130px;">
                              <input type="number" step="any" name="precio[]" value="<?php echo $prod->precio ?>" id="precio" class="span12 vpositive" min="0">
                            </td>
                            <td style="width: 150px;">
                              <input type="number" step="any" name="importe[]" value="<?php echo $prod->importe ?>" id="importe" class="span12 vpositive" min="0" readonly>
                            </td>
                            <td style="width: 50px;">
                              <button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button>
                            </td>
                          </tr>
                        <?php }} ?>
                      </tbody>
                      <tfoot>
                        <tr style="font-weight: bold;">
                          <td id="ttpercent"></td>
                          <td></td>
                          <td id="ttcantidad"></td>
                          <th id="ttcargo1" class="tipostyle" style="display: none;"></th>
                          <th id="ttcargo2" class="tipostyle" style="display: none;"></th>
                          <td id="ttaplicacion_total"></td>
                          <td></td>
                          <td>
                            <span id="ttimporte"></span>
                            <input type="hidden" name="total_importe" id="total_importe" value="<?php echo $receta['info']->total_importe ?>">
                          </td>
                          <td></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->



        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

</div>

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
