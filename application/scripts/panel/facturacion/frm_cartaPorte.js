$(function(){
  autocompletesCP();

  eventAddCpUbicaciones();
  eventAddCpCantidadTransporta();
});

function eventAddCpUbicaciones() {
  $('#btn-add-CpUbicaciones').click(function(event) {
    let htmll = `<div class="ubicacionn">
      <div class="row-fluid">
        <div class="span4">
          <div class="control-group">
            <label class="control-label" for="cp_ubicaciones_tipoEstacion" style="width: 115px;">Tipo Estación <i class="icon-question-sign helpover" data-title=""></i></label>
            <div class="controls" style="margin-left: 133px;">
              <select name="cp[ubicaciones][][tipoEstacion]" class="span12 sikey" id="cp_tipoEstacion" data-next="cp_totalDistRec">
                <option value=""></option>
                <option value="01">01 - Origen Nacional</option>
                <option value="02">02 - Intermedia</option>
                <option value="03">03 - Destino Final Nacional</option>
              </select>
            </div>
          </div>
        </div>

        <div class="span5">
          <div class="control-group">
            <label class="control-label" for="cp_ubicaciones_distanciaRecorrida" style="width: 80px;">Distancia Recorrida (Km) <i class="icon-question-sign helpover" data-title=""></i></label>
            <div class="controls" style="margin-left: 83px;">
              <input type="text" name="cp[ubicaciones][][distanciaRecorrida]" class="span12 sikey" id="cp_ubicaciones_distanciaRecorrida" value="" placeholder="Nombre" data-next="cce_destinatario_dom_calle">
            </div>
          </div>
        </div>
      </div>

      <table class="table table-striped table-cpOrigen">
        <thead>
          <tr>
            <th>ID Origen <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>RFC Remitente <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Nombre Remitente <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Num Reg Id Trib <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Residencia Fiscal <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Num Estacion <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Nombre Estacion <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Navegacion Trafico <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Fecha y Hora de Salida <i class="icon-question-sign helpover" data-title=""></i></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="center"><input type="text" name="cp[ubicaciones][][origen][idOrigen]" value="" id="cp_ubic_origen_idOrigen" minlength="8" maxlength="8" class="span12 sikey" data-next="cp_ubic_origen_rfcRemitente"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][origen][rfcRemitente]" value="" id="cp_ubic_origen_rfcRemitente" minlength="12" maxlength="13" class="span12 sikey" data-next="cp_ubic_origen_nombreRemitente"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][origen][nombreRemitente]" value="" id="cp_ubic_origen_nombreRemitente" minlength="1" maxlength="254" class="span12 sikey" data-next="cp_ubic_origen_numRegIdTrib"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][origen][numRegIdTrib]" value="" id="cp_ubic_origen_numRegIdTrib" minlength="6" maxlength="40" class="span12 sikey" data-next="cp_ubic_origen_residenciaFiscal"></td>
            <td class="center">
              <input type="text" name="cp[ubicaciones][][origen][residenciaFiscal_text]" value="" id="cp_ubic_origen_residenciaFiscal_text" maxlength="40" class="span12 sikey" data-next="cp_ubic_origen_numEstacion">
              <input type="hidden" name="cp[ubicaciones][][origen][residenciaFiscal]" value="" id="cp_ubic_origen_residenciaFiscal" maxlength="40" class="span12 sikey">
            </td>
            <td class="center">
              <input type="text" name="cp[ubicaciones][][origen][numEstacion_text]" value="" id="cp_ubic_origen_numEstacion_text" maxlength="60" class="span12 sikey" data-next="cp_ubic_origen_nombreEstacion">
              <input type="hidden" name="cp[ubicaciones][][origen][numEstacion]" value="" id="cp_ubic_origen_numEstacion" maxlength="60" class="span12 sikey">
            </td>
            <td class="center"><input type="text" name="cp[ubicaciones][][origen][nombreEstacion]" value="" id="cp_ubic_origen_nombreEstacion" minlength="1" maxlength="50" class="span12 sikey" data-next="cp_ubic_origen_navegacionTrafico"></td>
            <td class="center">
              <select name="cp[ubicaciones][][origen][navegacionTrafico]" id="cp_ubic_origen_navegacionTrafico">
                <option value=""></option>
                <option value="Altura">Altura</option>
                <option value="Cabotaje">>Cabotaje</option>
              </select>
            </td>
            <td class="center"><input type="datetime-local" name="cp[ubicaciones][][origen][fechaHoraSalida]" value="" id="cp_ubic_origen_fechaHoraSalida" class="span12 sikey" minlength="1" maxlength="12" data-next="cce_destinatario_dom_colonia"></td>
          </tr>
        </tbody>
      </table>

      <table class="table table-striped table-cpDestino">
        <thead>
          <tr>
            <th>ID Destino <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>RFC Destinatario <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Nombre Destinatario <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Num Reg Id Trib <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Residencia Fiscal <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Num Estacion <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Nombre Estacion <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Navegacion Trafico <i class="icon-question-sign helpover" data-title=""></i></th>
            <th>Fecha y Hora de Llegada <i class="icon-question-sign helpover" data-title=""></i></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="center"><input type="text" name="cp[ubicaciones][][destino][idDestino]" value="" id="cp_ubic_destino_idDestino" minlength="8" maxlength="8" class="span12 sikey" data-next="cp_ubic_destino_rfcDestinatario"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][destino][rfcDestinatario]" value="" id="cp_ubic_destino_rfcDestinatario" minlength="12" maxlength="13" class="span12 sikey" data-next="cp_ubic_destino_nombreDestinatario"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][destino][nombreDestinatario]" value="" id="cp_ubic_destino_nombreDestinatario" minlength="1" maxlength="254" class="span12 sikey" data-next="cp_ubic_destino_numRegIdTrib"></td>
            <td class="center"><input type="text" name="cp[ubicaciones][][destino][numRegIdTrib]" value="" id="cp_ubic_destino_numRegIdTrib" minlength="6" maxlength="40" class="span12 sikey" data-next="cp_ubic_destino_residenciaFiscal_text"></td>
            <td class="center">
              <input type="text" name="cp[ubicaciones][][destino][residenciaFiscal_text]" value="" id="cp_ubic_destino_residenciaFiscal_text" maxlength="40" class="span12 sikey" data-next="cp_ubic_destino_numEstacion_text">
              <input type="hidden" name="cp[ubicaciones][][destino][residenciaFiscal]" value="" id="cp_ubic_destino_residenciaFiscal" maxlength="40" class="span12 sikey">
            </td>
            <td class="center">
              <input type="text" name="cp[ubicaciones][][destino][numEstacion_text]" value="" id="cp_ubic_destino_numEstacion_text" maxlength="60" class="span12 sikey" data-next="cp_ubic_destino_nombreEstacion">
              <input type="hidden" name="cp[ubicaciones][][destino][numEstacion]" value="" id="cp_ubic_destino_numEstacion" maxlength="60" class="span12 sikey">
            </td>
            <td class="center"><input type="text" name="cp[ubicaciones][][destino][nombreEstacion]" value="" id="cp_ubic_destino_nombreEstacion" minlength="1" maxlength="50" class="span12 sikey" data-next="cp_ubic_destino_navegacionTrafico"></td>
            <td class="center">
              <select name="cp[ubicaciones][][destino][navegacionTrafico]" id="cp_ubic_destino_navegacionTrafico" data-next="cp_ubic_destino_fechaHoraProgLlegada">
                <option value=""></option>
                <option value="Altura">Altura</option>
                <option value="Cabotaje">Cabotaje</option>
              </select>
            </td>
            <td class="center"><input type="datetime-local" name="cp[ubicaciones][][origen][fechaHoraProgLlegada]" value="" id="cp_ubic_destino_fechaHoraProgLlegada" class="span12 sikey" minlength="1" maxlength="12" data-next=""></td>
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

function autocompletesCP(){
  $('#boxUbicaciones').on('focus', 'input#cp_ubic_origen_residenciaFiscal_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $ivalor = $this.parent().find('#cp_ubic_origen_residenciaFiscal');
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

  $('#boxUbicaciones').on('focus', 'input#cp_ubic_destino_residenciaFiscal_text:not(.ui-autocomplete-input)', function(event) {
    const $this = $(this),
    $ivalor = $this.parent().find('#cp_ubic_destino_residenciaFiscal');
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

}
