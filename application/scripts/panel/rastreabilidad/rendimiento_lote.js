(function(mycode){

  mycode(window.jQuery, window);

})(function ($, window) {

  $(function(){

    initDate();
    initLote();
    autocompleteClasif();
    autocompleteClasifLive();

    $('#tableClasif').on('click', '#btnAddClasif', function(event) {
      var $this = $(this);
      ajaxSaveClasifi($this.parent().parent());
    });

    $('#tableClasif').on('focusout', '#fexistente, #flinea1, #flinea2', function(event) {
      var $this = $(this),
          $tr =  $this.parent().parent();

      event.preventDefault();

      calculaTotalesClasifi($tr);
    });

  });

  var initDate = function () {
    $('#gfecha').datepicker({
      dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
      //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
      changeMonth: true, //permite modificar los meses (true o false)
      changeYear: true, //permite modificar los años (true o false)
      //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
      numberOfMonths: 1, //muestra mas de un mes en el calendario, depende del numero
    }).on('change', function(event) {
      var $form = $('#form'),
          $selectLote = $('#glote');

      $selectLote.val('');
      $form.submit();
    });
  };

  var initLote = function () {
    $('#glote').on('change', function(event) {
      var $this = $(this),
          $form = $('#form'),
          $option = $this.find('option:selected');

      if ($option.val() !== '') {
        $form.submit();
      }
    });
  };

  // Autocomplete Clasificaciones
  var autocompleteClasif = function () {
    $("input#fclasificacion").autocomplete({
      source: base_url + 'panel/rastreabilidad/ajax_get_clasificaciones/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {

        var $lote = $('#glote'), // Obj Select lotes
            $tr = $(this).parent().parent(), // tr padre
            indexSelected, // Index del option seleccionado
            $prevLote; // Almacena un obj option

        // Si la clasificacion que se esta agregando no existe
        if (validExisClasifi(ui.item.id)) {

          $tr.find("#fidclasificacion").val(ui.item.id); // Asigna el id al input
          $tr.find("#fclasificacion").val(ui.item.label).css({'background-color': '#99FF99'});

          // Si el num de lote que se esta modifican es mayor a 1
          if (parseInt($lote.find('option:selected').text(), 10) > 1) {

            // Obtiene el index del option seleccionado
            indexSelected = $lote.find('option:selected').index();

            // Obtiene el jquery obj del option anterior al seleccionado
            $prevLote = $lote.find('option').eq(indexSelected - 1);

            // Llama la funcion ajax para verificar si el lote anterior
            // tiene una clasificacion como la que se esta agregando, pasando
            // el id_rendimiento y la id_clasificacion.
            ajaxGetExistente($prevLote.val(), ui.item.id, $tr);
          }
        } else {
          $tr.find("#fidclasificacion").val("");
          $tr.find("#fclasificacion").val("");

          noty({"text": 'La clasificacion que seleccionó ya existe en el listado!', "layout":"topRight", "type": 'error'});
        }
      }
    }).keydown(function(e){
      if (e.which === 8) {
       $(this).css({'background-color': '#FFD9B3'});
        $('#fidclasificacion').val('');
      }
    });
  };

  // Autocomplete Clasificaciones live
  var autocompleteClasifLive = function () {
    $("#tableClasif").on("focus", 'input#fclasificacion:not(.ui-autocomplete-input)', function (event) {
        $(this).autocomplete({
          source: base_url + 'panel/rastreabilidad/ajax_get_clasificaciones/',
          minLength: 1,
          selectFirst: true,
          select: function( event, ui ) {

            var $lote = $('#glote'), // Obj Select lotes
                $tr = $(this).parent().parent(), // tr padre
                indexSelected, // Index del option seleccionado
                $prevLote; // Almacena un obj option

            // Si la clasificacion que se esta agregando no existe
            if (validExisClasifi(ui.item.id)) {

              $tr.find("#fidclasificacion").val(ui.item.id); // Asigna el id al input
              $tr.find("#fclasificacion").val(ui.item.label).css({'background-color': '#99FF99'});

              // Si el num de lote que se esta modifican es mayor a 1
              if (parseInt($lote.find('option:selected').text(), 10) > 1) {

                // Obtiene el index del option seleccionado
                indexSelected = $lote.find('option:selected').index();

                // Obtiene el jquery obj del option anterior al seleccionado
                $prevLote = $lote.find('option').eq(indexSelected - 1);

                // Llama la funcion ajax para verificar si el lote anterior
                // tiene una clasificacion como la que se esta agregando, pasando
                // el id_rendimiento y la id_clasificacion.
                ajaxGetExistente($prevLote.val(), ui.item.id, $tr);
              }
            } else {
              $tr.find("#fidclasificacion").val("");
              $tr.find("#fclasificacion").val("");

              noty({"text": 'La clasificacion que selecciono ya existe en el listado!', "layout":"topRight", "type": 'error'});
            }
          }
        }).keydown(function(e){
          var $tr = $(this).parent().parent(); // tr padre
          if (e.which === 8) {
            $(this).css({'background-color': '#FFD9B3'});
            $tr.find('#fidclasificacion').val('');
          }
        });
    });
  };

  var ajaxGetExistente = function (id, idClasifi, $tr) {

    var loteActual = $('#loteActual').val();

    $.get(base_url + 'panel/rastreabilidad/ajax_get_prev_clasifi/', {'id_rendimiento': id, 'id_clasificacion': idClasifi, 'loteActual': loteActual}, function(data) {
      // Colocar el existente anterior
      // $tr.find('#fexistente').val();
      console.log(data);
    });
  };

  var ajaxSaveClasifi = function ($tr) {

    var postData = {};

    postData.id_rendimiento   = $('#glote').find('option:selected').val();
    postData.id_clasificacion = $tr.find('#fidclasificacion').val();
    postData.existente        = $tr.find('#fexistente').val();
    postData.linea1           = $tr.find('#flinea1').val();
    postData.linea2           = $tr.find('#flinea2').val();
    postData.total            = $tr.find('#ftotal').val();
    postData.rendimiento      = $tr.find('#frd').val();

    if (postData.id_clasificacion !== '') {
      $.post(base_url + 'panel/rastreabilidad/ajax_save_clasifi/', postData, function(data) {
        $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);

        addNewTr();
      });
      // addNewTr();
    } else {
      noty({"text": 'Seleccione una clasificación', "layout":"topRight", "type": 'error'});
    }
  };

  var validExisClasifi = function (idClasifi) {
    var isValid = true;
    $('input#fidclasificacion').each(function (e, i) {
      $this = $(this);

      if (parseInt($this.val(), 10) === parseInt(idClasifi, 10)) {
        isValid = false;

        return false;
      }
    });
    return isValid;
  };

  var addNewTr = function () {

    var $tabla = $('#tableClasif'),
        trHtml = '';

    trHtml =  '<tr>' +
                '<td>' +
                  '<input type="text" id="fclasificacion" value="" class="span10">' +
                  '<input type="text" id="fidclasificacion" value="" class="span5">' +
                '</td>' +
                '<td>' +
                  '<input type="text" id="fexistente" value="0" class="span5 vpositive">' +
                '</td>' +
                '<td>' +
                  '<input type="text" id="flinea1" value="0" class="span5 vpositive">' +
                '</td>' +
                '<td>' +
                  '<input type="text" id="flinea2" value="0" class="span5 vpositive">' +
                '</td>' +
                '<td>' +
                  '<span id="ftotal-span">0</span>' +
                  '<input type="text" id="ftotal" value="0" class="span5 vpositive">' +
                '</td>' +
                '<td>' +
                  '<span id="frd-span">0</span>' +
                  '<input type="text" id="frd" value="0" class="span5 vpositive">' +
                '</td>' +
                '<td>' +
                  '<button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>' +
                  '<button type="button" class="btn btn-success btn-small" id="btnDelClasif">Eliminar</button>' +
                '</td>' +
              '</tr>';

    $(trHtml).appendTo($tabla.find('tbody'));
  };

  var calculaTotalesClasifi = function ($tr) {

    var $existente = $tr.find('#fexistente'),
        $linea1    = $tr.find('#flinea1'),
        $linea2    = $tr.find('#flinea2'),

        $ftotalspan = $tr.find('#ftotal-span'),
        $ftotal     = $tr.find('#ftotal'),

        $frdspan = $tr.find('#frd-span'),
        $frd     = $tr.find('#frd'),

        existente = parseFloat($existente.val() || 0),
        linea1    = parseFloat($linea1.val() || 0),
        linea2    = parseFloat($linea2.val() || 0),

        total = existente + linea1 + linea2,
        rd = total - existente;

    $ftotalspan.html(total);
    $ftotal.val(total);

    $frdspan.html(rd);
    $frd.val(rd);

  };

});