(function(mycode){

  mycode(window.jQuery, window);

})(function ($, window) {

  $(function(){

    initDate();
    autocompleteCodigos();
    autocompleteLabores();
    showCodigoArea();
    btnModalAreasSel();

    $('#box-content').keyJump();

    // Asigna evento click a los botones "Guardar"
    $('#tableClasif').on('click', '#btnAddClasif', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent();
      ajaxSave($tr);
    });

    // Asigna evento click a los botones "Eliminar"
    $('#tableClasif').on('click', '#btnDelClasif', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent();

      ajaxDelete($tr);
    });

    // Asigna evento focusout a los inputs que estan en existete, linea1, linea2
    $('#tableClasif').on('focusout', '#flts_combustible, #fhr_ini, #fhr_fin', function(event) {
      var $this = $(this),
          $tr =  $this.parent().parent();

      event.preventDefault();

      calculaTotalesHrs($tr);
    });

    // Evento para asignar los keys del 0 al 9.
    $('#tableClasif').on('keyup', '#flts_combustible', function(e) {
      var key = e.which,

          $this = $(this),
          $tr =  $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalesHrs($tr);
      }
    });

    // Evento para asignar los keys del 0 al 9.
    $('#tableClasif').on('keypress', '#fhr_fin', function(e) {
      var $this = $(this),
          $tr =  $this.parent().parent();

      calculaTotalesHrs($tr);
      // Guarda el registro
      if (e.which == 13) {
        $tr.find('#btnAddClasif').click();
      }
    });

  });

  var initDate = function () {
    $('#gfecha').on('change', function(event) {
      var $form = $('#form');
      $form.submit();
    });
  };


  // Autocomplete codigos live
  var autocompleteCodigos = function () {
    $('#tableClasif').on('focus', 'input.showCodigoAreaAuto:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/compras_areas/ajax_get_areasauto/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");
          setTimeout(function(){
            $this.val(ui.item.item.codigo_fin);
          },100)

          $tr.find('#'+$this.attr('id')+'_id').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $this = $(this), $tr = $this.parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#'+$this.attr('id')+'_id').val('');
        }
      });
    });
  };

  // Autocomplete labores live
  var autocompleteLabores = function () {
    $('#tableClasif').on('focus', 'input.showLabores:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/labores_codigo/ajax_get_labores/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('#'+$this.attr('id')+'_id').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $this = $(this), $tr = $this.parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#'+$this.attr('id')+'_id').val('');
        }
      });
    });
  };

  var ajaxSave = function ($tr) {

    var postData = {};

    postData.fecha           = $('#gfecha').val();
    postData.id_combustible  = $tr.find('#fid_combustible').val();
    postData.id_centro_costo = $tr.find('#fcentro_costo_id').val();
    postData.id_labor        = $tr.find('#flabor_id').val();
    postData.id_implemento   = $tr.find('#fimplemento_id').val();
    postData.lts_combustible = $tr.find('#flts_combustible').val();
    postData.hora_inicial    = $tr.find('#fhr_ini').val()+':00';
    postData.hora_final      = $tr.find('#fhr_fin').val()+':00';
    postData.horas_totales   = $tr.find('#ftotal_hrs').val();

    if ( validExisCombustible(postData, $tr) ) {
      $.post(base_url + 'panel/control_maquinaria/ajax_save/', postData, function(data) {

        if (data.passess) {
          $tr.find('#fid_combustible').val(data.id_combustible);

          $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);
        }else{
          noty({"text": 'Ocurrio un error al guardar!', "layout":"topRight", "type": 'error'});
          $tr.remove();
        }

        addNewTr();
      }, "json");
    } else {
      $tr.find('#fcentro_costo').focus();
      var colorini = $tr.find('td').css('background-color');
      $tr.find('td').animate({backgroundColor: 'red'}, 200, function() {
        $tr.find('td').animate({backgroundColor: colorini}, 200);
      });
      noty({"text": 'Todos los campos son requeridos!', "layout":"topRight", "type": 'error'});
    }
  };


  var ajaxDelete = function ($tr) {

    var postData = {
      'id_combustible': $tr.find('#fid_combustible').val(),
    };

    if (postData.id_combustible != '') {
      msb.confirm('Estas seguro de eliminar el registro? <br> <strong>Nota: Esta operación no se podrá revertir.</strong>', 'Rastreabilidad', $tr,
      function($tr, $obj)
      {
        // si
        $.post(base_url + 'panel/control_maquinaria/ajax_delete/', postData, function(data) {
          noty({"text": 'El registro se eliminó correctamente!', "layout":"topRight", "type": 'success'});
        });
        $tr.remove();
      },
      function()
      {
        // no
      });
    } else {
      $tr.remove();
    }

  };

  var validExisCombustible = function (datos, $trdata) {
    var isValid = true, $trdata = $trdata? $trdata: undefined;
    for (var i in datos) {
      if ($.trim(datos[i]) == '' && i != 'id_combustible') {
        isValid = false;
        break;
      }
    }
    return isValid;
  };

  var jumpIndex = 0;
  var addNewTr = function ($tr) {

    var $tabla = $('#tableClasif'),
        trHtml = '',

        indexJump = jumpIndex + 1;


    trHtml = '<tr>' +
                '<td>' +
                  '<input type="text" id="fcentro_costo" value="" class="span11 jump'+(++jumpIndex)+' pull-left showCodigoAreaAuto" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" id="fcentro_costo_id" value="" class="span12">' +
                  '<i class="ico icon-list pull-right showCodigoArea" style="cursor:pointer"></i>' +
                  '<input type="hidden" id="fid_combustible" value="" class="span12">' +
                '</td>' +
                '<td>' +
                  '<input type="text" id="flabor" value="" class="span12 jump'+jumpIndex+' showLabores" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" id="flabor_id" value="" class="span12">' +
                '</td>' +
                '<td>' +
                  '<input type="text" id="fimplemento" value="" class="span11 jump'+jumpIndex+' pull-left showCodigoAreaAuto" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" id="fimplemento_id" value="" class="span12">' +
                  '<i class="ico icon-list pull-right showCodigoArea" style="cursor:pointer"></i>' +
                '</td>' +
                '<td>' +
                  '<input type="number" id="flts_combustible" value="" class="span12 jump'+jumpIndex+' vpositive" data-next="jump'+(++jumpIndex)+'">' +
                '</td>' +
                '<td>' +
                  '<input type="time" name="fhr_ini" id="fhr_ini" value="" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                '</td>' +
                '<td>' +
                  '<input type="time" name="fhr_fin" id="fhr_fin" value="" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                '</td>' +
                '<td>' +
                  '<input type="text" id="ftotal_hrs" value="" class="span12" readonly>' +
                '</td>' +
                '<td>' +
                  '<input type="text" id="flitro_hr" value="" class="span12" readonly>' +
                '</td>' +
                '<td>' +
                  '<button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>' +
                  '<button type="button" class="btn btn-danger btn-small" id="btnDelClasif">Eliminar</button>' +
                '</td>' +
              '</tr>';

    $(trHtml).appendTo($tabla.find('tbody'));

    for (i = indexJump, max = jumpIndex; i <= max; i += 1)
    {
      $.fn.keyJump.setElem($('.jump'+i));
      $("input#flts_combustible.jump"+i).numeric({ negative: false });
    }

    $('.jump'+indexJump).focus();
  };

  var calculaTotalesHrs = function ($tr) {

    var $flts_combustible = $tr.find('#flts_combustible'),
        $fhr_ini    = $tr.find('#fhr_ini'),
        $fhr_fin    = $tr.find('#fhr_fin'),

        $ftotal_hrs = $tr.find('#ftotal_hrs'),
        $flitro_hr  = $tr.find('#flitro_hr'),

        total_hrs = calculaHoras($("#gfecha").val(), $fhr_ini.val()+':00', $fhr_fin.val()+':00').toFixed(2);

    if(total_hrs) {
      $ftotal_hrs.val(total_hrs);
      $flitro_hr.val( ((parseFloat($flts_combustible.val())||0) / (total_hrs>0?total_hrs:1)).toFixed(2) );
    }

  };

  var calculaHoras = function (fecha, v1,v2) {
    var startTime = new Date(fecha+' '+v1);
    var endTime = new Date(fecha+' '+v2),
    hrs = ((endTime.getTime() - startTime.getTime()) / 1000) / 60 / 60;

    return isNaN(hrs)? 0: hrs;
  }

  /**
   * MODAL AREAS
   * @return {[type]} [description]
   */
  var objCodigoArea;
  var showCodigoArea = function() {
    $("#tableClasif").on('click', '.showCodigoArea', function(event) {
      var $tr = $(this).parent().parent();
      objCodigoArea = $(this).parent().find('.showCodigoAreaAuto');
      $("div[id^=tblAreas]").hide();
      getAjaxAreas($(".tblAreasFirs").attr('data-id'), null);
      $("#modalAreas").modal('show');
    });


    $("#modalAreas").on('click', '.areaClick', function(event) {
      getAjaxAreas($(this).attr('data-sig'), $(this).attr('data-id'));
    });
  };

  var btnModalAreasSel = function() {
    $("#btnModalAreasSel").on('click', function(event) {
      var passes = true,
          radioSel = $("#modalAreas input[name=modalRadioSel]:checked");

      if (radioSel.length == 0){
        passes = false;
        noty({"text": 'Selecciona un codigo de los listados', "layout":"topRight", "type": 'error'});
      }


      if (passes) {
        objCodigoArea.val(radioSel.attr('data-codfin'));
        objCodigoArea.parent().find('#'+objCodigoArea.attr('id')+'_id').val(radioSel.val());
        $("#modalAreas").modal('hide');
        objCodigoArea = undefined;
      }

    });
  };

  var getAjaxAreas = function(area, padre) {
    $.getJSON(base_url+'panel/compras_areas/ajax_get_areas',
      {id_area: area, id_padre: padre},
      function(json, textStatus) {
        var html = '';
        for (var i = 0; i < json.length; i++) {
          html += '<tr class="areaClick" data-id="'+json[i].id_area+'" data-sig="'+(parseFloat(json[i].id_tipo)+1)+'">'+
                  '<td><input type="radio" name="modalRadioSel" value="'+json[i].id_area+'" data-codfin="'+json[i].codigo_fin+'" data-uniform="false"></td>'+
                  '<td>'+json[i].codigo+'</td>'+
                  '<td>'+json[i].nombre+'</td>'+
                '</tr>';
        }
        $("#tblAreasDiv"+area).show();
        $("#tblAreasDiv"+area+" tbody").html(html);

        for (var i = parseInt(area)+1; i < 15; i++) {
          $("#tblAreasDiv"+i).hide();
        }
      }
    );
  };

});