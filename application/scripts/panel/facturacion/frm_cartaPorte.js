$(function(){
  autocompletesCP();

  eventAddCpUbicaciones();
  eventAddCpCantidadTransporta();
  eventAddCpPedimentos();
  eventAddCpGuiaIdentificacion();
  eventAddCpProductoModal();
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
    $("#table-mcpsat_pedimentos tbody tr").each(function(index, el) {
      pedimentos += `
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][pedimentos][${index}][pedimento]" value="${$('.mcpsat_pedimentos_pedimento', el).val()}" class="cpMercans-pedimentos-pedimento">`;
    });
    $("#table-mcpsat_guias tbody tr").each(function(index, el) {
      guias += `
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][guiasIdentificacion][${index}][numeroGuiaIdentificacion]" value="${$('.mcpsat_guia_numeroGuiaIdentificacion', el).val()}" class="cpMercans-guia-numeroGuiaIdentificacion">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][guiasIdentificacion][${index}][descripGuiaIdentificacion]" value="${$('.mcpsat_guia_descripGuiaIdentificacion', el).val()}" class="cpMercans-guia-descripGuiaIdentificacion">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][guiasIdentificacion][${index}][pesoGuiaIdentificacion]" value="${$('.mcpsat_guia_pesoGuiaIdentificacion', el).val()}" class="cpMercans-guia-pesoGuiaIdentificacion">`;
    });
    $("#table-mcpsat_cantidadTransporta tbody tr").each(function(index, el) {
      cantidadTransporta += `
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][cantidad]" value="${$('.mcpsat_cantidadTransporta_cantidad', el).val()}" class="cpMercans-cantTrans-cantidad">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][idOrigen]" value="${$('.mcpsat_cantidadTransporta_idOrigen', el).val()}" class="cpMercans-cantTrans-idOrigen">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][idDestino]" value="${$('.mcpsat_cantidadTransporta_idDestino', el).val()}" class="cpMercans-cantTrans-idDestino">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][cvesTransporte]" value="${$('.mcpsat_cantidadTransporta_cvesTransporte', el).val()}" class="cpMercans-cantTrans-cvesTransporte">`;
    });
    let htmlrow = `
      <tr class="cp-mercans" data-row="${cpnumrowsmercans}">
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
          <button type="button" class="btn btn-cp-editMercancia">Editar</button>
          <button type="button" class="btn btn-danger btn-cp-removeMercancia">Quitar</button>
        </td>
      </tr>`;
    $("#table-mercanciass tbody").append(htmlrow);

    cpnumrowsmercans++;

    $('#modal-cpsat-mercancia').modal('hide');
  });

  $("#table-mercanciass").on('click', '.btn-cp-removeMercancia', function(){
    $(this).parent().parent().remove();
  });

  $("#table-mercanciass").on('click', '.btn-cp-editMercancia', function(){
    let $tr = $(this).parent().parent();
    let cantidadTransporta = '', trrm = undefined, guias = '', pedimentos = '';

    $tr.find(".cpMercans-pedimentos-pedimento").each(function(index, el) {
      pedimentos += `<tr>
          <td><input type="number" class="mcpsat_pedimentos_pedimento" value="${$(el).val()}" placeholder="52 45 4214 4213546"></td>
          <td><i class="icon-ban-circle delete"></i></td>
        </tr>`;
    });
    $("#table-mcpsat_guias tbody tr").each(function(index, el) {
      guias += `<tr>
          <td><input type="number" step="any" class="mcpsat_guia_numeroGuiaIdentificacion" value=""></td>
          <td><input type="text" class="mcpsat_guia_descripGuiaIdentificacion" value=""></td>
          <td><input type="number" step="any" class="mcpsat_guia_pesoGuiaIdentificacion" value=""></td>
          <td><i class="icon-ban-circle delete"></i></td>
        </tr>
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][guiasIdentificacion][${index}][numeroGuiaIdentificacion]" value="${$('.mcpsat_guia_numeroGuiaIdentificacion', el).val()}" class="cpMercans-guia-numeroGuiaIdentificacion">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][guiasIdentificacion][${index}][descripGuiaIdentificacion]" value="${$('.mcpsat_guia_descripGuiaIdentificacion', el).val()}" class="cpMercans-guia-descripGuiaIdentificacion">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][guiasIdentificacion][${index}][pesoGuiaIdentificacion]" value="${$('.mcpsat_guia_pesoGuiaIdentificacion', el).val()}" class="cpMercans-guia-pesoGuiaIdentificacion">`;
    });
    $("#table-mcpsat_cantidadTransporta tbody tr").each(function(index, el) {
      cantidadTransporta += `
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][cantidad]" value="${$('.mcpsat_cantidadTransporta_cantidad', el).val()}" class="cpMercans-cantTrans-cantidad">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][idOrigen]" value="${$('.mcpsat_cantidadTransporta_idOrigen', el).val()}" class="cpMercans-cantTrans-idOrigen">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][idDestino]" value="${$('.mcpsat_cantidadTransporta_idDestino', el).val()}" class="cpMercans-cantTrans-idDestino">
          <input type="hidden" name="cp[mercancias][mercancias][${cpnumrowsmercans}][cantidadTransporta][${index}][cvesTransporte]" value="${$('.mcpsat_cantidadTransporta_cvesTransporte', el).val()}" class="cpMercans-cantTrans-cvesTransporte">`;
    });
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

}
