$(function(){
  autocompletesCP();

  eventAddCpUbicaciones();
  eventAddCpCantidadTransporta();
  eventAddCpPedimentos();
  eventAddCpGuiaIdentificacion();
  eventAddCpProductoModal();
  eventAddCpAutotransporteRemolques();
  eventAddCpPartesTransporte();
});

function eventAddCpUbicaciones() {
  $('#btn-add-CpUbicaciones').click(function(event) {
    let htmll = `<div class="ubicacionn">
      <table class="table table-striped table-cpOrigen">
        <thead>
          <tr>
            <th>Tipo Ubicacion <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>ID Ubicacion <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>RFC <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Nombre <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Num Reg Id Trib <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Residencia Fiscal <i class="icon-question-sign helpover" data-title=""></i></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="center">
              <select name="cp[ubicaciones][][tipoUbicacion]" id="cp_ubic_tipoUbicacion">
                <option value=""></option>
                <option value="Origen">Origen</option>
                <option value="Destino">Destino</option>
              </select>
            </td>
            <td class="center"><input type="text" name="cp[ubicaciones][][idUbicacion]" value="" id="cp_ubic_idUbicacion" minlength="8" maxlength="8" class="span12 sikey" data-next="cp_ubic_rfcRemitenteDestinatario"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][rfcRemitenteDestinatario]" value="" id="cp_ubic_rfcRemitenteDestinatario" minlength="12" maxlength="13" class="span12 sikey" data-next="cp_ubic_nombreRemitenteDestinatario"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][nombreRemitenteDestinatario]" value="" id="cp_ubic_nombreRemitenteDestinatario" minlength="1" maxlength="254" class="span12 sikey" data-next="cp_ubic_numRegIdTrib"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][numRegIdTrib]" value="" id="cp_ubic_numRegIdTrib" minlength="6" maxlength="40" class="span12 sikey" data-next="cp_ubic_residenciaFiscal_text"></td>
            <td class="center">
              <input type="text" name="cp[ubicaciones][][residenciaFiscal_text]" value="" id="cp_ubic_residenciaFiscal_text" maxlength="40" class="span12 sikey" data-next="cp_ubic_numEstacion">
              <input type="hidden" name="cp[ubicaciones][][residenciaFiscal]" value="" id="cp_ubic_residenciaFiscal" maxlength="40" class="span12 sikey">
            </td>
          </tr>
          <tr>
            <th>Num Estacion <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Nombre Estacion <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Navegacion Trafico <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Fecha y Hora de Salida <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Tipo Estación <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Distancia Recorrida (Km) <i class="icon-question-sign helpover" data-title=""></i></th>
          </tr>
          <tr>
            <td class="center">
              <input type="text" name="cp[ubicaciones][][numEstacion]" value="" id="cp_ubic_numEstacion" maxlength="60" class="span12 sikey" data-next="cp_ubic_nombreEstacion">
              <input type="hidden" name="cp[ubicaciones][][numEstacion_text]" value="" id="cp_ubic_numEstacion_text" maxlength="60" class="span12 sikey">
            </td>
            <td class="center"><input type="text" name="cp[ubicaciones][][nombreEstacion]" value="" id="cp_ubic_nombreEstacion" minlength="1" maxlength="50" class="span12 sikey" data-next="cp_ubic_navegacionTrafico"></td>
            <td class="center">
              <select name="cp[ubicaciones][][navegacionTrafico]" id="cp_ubic_navegacionTrafico">
                <option value=""></option>
                <option value="Altura">Altura</option>
                <option value="Cabotaje">Cabotaje</option>
              </select>
            </td>
            <td class="center"><input type="datetime-local" name="cp[ubicaciones][][fechaHoraSalida]" value="" id="cp_ubic_fechaHoraSalida" class="span12 sikey" minlength="1" maxlength="12" data-next="cce_destinatario_dom_colonia"></td>
            <td class="center">
              <select name="cp[ubicaciones][][tipoEstacion]" class="span12 sikey" id="cp_tipoEstacion" data-next="cp_totalDistRec">
                <option value=""></option>
                <option value="01">01 - Origen Nacional</option>
                <option value="02">02 - Intermedia</option>
                <option value="03">03 - Destino Final Nacional</option>
              </select>
            </td>
            <td class="center">
              <input type="text" name="cp[ubicaciones][][distanciaRecorrida]" class="span12 sikey" id="cp_ubicaciones_distanciaRecorrida" value="" placeholder="Nombre" data-next="cce_destinatario_dom_calle">
            </td>
          </tr>
        </tbody>
      </table>

      <table class="table table-striped table-cpDomicilio">
        <thead>
          <tr>
            <th>Calle <i class="icon-question-sign helpover" data-title="Calle: Atributo requerido sirve para precisar la calle en que está ubicado el domicilio del destinatario de la mercancía."></i></th>
            <th>No. Exterior <i class="icon-question-sign helpover" data-title="No. Exterior: Atributo opcional sirve para expresar el número exterior en donde se ubica el domicilio del destinatario de la mercancía."></i></th>
            <th>No. Interior <i class="icon-question-sign helpover" data-title="No. Interior: Campo opcional sirve para expresar información adicional para especificar la ubicación cuando calle y número exterior no resulten suficientes para determinar la ubicación precisa del inmuebleAtributo opcional sirve para expresar el número interior, en caso de existir, en donde se ubica el domicilio del destinatario de la mercancía."></i></th>
            <th>Referencia <i class="icon-question-sign helpover" data-title="Referencia: Atributo opcional para expresar una referencia geográfica adicional que permita una más fácil o precisa ubicación del domicilio del destinatario de la mercancía, por ejemplo las coordenadas GPS."></i></th>
            <th>Pais <i class="icon-question-sign helpover" data-title="Pais: Atributo requerido que sirve para precisar el país donde  se encuentra ubicado el destinatario de la mercancía."></i></th>
            <th>Estado <i class="icon-question-sign helpover" data-title="Estado: Atributo requerido para señalar el estado, entidad, región, comunidad u otra figura análoga en donde  se encuentra ubicado el  domicilio del destinatario de la mercancía."></i></th>
            <th>Municipio <i class="icon-question-sign helpover" data-title="Municipio: Atributo opcional que sirve para precisar el municipio, delegación, condado u otro análogo en donde se encuentra ubicado el destinatario de la mercancía."></i></th>
            <th>Localidad <i class="icon-question-sign helpover" data-title="Localidad: Atributo opcional que sirve para precisar la ciudad, población, distrito u otro análogo en donde se ubica el domicilio del  destinatario de la mercancía."></i></th>
            <th>Codigo Postal <i class="icon-question-sign helpover" data-title="Codigo Postal: Atributo requerido que sirve para asentar el código postal (PO, BOX) en donde se encuentra ubicado el domicilio del destinatario de la mercancía."></i></th>
            <th>Colonia <i class="icon-question-sign helpover" data-title="Colonia: Atributo opcional sirve para expresar la colonia o dato análogo en donde se ubica el domicilio del destinatario de la mercancía."></i></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="center"><input type="text" name="cp[ubicaciones][][domicilio][calle]" value="" id="cp_ubic_dom_calle" minlength="1" maxlength="100" class="span12 sikey" data-next="cp_ubic_dom_numeroExterior"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][domicilio][numeroExterior]" value="" id="cp_ubic_dom_numeroExterior" minlength="1" maxlength="55" class="span12 sikey" data-next="cp_ubic_dom_numeroInterior"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][domicilio][numeroInterior]" value="" id="cp_ubic_dom_numeroInterior" minlength="1" maxlength="55" class="span12 sikey" data-next="cp_ubic_dom_referencia"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][domicilio][referencia]" value="" id="cp_ubic_dom_referencia" minlength="1" maxlength="250" class="span12 sikey" data-next="cp_ubic_dom_pais"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][domicilio][pais]" value="" id="cp_ubic_dom_pais" maxlength="40" class="span12 sikey" data-next="cp_ubic_dom_estado"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][domicilio][estado]" value="" id="cp_ubic_dom_estado" maxlength="60" class="span12 sikey" data-next="cp_ubic_dom_municipio"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][domicilio][municipio]" value="" id="cp_ubic_dom_municipio" minlength="1" maxlength="120" class="span12 sikey" data-next="cp_ubic_dom_localidad"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][domicilio][localidad]" value="" id="cp_ubic_dom_localidad" minlength="1" maxlength="12" class="span12 sikey" data-next="cp_ubic_dom_codigopostal"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][domicilio][codigoPostal]" value="" class="span12 sikey" id="cp_ubic_dom_codigopostal" minlength="1" maxlength="12" data-next="cp_ubic_dom_colonia"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][domicilio][colonia]" value="" id="cp_ubic_dom_colonia" minlength="1" maxlength="120" class="span12 sikey" data-next="cp_ubic_dom_calle"></td>
          </tr>
        </tbody>
      </table>

      <hr class="div">
    </div>`;

    $('#boxUbicaciones').append(htmll);
  });
}

function eventAddCpCantidadTransporta() {
  $('#btn-add-cantidadTransporta').click(function(event) {
    let htmll = `<tr>
      <td><input type="number" class="mcpsat_cantidadTransporta_cantidad" value=""></td>
      <td><input type="text" class="mcpsat_cantidadTransporta_idOrigen" value=""></td>
      <td><input type="text" class="mcpsat_cantidadTransporta_idDestino" value=""></td>
      <td>
        <select class="mcpsat_cantidadTransporta_cvesTransporte">
          <option></option>
          <option value="01">01 - Autotransporte Federal</option>
          <option value="02">02 - Transporte Marítimo</option>
          <option value="03">03 - Transporte Aéreo</option>
          <option value="04">04 - Transporte Ferroviario</option>
          <option value="05">05 - Ducto</option>
        </select>
      </td>
      <td><i class="icon-ban-circle delete"></i></td>
    </tr>`;

    $('#table-mcpsat_cantidadTransporta tbody').append(htmll);
  });

  $('#table-mcpsat_cantidadTransporta tbody').on('click', 'i.delete', function(){
    const $tr = $(this).parent().parent();
    $tr.remove();
  });
}

function eventAddCpPedimentos() {
  $('#btn-add-cp-pedimentos').click(function(event) {
    let htmll = `<tr>
      <td><input type="number" class="mcpsat_pedimentos_pedimento" value="" placeholder="52 45 4214 4213546"></td>
      <td><i class="icon-ban-circle delete"></i></td>
    </tr>`;

    $('#table-mcpsat_pedimentos tbody').append(htmll);
  });

  $('#table-mcpsat_pedimentos tbody').on('click', 'i.delete', function(){
    const $tr = $(this).parent().parent();
    $tr.remove();
  });
}

function eventAddCpGuiaIdentificacion() {
  $('#btn-add-cp-guias').click(function(event) {
    let htmll = `<tr>
      <td><input type="number" step="any" class="mcpsat_guia_numeroGuiaIdentificacion" value=""></td>
      <td><input type="text" class="mcpsat_guia_descripGuiaIdentificacion" value=""></td>
      <td><input type="number" step="any" class="mcpsat_guia_pesoGuiaIdentificacion" value=""></td>
      <td><i class="icon-ban-circle delete"></i></td>
    </tr>`;

    $('#table-mcpsat_guias tbody').append(htmll);
  });

  $('#table-mcpsat_guias tbody').on('click', 'i.delete', function(){
    const $tr = $(this).parent().parent();
    $tr.remove();
  });
}

function eventAddCpProductoModal() {
  var cpnumrowsmercans = 0;
  $("#btn-add-CpProductoModal").click(function(event) {

    let cantidadTransporta = '', trrm = undefined, guias = '', pedimentos = '';
    let objson = {
      datos: {},
      pedimentos: [],
      guias: [],
      cantidadTransporta: [],
      detalleMercancia: {}
    };

    $("#table-mcpsat_pedimentos tbody tr").each(function(index, el) {
      pedimentos += `
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][pedimentos][${index}][pedimento]" value="${$('.mcpsat_pedimentos_pedimento', el).val()}" class="cpMercans-pedimentos-pedimento">`;
      objson.pedimentos.push({pedimento: $('.mcpsat_pedimentos_pedimento', el).val()});
    });
    $("#table-mcpsat_guias tbody tr").each(function(index, el) {
      guias += `
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][guiasIdentificacion][${index}][numeroGuiaIdentificacion]" value="${$('.mcpsat_guia_numeroGuiaIdentificacion', el).val()}" class="cpMercans-guia-numeroGuiaIdentificacion">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][guiasIdentificacion][${index}][descripGuiaIdentificacion]" value="${$('.mcpsat_guia_descripGuiaIdentificacion', el).val()}" class="cpMercans-guia-descripGuiaIdentificacion">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][guiasIdentificacion][${index}][pesoGuiaIdentificacion]" value="${$('.mcpsat_guia_pesoGuiaIdentificacion', el).val()}" class="cpMercans-guia-pesoGuiaIdentificacion">`;
      objson.guias.push({
        numeroGuiaIdentificacion: $('.mcpsat_guia_numeroGuiaIdentificacion', el).val(),
        descripGuiaIdentificacion: $('.mcpsat_guia_descripGuiaIdentificacion', el).val(),
        pesoGuiaIdentificacion: $('.mcpsat_guia_pesoGuiaIdentificacion', el).val(),
      });
    });
    $("#table-mcpsat_cantidadTransporta tbody tr").each(function(index, el) {
      cantidadTransporta += `
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][cantidad]" value="${$('.mcpsat_cantidadTransporta_cantidad', el).val()}" class="cpMercans-cantTrans-cantidad">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][idOrigen]" value="${$('.mcpsat_cantidadTransporta_idOrigen', el).val()}" class="cpMercans-cantTrans-idOrigen">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][idDestino]" value="${$('.mcpsat_cantidadTransporta_idDestino', el).val()}" class="cpMercans-cantTrans-idDestino">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][cvesTransporte]" value="${$('.mcpsat_cantidadTransporta_cvesTransporte', el).val()}" class="cpMercans-cantTrans-cvesTransporte">`;
      objson.cantidadTransporta.push({
        cantidad: $('.mcpsat_cantidadTransporta_cantidad', el).val(),
        idOrigen: $('.mcpsat_cantidadTransporta_idOrigen', el).val(),
        idDestino: $('.mcpsat_cantidadTransporta_idDestino', el).val(),
        cvesTransporte: $('.mcpsat_cantidadTransporta_cvesTransporte', el).val(),
      });
    });

    objson.datos = {
      bienesTransp: $('#mcpsat_bienesTransp').val(),
      bienesTransp_text: $('#mcpsat_bienesTransp_text').val(),
      claveSTCC: $('#mcpsat_claveSTCC').val(),
      claveSTCC_text: $('#mcpsat_claveSTCC_text').val(),
      descripcion: $('#mcpsat_descripcion').val(),
      cantidad: $('#mcpsat_cantidad').val(),
      claveUnidad: $('#mcpsat_claveUnidad').val(),
      claveUnidad_text: $('#mcpsat_claveUnidad_text').val(),
      unidad: $('#mcpsat_unidad').val(),
      dimensiones: $('#mcpsat_dimensiones').val(),
      materialPeligroso: $('#mcpsat_materialPeligroso').val(),
      cveMaterialPeligroso: $('#mcpsat_cveMaterialPeligroso').val(),
      cveMaterialPeligroso_text: $('#mcpsat_cveMaterialPeligroso_text').val(),
      embalaje: $('#mcpsat_embalaje').val(),
      descripEmbalaje: $('#mcpsat_descripEmbalaje').val(),
      pesoEnKg: $('#mcpsat_pesoEnKg').val(),
      valorMercancia: $('#mcpsat_valorMercancia').val(),
      moneda: $('#mcpsat_moneda').val(),
      fraccionArancelaria: $('#mcpsat_fraccionArancelaria').val(),
      fraccionArancelaria_text: $('#mcpsat_fraccionArancelaria_text').val(),
      uuidComercioExt: $('#mcpsat_uuidComercioExt').val(),
    };
    objson.detalleMercancia = {
      unidadPeso: $('#mcpsat_detalleMercancia_unidadPeso').val(),
      unidadPeso_text: $('#mcpsat_detalleMercancia_unidadPeso_text').val(),
      pesoBruto: $('#mcpsat_detalleMercancia_pesoBruto').val(),
      pesoNeto: $('#mcpsat_detalleMercancia_pesoNeto').val(),
      pesoTara: $('#mcpsat_detalleMercancia_pesoTara').val(),
      numPiezas: $('#mcpsat_detalleMercancia_numPiezas').val(),
    };

    let htmlrow = `
      <tr class="cp-mercans" id="cp-mercans${cpnumrowsmercans}">
        <td>
          ${$('#mcpsat_bienesTransp_text').val()}
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][bienesTransp]" value="${$('#mcpsat_bienesTransp').val()}" class="cpMercans-bienesTransp">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][bienesTransp_text]" value="${$('#mcpsat_bienesTransp_text').val()}" class="cpMercans-bienesTransp_text">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][claveSTCC]" value="${$('#mcpsat_claveSTCC').val()}" class="cpMercans-claveSTCC">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][claveSTCC_text]" value="${$('#mcpsat_claveSTCC_text').val()}" class="cpMercans-claveSTCC_text">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][descripcion]" value="${$('#mcpsat_descripcion').val()}" class="cpMercans-descripcion">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidad]" value="${$('#mcpsat_cantidad').val()}" class="cpMercans-cantidad">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][claveUnidad]" value="${$('#mcpsat_claveUnidad').val()}" class="cpMercans-claveUnidad">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][claveUnidad_text]" value="${$('#mcpsat_claveUnidad_text').val()}" class="cpMercans-claveUnidad_text">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][unidad]" value="${$('#mcpsat_unidad').val()}" class="cpMercans-unidad">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][dimensiones]" value="${$('#mcpsat_dimensiones').val()}" class="cpMercans-dimensiones">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][materialPeligroso]" value="${$('#mcpsat_materialPeligroso').val()}" class="cpMercans-materialPeligroso">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cveMaterialPeligroso]" value="${$('#mcpsat_cveMaterialPeligroso').val()}" class="cpMercans-cveMaterialPeligroso">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cveMaterialPeligroso_text]" value="${$('#mcpsat_cveMaterialPeligroso_text').val()}" class="cpMercans-cveMaterialPeligroso_text">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][embalaje]" value="${$('#mcpsat_embalaje').val()}" class="cpMercans-embalaje">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][descripEmbalaje]" value="${$('#mcpsat_descripEmbalaje').val()}" class="cpMercans-descripEmbalaje">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][pesoEnKg]" value="${$('#mcpsat_pesoEnKg').val()}" class="cpMercans-pesoEnKg">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][valorMercancia]" value="${$('#mcpsat_valorMercancia').val()}" class="cpMercans-valorMercancia">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][moneda]" value="${$('#mcpsat_moneda').val()}" class="cpMercans-moneda">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][fraccionArancelaria]" value="${$('#mcpsat_fraccionArancelaria').val()}" class="cpMercans-fraccionArancelaria">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][fraccionArancelaria_text]" value="${$('#mcpsat_fraccionArancelaria_text').val()}" class="cpMercans-fraccionArancelaria_text">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][uuidComercioExt]" value="${$('#mcpsat_uuidComercioExt').val()}" class="cpMercans-uuidComercioExt">

          ${pedimentos}
          ${guias}
          ${cantidadTransporta}

          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][detalleMercancia][unidadPeso]" value="${$('#mcpsat_detalleMercancia_unidadPeso').val()}" class="cpMercans-detaMerca-unidadPeso">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][detalleMercancia][unidadPeso_text]" value="${$('#mcpsat_detalleMercancia_unidadPeso_text').val()}" class="cpMercans-detaMerca-unidadPeso_text">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][detalleMercancia][pesoBruto]" value="${$('#mcpsat_detalleMercancia_pesoBruto').val()}" class="cpMercans-detaMerca-pesoBruto">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][detalleMercancia][pesoNeto]" value="${$('#mcpsat_detalleMercancia_pesoNeto').val()}" class="cpMercans-detaMerca-pesoNeto">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][detalleMercancia][pesoTara]" value="${$('#mcpsat_detalleMercancia_pesoTara').val()}" class="cpMercans-detaMerca-pesoTara">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][detalleMercancia][numPiezas]" value="${$('#mcpsat_detalleMercancia_numPiezas').val()}" class="cpMercans-detaMerca-numPiezas">
        </td>
        <td>${$('#mcpsat_descripcion').val()}</td>
        <td>${$('#mcpsat_cantidad').val()}</td>
        <td>${$('#mcpsat_claveUnidad_text').val()}</td>
        <td>${$('#mcpsat_pesoEnKg').val()}</td>
        <td style="width: 20px;">
          <button type="button" class="btn btn-cp-editMercancia" data-json="${encodeURIComponent(JSON.stringify(objson))}">Editar</button>
          <button type="button" class="btn btn-danger btn-cp-removeMercancia">Quitar</button>
        </td>
      </tr>`;
    $("#table-mercanciass tbody").append(htmlrow);
    if($('#btn-add-CpProductoModal').data('edit')){ // elimina el tr
      $('#'+$('#btn-add-CpProductoModal').data('edit')).remove();
    }

    cpnumrowsmercans++;

    for (const property in objson.datos) {
      $(`#mcpsat_${property}`).val('');
    }
    for (const property in objson.detalleMercancia) {
      $(`#mcpsat_detalleMercancia_${property}`).val('');
    }
    $("#table-mcpsat_pedimentos tbody").html('');
    $("#table-mcpsat_guias tbody").html('');
    $("#table-mcpsat_cantidadTransporta tbody").html('');
    $('#btn-add-CpProductoModal').removeAttr('edit');

    $('#modal-cpsat-mercancia').modal('hide');
  });

  $("#table-mercanciass").on('click', '.btn-cp-removeMercancia', function(){
    $(this).parent().parent().remove();
  });

  // Editar
  $("#table-mercanciass").on('click', '.btn-cp-editMercancia', function(){
    let $tr = $(this).parent().parent();
    let cantidadTransporta = '', trrm = undefined, guias = '', pedimentos = '';

    let objson = JSON.parse(decodeURIComponent($(this).attr('data-json')));

    objson.pedimentos.forEach(function(el) {
      pedimentos += `<tr>
          <td><input type="number" class="mcpsat_pedimentos_pedimento" value="${el.pedimento}" placeholder="52 45 4214 4213546"></td>
          <td><i class="icon-ban-circle delete"></i></td>
        </tr>`;
    });
    objson.guias.forEach(function(el) {
      guias += `<tr>
          <td><input type="number" step="any" class="mcpsat_guia_numeroGuiaIdentificacion" value="${el.numeroGuiaIdentificacion}"></td>
          <td><input type="text" class="mcpsat_guia_descripGuiaIdentificacion" value="${el.descripGuiaIdentificacion}"></td>
          <td><input type="number" step="any" class="mcpsat_guia_pesoGuiaIdentificacion" value="${el.pesoGuiaIdentificacion}"></td>
          <td><i class="icon-ban-circle delete"></i></td>
        </tr>`;
    });
    objson.cantidadTransporta.forEach(function(el) {
      cantidadTransporta += `<tr>
          <td><input type="number" class="mcpsat_cantidadTransporta_cantidad" value="${el.cantidad}"></td>
          <td><input type="text" class="mcpsat_cantidadTransporta_idOrigen" value="${el.idOrigen}"></td>
          <td><input type="text" class="mcpsat_cantidadTransporta_idDestino" value="${el.idDestino}"></td>
          <td>
            <select class="mcpsat_cantidadTransporta_cvesTransporte">
              <option></option>
              <option value="01" ${(el.cvesTransporte == '01'? 'selected': '')}>01 - Autotransporte Federal</option>
              <option value="02" ${(el.cvesTransporte == '02'? 'selected': '')}>02 - Transporte Marítimo</option>
              <option value="03" ${(el.cvesTransporte == '03'? 'selected': '')}>03 - Transporte Aéreo</option>
              <option value="04" ${(el.cvesTransporte == '04'? 'selected': '')}>04 - Transporte Ferroviario</option>
              <option value="05" ${(el.cvesTransporte == '05'? 'selected': '')}>05 - Ducto</option>
            </select>
          </td>
          <td><i class="icon-ban-circle delete"></i></td>
        </tr>`;
    });

    for (const property in objson.datos) {
      $(`#mcpsat_${property}`).val(objson.datos[property]);
    }
    for (const property in objson.detalleMercancia) {
      $(`#mcpsat_detalleMercancia_${property}`).val(objson.detalleMercancia[property]);
    }

    $("#table-mcpsat_pedimentos tbody").html(pedimentos);
    $("#table-mcpsat_guias tbody").html(guias);
    $("#table-mcpsat_cantidadTransporta tbody").html(cantidadTransporta);

    $('#modal-cpsat-mercancia').modal('show');
    $('#btn-add-CpProductoModal').data('edit', $tr.attr('id'));
  });
}

function eventAddCpAutotransporteRemolques() {
  var cpnumrowsremolques = 0;
  $('#btn-add-CpRemolques').click(function(event) {
    let htmll = `<tr class="cp-mercans" data-row="${cpnumrowsremolques}">
      <td>
        <select name="cp[mercancias][autotransporte][remolques][${cpnumrowsremolques}][subTipoRem]" class="cpMercans-autotrans_rem_subTipoRem">
          <option value="CTR001">CTR001 - Caballete</option>
          <option value="CTR002">CTR002 - Caja</option>
          <option value="CTR003">CTR003 - Caja Abierta</option>
          <option value="CTR004">CTR004 - Caja Cerrada</option>
          <option value="CTR005">CTR005 - Caja De Recolección Con Cargador Frontal</option>
          <option value="CTR006">CTR006 - Caja Refrigerada</option>
          <option value="CTR007">CTR007 - Caja Seca</option>
          <option value="CTR008">CTR008 - Caja Transferencia</option>
          <option value="CTR009">CTR009 - Cama Baja o Cuello Ganso</option>
          <option value="CTR010">CTR010 - Chasis Portacontenedor</option>
          <option value="CTR011">CTR011 - Convencional De Chasis</option>
          <option value="CTR012">CTR012 - Equipo Especial</option>
          <option value="CTR013">CTR013 - Estacas</option>
          <option value="CTR014">CTR014 - Góndola Madrina</option>
          <option value="CTR015">CTR015 - Grúa Industrial</option>
          <option value="CTR016">CTR016 - Grúa</option>
          <option value="CTR017">CTR017 - Integral</option>
          <option value="CTR018">CTR018 - Jaula</option>
          <option value="CTR019">CTR019 - Media Redila</option>
          <option value="CTR020">CTR020 - Pallet o Celdillas</option>
          <option value="CTR021">CTR021 - Plataforma</option>
          <option value="CTR022">CTR022 - Plataforma Con Grúa</option>
          <option value="CTR023">CTR023 - Plataforma Encortinada</option>
          <option value="CTR024">CTR024 - Redilas</option>
          <option value="CTR025">CTR025 - Refrigerador</option>
          <option value="CTR026">CTR026 - Revolvedora</option>
          <option value="CTR027">CTR027 - Semicaja</option>
          <option value="CTR028">CTR028 - Tanque</option>
          <option value="CTR029">CTR029 - Tolva</option>
          <option value="CTR030">CTR030 - Tractor</option>
          <option value="CTR031">CTR031 - Volteo</option>
          <option value="CTR032">CTR032 - Volteo Desmontable</option>
        </select>
      </td>
      <td><input type="text" name="cp[mercancias][autotransporte][remolques][${cpnumrowsremolques}][placa]" value="" class="cpMercans-autotrans_rem_placa"></td>
      <td style="width: 20px;">
        <button type="button" class="btn btn-danger delete">Quitar</button>
      </td>
    </tr>`;

    $('#table-remolequess tbody').append(htmll);

    cpnumrowsremolques++;
  });

  $('#table-remolequess tbody').on('click', 'button.delete', function(){
    const $tr = $(this).parent().parent();
    $tr.remove();
  });
}

function eventAddCpPartesTransporte() {
  $('#btn-add-cp-partesTrans').click(function(event) {
    let htmll = `<tr>
      <td>
        <select class="ftcpsat_parteTransporte">
          <option></option>
          <option value="PT01">PT01</option>
          <option value="PT02">PT02</option>
          <option value="PT03">PT03</option>
          <option value="PT04">PT04</option>
          <option value="PT05">PT05</option>
          <option value="PT06">PT06</option>
          <option value="PT07">PT07</option>
          <option value="PT08">PT08</option>
          <option value="PT09">PT09</option>
          <option value="PT10">PT10</option>
          <option value="PT11">PT11</option>
          <option value="PT12">PT12</option>
        </select>
      </td>
      <td><i class="icon-ban-circle delete"></i></td>
    </tr>`;

    $('#table-ftcpsat_partesTrans tbody').append(htmll);
  });

  $('#table-ftcpsat_partesTrans tbody').on('click', 'i.delete', function(){
    const $tr = $(this).parent().parent();
    $tr.remove();
  });
}

function eventAddCpProductoModal() {
  var cpnumrowfiguratrans = 0;
  $("#btn-add-CpTiposFigura").click(function(event) {

    let cantidadTransporta = '', trrm = undefined, guias = '', partesTransporte = '';
    let objson = {
      datos: {},
      partesTransporte: [],
      domicilio: {}
    };

    $("#table-ftcpsat_partesTrans tbody tr").each(function(index, el) {
      partesTransporte += `
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][partesTransporte][${index}][parteTransporte]" value="${$('.ftcpsat_parteTransporte', el).val()}" class="cpFigTransParteTransporte">`;
      objson.partesTransporte.push({parteTransporte: $('.ftcpsat_parteTransporte', el).val()});
    });

    objson.datos = {
      tipoFigura: $('#ftcpsat_tipoFigura').val(),
      rfcFigura: $('#ftcpsat_rfcFigura').val(),
      numLicencia: $('#ftcpsat_numLicencia').val(),
      nombreFigura: $('#ftcpsat_nombreFigura').val(),
      numRegIdTribFigura: $('#ftcpsat_numRegIdTribFigura').val(),
      residenciaFiscalFigura: $('#ftcpsat_residenciaFiscalFigura').val(),
      residenciaFiscalFigura_text: $('#ftcpsat_residenciaFiscalFigura_text').val(),
    };
    objson.domicilio = {
      calle: $('#ftcpsat_domi_calle').val(),
      numeroExterior: $('#ftcpsat_domi_numeroExterior').val(),
      numeroInterior: $('#ftcpsat_domi_numeroInterior').val(),
      pais: $('#ftcpsat_domi_pais').val(),
      pais_text: $('#ftcpsat_domi_pais_text').val(),
      estado: $('#ftcpsat_domi_estado').val(),
      estado_text: $('#ftcpsat_domi_estado_text').val(),
      municipio: $('#ftcpsat_domi_municipio').val(),
      municipio_text: $('#ftcpsat_domi_municipio_text').val(),
      localidad: $('#ftcpsat_domi_localidad').val(),
      localidad_text: $('#ftcpsat_domi_localidad_text').val(),
      codigoPostal: $('#ftcpsat_domi_codigoPostal').val(),
      colonia: $('#ftcpsat_domi_colonia').val(),
      colonia_text: $('#ftcpsat_domi_colonia_text').val(),
      referencia: $('#ftcpsat_domi_referencia').val(),
    };

    let htmlrow = `
      <tr class="cp-figTrans" id="cp-figTrans${cpnumrowfiguratrans}">
        <td>
          ${objson.datos.tipoFigura}
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][tipoFigura]" value="${objson.datos.tipoFigura}" class="cpFigTransTipoFigura">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][rfcFigura]" value="${objson.datos.rfcFigura}" class="cpFigTransRfcFigura">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][numLicencia]" value="${objson.datos.numLicencia}" class="cpFigTransNumLicencia">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][nombreFigura]" value="${objson.datos.nombreFigura}" class="cpFigTransNombreFigura">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][numRegIdTribFigura]" value="${objson.datos.numRegIdTribFigura}" class="cpFigTransNumRegIdTribFigura">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][residenciaFiscalFigura]" value="${objson.datos.residenciaFiscalFigura}" class="cpFigTransResidenciaFiscalFigura">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][residenciaFiscalFigura_text]" value="${objson.datos.residenciaFiscalFigura_text}" class="cpFigTransResidenciaFiscalFigura_text">

          ${partesTransporte}

          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][calle]" value="${objson.domicilio.calle}" class="cpFigTransDomCalle">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][numeroExterior]" value="${objson.domicilio.numeroExterior}" class="cpFigTransDomNumeroExterior">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][numeroInterior]" value="${objson.domicilio.numeroInterior}" class="cpFigTransDomNumeroInterior">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][pais]" value="${objson.domicilio.pais}" class="cpFigTransDomPais">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][pais_text]" value="${objson.domicilio.pais_text}" class="cpFigTransDomPais_text">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][estado]" value="${objson.domicilio.estado}" class="cpFigTransDomEstado">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][estado_text]" value="${objson.domicilio.estado_text}" class="cpFigTransDomEstado_text">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][municipio]" value="${objson.domicilio.municipio}" class="cpFigTransDomMunicipio">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][municipio_text]" value="${objson.domicilio.municipio_text}" class="cpFigTransDomMunicipio_text">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][localidad]" value="${objson.domicilio.localidad}" class="cpFigTransDomLocalidad">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][localidad_text]" value="${objson.domicilio.localidad_text}" class="cpFigTransDomLocalidad_text">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][codigoPostal]" value="${objson.domicilio.codigoPostal}" class="cpFigTransDomCodigoPostal">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][colonia]" value="${objson.domicilio.colonia}" class="cpFigTransDomColonia">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][colonia_text]" value="${objson.domicilio.colonia_text}" class="cpFigTransDomColonia_text">
          <input type="hidden" name="cp[figuraTransporte][tiposFigura][${cpnumrowfiguratrans}][domicilio][referencia]" value="${objson.domicilio.referencia}" class="cpFigTransDomReferencia">
        </td>
        <td>${$('#mcpsat_descripcion').val()}</td>
        <td>${$('#mcpsat_cantidad').val()}</td>
        <td>${$('#mcpsat_claveUnidad_text').val()}</td>
        <td>${$('#mcpsat_pesoEnKg').val()}</td>
        <td style="width: 20px;">
          <button type="button" class="btn btn-cp-editMercancia" data-json="${encodeURIComponent(JSON.stringify(objson))}">Editar</button>
          <button type="button" class="btn btn-danger btn-cp-removeMercancia">Quitar</button>
        </td>
      </tr>`;
    $("#table-mercanciass tbody").append(htmlrow);
    if($('#btn-add-CpProductoModal').data('edit')){ // elimina el tr
      $('#'+$('#btn-add-CpProductoModal').data('edit')).remove();
    }

    cpnumrowfiguratrans++;

    for (const property in objson.datos) {
      $(`#mcpsat_${property}`).val('');
    }
    for (const property in objson.detalleMercancia) {
      $(`#mcpsat_detalleMercancia_${property}`).val('');
    }
    $("#table-mcpsat_pedimentos tbody").html('');
    $("#table-mcpsat_guias tbody").html('');
    $("#table-mcpsat_cantidadTransporta tbody").html('');
    $('#btn-add-CpProductoModal').removeAttr('edit');

    $('#modal-cpsat-mercancia').modal('hide');
  });

  $("#table-mercanciass").on('click', '.btn-cp-removeMercancia', function(){
    $(this).parent().parent().remove();
  });

  // Editar
  $("#table-mercanciass").on('click', '.btn-cp-editMercancia', function(){
    let $tr = $(this).parent().parent();
    let cantidadTransporta = '', trrm = undefined, guias = '', pedimentos = '';

    let objson = JSON.parse(decodeURIComponent($(this).attr('data-json')));

    objson.pedimentos.forEach(function(el) {
      pedimentos += `<tr>
          <td><input type="number" class="mcpsat_pedimentos_pedimento" value="${el.pedimento}" placeholder="52 45 4214 4213546"></td>
          <td><i class="icon-ban-circle delete"></i></td>
        </tr>`;
    });
    objson.guias.forEach(function(el) {
      guias += `<tr>
          <td><input type="number" step="any" class="mcpsat_guia_numeroGuiaIdentificacion" value="${el.numeroGuiaIdentificacion}"></td>
          <td><input type="text" class="mcpsat_guia_descripGuiaIdentificacion" value="${el.descripGuiaIdentificacion}"></td>
          <td><input type="number" step="any" class="mcpsat_guia_pesoGuiaIdentificacion" value="${el.pesoGuiaIdentificacion}"></td>
          <td><i class="icon-ban-circle delete"></i></td>
        </tr>`;
    });
    objson.cantidadTransporta.forEach(function(el) {
      cantidadTransporta += `<tr>
          <td><input type="number" class="mcpsat_cantidadTransporta_cantidad" value="${el.cantidad}"></td>
          <td><input type="text" class="mcpsat_cantidadTransporta_idOrigen" value="${el.idOrigen}"></td>
          <td><input type="text" class="mcpsat_cantidadTransporta_idDestino" value="${el.idDestino}"></td>
          <td>
            <select class="mcpsat_cantidadTransporta_cvesTransporte">
              <option></option>
              <option value="01" ${(el.cvesTransporte == '01'? 'selected': '')}>01 - Autotransporte Federal</option>
              <option value="02" ${(el.cvesTransporte == '02'? 'selected': '')}>02 - Transporte Marítimo</option>
              <option value="03" ${(el.cvesTransporte == '03'? 'selected': '')}>03 - Transporte Aéreo</option>
              <option value="04" ${(el.cvesTransporte == '04'? 'selected': '')}>04 - Transporte Ferroviario</option>
              <option value="05" ${(el.cvesTransporte == '05'? 'selected': '')}>05 - Ducto</option>
            </select>
          </td>
          <td><i class="icon-ban-circle delete"></i></td>
        </tr>`;
    });

    for (const property in objson.datos) {
      $(`#mcpsat_${property}`).val(objson.datos[property]);
    }
    for (const property in objson.detalleMercancia) {
      $(`#mcpsat_detalleMercancia_${property}`).val(objson.detalleMercancia[property]);
    }

    $("#table-mcpsat_pedimentos tbody").html(pedimentos);
    $("#table-mcpsat_guias tbody").html(guias);
    $("#table-mcpsat_cantidadTransporta tbody").html(cantidadTransporta);

    $('#modal-cpsat-mercancia').modal('show');
    $('#btn-add-CpProductoModal').data('edit', $tr.attr('id'));
  });
}

function autocompletesCP(){
  $('#tabCartaPorte').on('focus', 'input#cp_paisOrigenDestino_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $ivalor = $this.parent().find('#cp_paisOrigenDestino');
    $this.autocomplete({
      source: base_url + 'panel/catalogos/cpaises',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.value);
          $ivalor.val(ui.item.id);
          $this.parent().find('.cp_paisOrigenDestino').text(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $ivalor.val('');
      }
    });
  });

  $('#boxUbicaciones').on('focus', 'input#cp_ubic_residenciaFiscal_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $ivalor = $this.parent().find('#cp_ubic_residenciaFiscal');
    $this.autocomplete({
      source: base_url + 'panel/catalogos/cpaises',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.value);
          $ivalor.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $ivalor.val('');
      }
    });
  });

  // $('#boxUbicaciones').on('focus', 'input#cp_ubic_destino_residenciaFiscal_text:not(.ui-autocomplete-input)', function(event) {
  //   const $this = $(this),
  //   $ivalor = $this.parent().find('#cp_ubic_destino_residenciaFiscal');
  //   $this.autocomplete({
  //     source: base_url + 'panel/catalogos/cpaises',
  //     minLength: 1,
  //     selectFirst: true,
  //     select: function( event, ui ) {
  //       $this.css("background-color", "#A1F57A");
  //       setTimeout(function(){
  //         $this.val(ui.item.value);
  //         $ivalor.val(ui.item.id);
  //       }, 100);
  //     }
  //   }).on("keydown", function(event) {
  //     if(event.which == 8 || event.which == 46) {
  //       $this.css("background-color", "#FFD071");
  //       $ivalor.val('');
  //     }
  //   });
  // });

  $('#boxUbicaciones').on('focus', 'input#cp_ubic_dom_pais:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $this.autocomplete({
      source: base_url + 'panel/catalogos/cpaises',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

  $('#boxUbicaciones').on('focus', 'input#cp_ubic_dom_estado:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $pais_obj = $this.parent().find('#cp_ubic_dom_pais');
    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/cestados',
          dataType: "json",
          data: {
            'c_pais': $pais_obj.val(),
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

  $('#boxUbicaciones').on('focus', 'input#cp_ubic_dom_municipio:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $estado_obj = $this.parent().find('#cp_ubic_dom_estado');
    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/cmunicipios',
          dataType: "json",
          data: {
            'c_estado': $estado_obj.val(),
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

  $('#boxUbicaciones').on('focus', 'input#cp_ubic_dom_localidad:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $estado_obj = $this.parent().parent().find('#cp_ubic_dom_estado');
    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/clocalidades',
          dataType: "json",
          data: {
            'c_estado': $estado_obj.val(),
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

  $('#boxUbicaciones').on('focus', 'input#cp_ubic_dom_colonia:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $cp_obj = $this.parent().parent().find('#cp_ubic_dom_codigopostal');
    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/ccolonias',
          dataType: "json",
          data: {
            'c_cp': $cp_obj.val(),
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

  $('#boxUbicaciones').on('focus', 'input#cp_ubic_origen_numEstacion:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $nEstacionTxt = $this.parent().parent().find('#cp_ubic_origen_numEstacion_text'),
    $nomEstacionTxt = $this.parent().parent().find('#cp_ubic_origen_nombreEstacion');

    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/cnumEstacion',
          dataType: "json",
          data: {
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.item.clave_identificacion);
          $nEstacionTxt.val(ui.item.value);
          $nomEstacionTxt.val(ui.item.item.descripcion);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $nEstacionTxt.val('');
      }
    });
  });

  $('#boxUbicaciones').on('focus', 'input#cp_ubic_destino_numEstacion:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $nEstacionTxt = $this.parent().parent().find('#cp_ubic_destino_numEstacion_text'),
    $nomEstacionTxt = $this.parent().parent().find('#cp_ubic_destino_nombreEstacion');

    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/cnumEstacion',
          dataType: "json",
          data: {
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.item.clave_identificacion);
          $nEstacionTxt.val(ui.item.value);
          $nomEstacionTxt.val(ui.item.item.descripcion);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $nEstacionTxt.val('');
      }
    });
  });

  $('#modal-cpsat-mercancia').on('focus', 'input#mcpsat_bienesTransp_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $bienesTransp = $this.parent().parent().find('#mcpsat_bienesTransp');

    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos33/claveProdServ',
          dataType: "json",
          data: {
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.value);
          $bienesTransp.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $bienesTransp.val('');
      }
    });
  });

  $('#modal-cpsat-mercancia').on('focus', 'input#mcpsat_claveSTCC_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $claveSTCC = $this.parent().parent().find('#mcpsat_claveSTCC');

    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/cclaveStcc',
          dataType: "json",
          data: {
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.value);
          $claveSTCC.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $claveSTCC.val('');
      }
    });
  });

  $('#modal-cpsat-mercancia').on('focus', 'input#mcpsat_claveUnidad_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $claveUnidad = $this.parent().parent().find('#mcpsat_claveUnidad');

    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos33/claveUnidad',
          dataType: "json",
          data: {
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.value);
          $claveUnidad.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $claveUnidad.val('');
      }
    });
  });

  $('#modal-cpsat-mercancia').on('focus', 'input#mcpsat_cveMaterialPeligroso_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $claveMatPel = $this.parent().parent().find('#mcpsat_cveMaterialPeligroso');

    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/cclaveMatPeligro',
          dataType: "json",
          data: {
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.value);
          $claveMatPel.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $claveMatPel.val('');
      }
    });
  });

  $('#modal-cpsat-mercancia').on('focus', 'input#mcpsat_fraccionArancelaria_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $fracArance = $this.parent().parent().find('#mcpsat_fraccionArancelaria');

    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/fraccionArancelaria',
          dataType: "json",
          data: {
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.item.descripcion);
          $fracArance.val(ui.item.value);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $fracArance.val('');
      }
    });
  });

  $('#modal-cpsat-mercancia').on('focus', 'input#mcpsat_detalleMercancia_unidadPeso_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $unidadPeso = $this.parent().parent().find('#mcpsat_detalleMercancia_unidadPeso');

    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/cunidadPeso',
          dataType: "json",
          data: {
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.value);
          $unidadPeso.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $unidadPeso.val('');
      }
    });
  });

  $('#boxMercancias').on('focus', 'input#cp_mercancias_unidadPeso_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $unidadPeso = $this.parent().parent().find('#cp_mercancias_unidadPeso');

    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/cunidadPeso',
          dataType: "json",
          data: {
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.value);
          $unidadPeso.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $unidadPeso.val('');
      }
    });
  });

  $('#modal-cpsat-mercancia').on('focus', 'input#mcpsat_fraccionArancelaria_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $facccionAran = $this.parent().parent().find('#mcpsat_fraccionArancelaria');

    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/fraccionArancelaria',
          dataType: "json",
          data: {
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $this.val(ui.item.label);
          $facccionAran.val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
        $facccionAran.val('');
      }
    });
  });

  $('#modal-cpsat-FiguraTrans').on('focus', 'input#ftcpsat_residenciaFiscalFigura_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $this.autocomplete({
      source: base_url + 'panel/catalogos/cpaises',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
          $('#ftcpsat_residenciaFiscalFigura').val(ui.item.id);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

  $('#modal-cpsat-FiguraTrans').on('focus', 'input#ftcpsat_domi_pais_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $this.autocomplete({
      source: base_url + 'panel/catalogos/cpaises',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
          $('#ftcpsat_domi_pais').val(ui.item.id);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

  $('#modal-cpsat-FiguraTrans').on('focus', 'input#ftcpsat_domi_estado_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $pais_obj = $this.parent().find('#ftcpsat_domi_pais');
    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/cestados',
          dataType: "json",
          data: {
            'c_pais': $pais_obj.val(),
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $('#ftcpsat_domi_estado').val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

  $('#modal-cpsat-FiguraTrans').on('focus', 'input#ftcpsat_domi_municipio_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $estado_obj = $this.parent().find('#ftcpsat_domi_estado');
    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/cmunicipios',
          dataType: "json",
          data: {
            'c_estado': $estado_obj.val(),
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $('#ftcpsat_domi_municipio').val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

  $('#modal-cpsat-FiguraTrans').on('focus', 'input#ftcpsat_domi_localidad_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $estado_obj = $this.parent().parent().find('#ftcpsat_domi_estado');
    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/clocalidades',
          dataType: "json",
          data: {
            'c_estado': $estado_obj.val(),
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $('#ftcpsat_domi_localidad').val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

  $('#modal-cpsat-FiguraTrans').on('focus', 'input#ftcpsat_domi_colonia_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this);
    $cp_obj = $this.parent().parent().find('#ftcpsat_domi_codigoPostal');
    $this.autocomplete({
      source: function( request, response ) {
        $.ajax({
          url: base_url + 'panel/catalogos/ccolonias',
          dataType: "json",
          data: {
            'c_cp': $cp_obj.val(),
            'term': request.term,
          },
          success: function( data ) {
            response( data );
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $this.css("background-color", "#A1F57A");
        setTimeout(function(){
          $('#ftcpsat_domi_colonia').val(ui.item.id);
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $this.css("background-color", "#FFD071");
      }
    });
  });

}
