(function(mycode){

  mycode(window.jQuery, window);

})(function ($, window) {

  $(function(){

    initDate();
    initLote();
    autocompleteClasif();
    autocompleteClasifLive();
    autocompleteUnidades();
    autocompleteUnidadesLive();
    autocompleteCalibre();
    autocompleteCalibreLive();
    autocompleteEtiqueta();
    autocompleteEtiquetaLive();
    autocompleteSize();
    autocompleteSizeLive();

    $('#box-content').keyJump();

    // Asigna evento click a los botones "Guardar"
    $('#tableClasif').on('click', '#btnAddClasif', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent(),
          id = $tr.attr('id');

      if (id !== undefined) ajaxEditClasifi($tr);

      else ajaxSaveClasifi($tr);
    });

    // Asigna evento click a los botones "Eliminar"
    $('#tableClasif').on('click', '#btnDelClasif', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent(),
          id = $tr.attr('id');

      if (id !== undefined) ajaxDelClasifi($tr);

      else $tr.remove();
    });

    // Asigna evento focusout a los inputs que estan en existete, linea1, linea2
    $('#tableClasif').on('focusout', '#fexistente, #flinea1, #flinea2', function(event) {
      var $this = $(this),
          $tr =  $this.parent().parent();

      event.preventDefault();

      calculaTotalesClasifi($tr);
    });

    // Evento para asignar los keys del 0 al 9.
    $('#tableClasif').on('keyup', '#fexistente, #flinea1, #flinea2', function(e) {
      var key = e.which,

          $this = $(this),
          $tr =  $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalesClasifi($tr);
      }
    });

    $('#tableClasif').on('keypress', '#flinea2', function(event) {
      var $this = $(this),
          $tr =  $this.parent().parent();

      if (event.which === 13)
      {
        if ($tr.attr('id') === undefined)
          $tr.find('#btnAddClasif').trigger('click');
        else if ($tr.attr('id') !== undefined && ($tr.find('#ftotal').val() != $tr.find('#ftotal').attr('data-valor')))
          $tr.find('#btnAddClasif').trigger('click');
        else if($tr.find("#flinea2").attr('data-valor') == 'true')
          $tr.find('#btnAddClasif').trigger('click');
        else
          $tr.next().find('#fclasificacion').focus();
      }
    });

    //Para guardar 
    $('#tableClasif').live('keypress', 'input#funidad, input#fcalibre, input#fetiqueta', function(event) {
      var $this = $(this),
          $tr =  $this.parent().parent();
      $tr.find("#flinea2").attr('data-valor', 'true');
    });

  });

  var initDate = function () {
    $('#gfecha').on('change', function(event) {
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
        // idUnidad, idCalibre, idEtiqueta
        if (validExisClasifi(ui.item.id, $tr.find('input#fidunidad').val(), $tr.find('input#fidcalibre').val(), $tr.find('input#fidetiqueta').val() )) {

          $tr.find("#fidclasificacion").val(ui.item.id); // Asigna el id al input
          $tr.find("#fclasificacion").val(ui.item.label).css({'background-color': '#99FF99'});

          cajasExistente($lote, $tr, $prevLote);
        } else {
          $tr.find("#fidclasificacion").val("");
          $tr.find("#fclasificacion").val("");

          noty({"text": 'La clasificacion que seleccionó ya existe en el listado!', "layout":"topRight", "type": 'error'});
        }
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        // $tr.find('#fidclasificacion').val('');

        $(this).parent().parent().find('#fidclasificacion').val('');
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
            // idUnidad, idCalibre, idEtiqueta
            if (validExisClasifi(ui.item.id, $tr.find('input#fidunidad').val(), $tr.find('input#fidcalibre').val(), $tr.find('input#fidetiqueta').val() )) {

              $tr.find("#fidclasificacion").val(ui.item.id); // Asigna el id al input
              $tr.find("#fclasificacion").val(ui.item.label).css({'background-color': '#99FF99'});

              cajasExistente($lote, $tr, $prevLote);
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
            // $tr.find('#fidclasificacion').val('');
            $(this).parent().parent().find('#fidclasificacion').val('');
          }
        });
    });
  };
  var cajasExistente = function($lote, $tr, $prevLote){
    var indexSelected;
    // Si el num de lote que se esta modifican es mayor a 1
    if (parseInt($lote.find('option:selected').text(), 10) > 1) {

      // Obtiene el index del option seleccionado
      indexSelected = $lote.find('option:selected').index();

      // Obtiene el jquery obj del option anterior al seleccionado
      $prevLote = $lote.find('option').eq(indexSelected - 1);

      // Llama la funcion ajax para verificar si el lote anterior
      // tiene una clasificacion como la que se esta agregando, pasando
      // el id_rendimiento y la id_clasificacion.
      // ajaxGetExistente($prevLote.val(), ui.item.id, $tr);
      ajaxGetExistente($lote.find('option:selected').val(), $tr);
    } else {
      ajaxGetExistente($lote.find('option:selected').val(), $tr);
    }
  };

  // Autocomplete Unidades
  var autocompleteUnidades = function () {
    $("input#funidad").autocomplete({
      source: base_url + 'panel/rastreabilidad/ajax_get_unidades/',
      minLength: 1,
      selectFirst: true,
      select: funcAutocompleteUnidades
    }).keydown(funcAutocompleteUnidadesKey);
  };
  // Autocomplete Unidades live
  var autocompleteUnidadesLive = function () {
    $("#tableClasif").on("focus", 'input#funidad:not(.ui-autocomplete-input)', function (event) {
        $(this).autocomplete({
          source: base_url + 'panel/rastreabilidad/ajax_get_unidades/',
          minLength: 1,
          selectFirst: true,
          select: funcAutocompleteUnidades
        }).keydown(funcAutocompleteUnidadesKey);
    });
  };
  var funcAutocompleteUnidades = function( event, ui ) {
    var $lote = $('#glote'), // Obj Select lotes
        $tr = $(this).parent().parent(), // tr padre
        $prevLote; // Almacena un obj option

    // Si la clasificacion que se esta agregando no existe
    // idUnidad, idCalibre, idEtiqueta
    if (validExisClasifi($tr.find('input#fidclasificacion').val(), ui.item.id, $tr.find('input#fidcalibre').val(), $tr.find('input#fidetiqueta').val() )) {
      $tr.find("#fidunidad").val(ui.item.id); // Asigna el id al input
      $tr.find("#funidad").val(ui.item.label).css({'background-color': '#99FF99'});

      cajasExistente($lote, $tr, $prevLote);
    } else {
      $tr.find("#fidunidad").val("");
      $tr.find("#funidad").val("");

      noty({"text": 'La clasificacion que selecciono ya existe en el listado!', "layout":"topRight", "type": 'error'});
    }
  };
  var funcAutocompleteUnidadesKey = function(e){
    var $tr = $(this).parent().parent(); // tr padre
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      // $tr.find('#fidunidad').val('');
      $(this).parent().parent().find('#fidunidad').val('');
    }
  };

  //Autocomplete Calibre
  var autocompleteCalibre = function () {
    $("input#fcalibre").autocomplete({
      source: base_url + 'panel/rastreabilidad/ajax_get_calibres/',
      minLength: 1,
      selectFirst: true,
      select: funcAutocompleteCalibre
    }).keydown(funcAutocompleteCalibreKey);
  };
  // Autocomplete Calibre live
  var autocompleteCalibreLive = function () {
    $("#tableClasif").on("focus", 'input#fcalibre:not(.ui-autocomplete-input)', function (event) {
        $(this).autocomplete({
          source: base_url + 'panel/rastreabilidad/ajax_get_calibres/',
          minLength: 1,
          selectFirst: true,
          select: funcAutocompleteCalibre
        }).keydown(funcAutocompleteCalibreKey);
    });
  };
  var funcAutocompleteCalibre = function( event, ui ) {
    var $lote = $('#glote'), // Obj Select lotes
        $tr = $(this).parent().parent(), // tr padre
        $prevLote; // Almacena un obj option

    // Si la clasificacion que se esta agregando no existe
    // idUnidad, idCalibre, idEtiqueta
    if (validExisClasifi($tr.find('input#fidclasificacion').val(), $tr.find('input#fidunidad').val(), ui.item.id, $tr.find('input#fidetiqueta').val() )) {
      $tr.find("#fidcalibre").val(ui.item.id); // Asigna el id al input
      $tr.find("#fcalibre").val(ui.item.label).css({'background-color': '#99FF99'});

      cajasExistente($lote, $tr, $prevLote);
    } else {
      $tr.find("#fidcalibre").val("");
      $tr.find("#fcalibre").val("");

      noty({"text": 'La clasificacion que selecciono ya existe en el listado!', "layout":"topRight", "type": 'error'});
    }
  };
  var funcAutocompleteCalibreKey = function(e){
    var $tr = $(this).parent().parent(); // tr padre
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      // $tr.find('#fidcalibre').val('');
      $(this).parent().parent().find('#fidcalibre').val('');
    }
  };

  //Autocomplete Size
  var autocompleteSize = function () {
    $("input#fsize").autocomplete({
      source: base_url + 'panel/rastreabilidad/ajax_get_calibres/',
      minLength: 1,
      selectFirst: true,
      select: funcAutocompleteSize
    }).keydown(funcAutocompleteSizeKey);
  };
  // Autocomplete Size live
  var autocompleteSizeLive = function () {
    $("#tableClasif").on("focus", 'input#fsize:not(.ui-autocomplete-input)', function (event) {
        $(this).autocomplete({
          source: base_url + 'panel/rastreabilidad/ajax_get_calibres/',
          minLength: 1,
          selectFirst: true,
          select: funcAutocompleteSize
        }).keydown(funcAutocompleteSizeKey);
    });
  };
  var funcAutocompleteSize = function( event, ui ) {
    var $lote = $('#glote'), // Obj Select lotes
        $tr = $(this).parent().parent(), // tr padre
        $prevLote; // Almacena un obj option

    // Si la clasificacion que se esta agregando no existe
    // idUnidad, idCalibre, idEtiqueta
    if (validExisClasifi($tr.find('input#fidclasificacion').val(), $tr.find('input#fidunidad').val(), ui.item.id, $tr.find('input#fidetiqueta').val() )) {
      $tr.find("#fidsize").val(ui.item.id); // Asigna el id al input
      $tr.find("#fsize").val(ui.item.label).css({'background-color': '#99FF99'});
    } else {
      $tr.find("#fidsize").val("");
      $tr.find("#fsize").val("");

      noty({"text": 'El Size que selecciono ya existe en el listado!', "layout":"topRight", "type": 'error'});
    }
  };
  var funcAutocompleteSizeKey = function(e){
    var $tr = $(this).parent().parent(); // tr padre
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      // $tr.find('#fidsize').val('');
      $(this).parent().parent().find('#fidsize').val('');
    }
  };

  //Autocomplete Etiqueta
  var autocompleteEtiqueta = function () {
    $("input#fetiqueta").autocomplete({
      source: base_url + 'panel/rastreabilidad/ajax_get_etiquetas/',
      minLength: 1,
      selectFirst: true,
      select: funcAutocompleteEtiqueta
    }).keydown(funcAutocompleteEtiquetaKey);
  };
  // Autocomplete Etiqueta live
  var autocompleteEtiquetaLive = function () {
    $("#tableClasif").on("focus", 'input#fetiqueta:not(.ui-autocomplete-input)', function (event) {
        $(this).autocomplete({
          source: base_url + 'panel/rastreabilidad/ajax_get_etiquetas/',
          minLength: 1,
          selectFirst: true,
          select: funcAutocompleteEtiqueta
        }).keydown(funcAutocompleteEtiquetaKey);
    });
  };
  var funcAutocompleteEtiqueta = function( event, ui ) {
    var $lote = $('#glote'), // Obj Select lotes
        $tr = $(this).parent().parent(), // tr padre
        $prevLote; // Almacena un obj option

    // Si la clasificacion que se esta agregando no existe
    // idUnidad, idCalibre, idEtiqueta
    if (validExisClasifi($tr.find('input#fidclasificacion').val(), $tr.find('input#fidunidad').val(), $tr.find('input#fidcalibre').val(), ui.item.id )) {
      $tr.find("#fidetiqueta").val(ui.item.id); // Asigna el id al input
      $tr.find("#fetiqueta").val(ui.item.label).css({'background-color': '#99FF99'});

      cajasExistente($lote, $tr, $prevLote);
    } else {
      $tr.find("#fidetiqueta").val("");
      $tr.find("#fetiqueta").val("");

      noty({"text": 'La clasificacion que selecciono ya existe en el listado!', "layout":"topRight", "type": 'error'});
    }
  };
  var funcAutocompleteEtiquetaKey = function(e){
    var $tr = $(this).parent().parent(); // tr padre
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      // $tr.find('#fidetiqueta').val('');
      $(this).parent().parent().find('#fidetiqueta').val('');
    }
  };


  var ajaxGetExistente = function (id, $tr) {
    var loteActual = $('#loteActual').val(),
    dataPost = {
      'id_rendimiento': id, 
      'id_clasificacion': $tr.find('#fidclasificacion').val(), 
      'loteActual': loteActual,
      'id_unidad': $tr.find('#fidunidad').val(),
      'id_calibre': $tr.find('#fidcalibre').val(),
      'id_etiqueta': $tr.find('#fidetiqueta').val(),
    }, enviar = true;

    $.each(dataPost, function(index, val) {
      if (val == '') enviar = false;
    });

    if (enviar) 
    {
      $.get(base_url + 'panel/rastreabilidad/ajax_get_prev_clasifi/', dataPost, function(data) {
        // Colocar el existente anterior
        $tr.find('#fexistente').val(data.existentes || 0);
        // console.log(data);
      }, "json");
    }
  };

  var ajaxSaveClasifi = function ($tr) {

    var postData = {};

    postData.id_rendimiento   = $('#glote').find('option:selected').val();
    postData.id_clasificacion = $tr.find('#fidclasificacion').val();
    postData.id_unidad        = $tr.find('#fidunidad').val();
    postData.id_calibre       = $tr.find('#fidcalibre').val();
    postData.id_size          = $tr.find('#fidsize').val();
    postData.id_etiqueta      = $tr.find('#fidetiqueta').val();
    postData.existente        = $tr.find('#fexistente').val();
    postData.kilos            = $tr.find('#fkilos').val();
    postData.linea1           = $tr.find('#flinea1').val();
    postData.linea2           = $tr.find('#flinea2').val();
    postData.total            = $tr.find('#ftotal').val();
    postData.rendimiento      = $tr.find('#frd').val();
    
    postData.fcalibre         = $tr.find('#fcalibre').val();
    postData.fsize            = $tr.find('#fsize').val();

    if (postData.id_clasificacion != '' && postData.id_unidad != '' && (postData.id_calibre != '' || postData.fcalibre != '')
       && postData.id_etiqueta != '' && (postData.id_size != '' || postData.fsize != '')) {
      $.post(base_url + 'panel/rastreabilidad/ajax_save_clasifi/', postData, function(data) {
        $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);

        $tr.attr('id', $tr.find('#fidclasificacion').val());

        $tr.find('#ftotal').attr('data-valor', postData.total);

        addNewTr();
      });
    } else {
      $tr.find('#fclasificacion').focus();
      noty({"text": 'Seleccione una clasificación, unidad, calibre, size y etiqueta', "layout":"topRight", "type": 'error'});
    }
  };

  var ajaxEditClasifi = function ($tr) {

    var postData = {};

    postData.id_rendimiento   = $('#glote').find('option:selected').val();
    postData.id_clasificacion = $tr.find('#fidclasificacion').val();
    postData.id_unidad        = $tr.find('#fidunidad').val();
    postData.id_calibre       = $tr.find('#fidcalibre').val();
    postData.id_size          = $tr.find('#fidsize').val();
    postData.id_etiqueta      = $tr.find('#fidetiqueta').val();
    postData.existente        = $tr.find('#fexistente').val();
    postData.kilos            = $tr.find('#fkilos').val();
    postData.linea1           = $tr.find('#flinea1').val();
    postData.linea2           = $tr.find('#flinea2').val();
    postData.total            = $tr.find('#ftotal').val();
    postData.rendimiento      = $tr.find('#frd').val();

    postData.fcalibre         = $tr.find('#fcalibre').val();
    postData.fsize            = $tr.find('#fsize').val();

    if (postData.id_clasificacion != '' && postData.id_unidad != '' && (postData.id_calibre != '' || postData.fcalibre != '')
       && postData.id_etiqueta != '' && (postData.id_size != '' || postData.fsize != '')) {
      $.post(base_url + 'panel/rastreabilidad/ajax_edit_clasifi/', postData, function(data) {

        noty({"text": 'La clasificacion se modifico correctamente!', "layout":"topRight", "type": 'success'});

        $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);

        $tr.find('#ftotal').attr('data-valor', postData.total);

        $tr.next().find('#fclasificacion').focus();
      });
      // console.log($tr.next().find('#fclasificacion').focus());
    } else {
      noty({"text": 'Seleccione una clasificación, unidad, calibre, size y etiqueta', "layout":"topRight", "type": 'error'});
    }
  };

  var ajaxDelClasifi = function ($tr) {

    var postData = {};

    msb.confirm('Estas seguro de eliminar la clasificación? <br> <strong>Nota: Esta operación no se podrá revertir y los datos de otros lotes pueden cambiar.</strong>', 'Rastreabilidad', $tr,
    function($tr, $obj)
    {
      // si
      postData.id_rendimiento   = $('#glote').find('option:selected').val();
      postData.id_clasificacion = $tr.find('#fidclasificacion').val();
      $.post(base_url + 'panel/rastreabilidad/ajax_del_clasifi/', postData, function(data) {
        noty({"text": 'La clasificacion se eliminó correctamente!', "layout":"topRight", "type": 'success'});
      });
      $tr.remove();
    },
    function()
    {
      // no
    });
  };

  var validExisClasifi = function (idClasifi, idUnidad, idCalibre, idEtiqueta) {
    var isValid = true;
    $('input#fidclasificacion').each(function (e, i) {
      $this = $(this), $tr = $this.parent().parent();

      if (parseInt($this.val(), 10) === parseInt(idClasifi, 10) && 
          parseInt($tr.find('input#fidunidad').val(), 10) === parseInt(idUnidad, 10) && 
          parseInt($tr.find('input#fidcalibre').val(), 10) === parseInt(idCalibre, 10) && 
          parseInt($tr.find('input#fidetiqueta').val(), 10) === parseInt(idEtiqueta, 10)
        ) {
        isValid = false;

        return false;
      }
    });
    return isValid;
  };

  var jumpIndex = 0;
  var addNewTr = function ($tr) {

    var $tabla = $('#tableClasif'),
        trHtml = '',

        indexJump = jumpIndex + 1;


    trHtml =  '<tr>' +
                '<td>' +
                  '<input type="text" id="fclasificacion" value="" class="span12 jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" id="fidclasificacion" value="" class="span12">' +
                '</td>' +
                '<td>'+
                  '<input type="text" id="funidad" value="" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" id="fidunidad" value="" class="span12">'+
                '</td>'+
                '<td>'+
                  '<input type="text" id="fcalibre" value="" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" id="fidcalibre" value="" class="span12">'+
                '</td>'+
                '<td>'+
                  '<input type="text" id="fsize" value="" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" id="fidsize" value="" class="span12">'+
                '</td>'+
                '<td>'+
                  '<input type="text" id="fetiqueta" value="" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" id="fidetiqueta" value="" class="span12">'+
                '</td>'+
                '<td>' +
                  '<input type="text" id="fkilos" value="0" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                '</td>' +
                '<td>' +
                  '<input type="text" id="fexistente" value="0" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                '</td>' +
                '<td>' +
                  '<input type="text" id="flinea1" value="0" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                '</td>' +
                '<td>' +
                  '<input type="text" id="flinea2" value="0" class="span12 vpositive jump'+jumpIndex+'">' +
                '</td>' +
                '<td>' +
                  '<span id="ftotal-span">0</span>' +
                  '<input type="hidden" id="ftotal" value="0" class="span12 vpositive">' +
                '</td>' +
                '<td>' +
                  '<span id="frd-span">0</span>' +
                  '<input type="hidden" id="frd" value="0" class="span12 vpositive">' +
                '</td>' +
                '<td>' +
                  '<button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>' +
                  '<button type="button" class="btn btn-success btn-small" id="btnDelClasif">Eliminar</button>' +
                '</td>' +
              '</tr>';

    $(trHtml).appendTo($tabla.find('tbody'));

    for (i = indexJump, max = jumpIndex; i <= max; i += 1)
    {
      $.fn.keyJump.setElem($('.jump'+i));
      $("input#fkilos.jump"+i).numeric({ negative: false });
    }

    $('.jump'+indexJump).focus();
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