(function(mycode){

  mycode(window.jQuery, window);

})(function ($, window) {

  $(function(){

    initDate();
    autocompleteCodigos();
    autocompleteLabores();
    autocompleteEmpresas();
    showCodigoArea();
    btnModalAreasSel();
    addNewlabor();

    $('#box-content').keyJump();

    // Asigna evento click a los botones "Guardar"
    $('.tableClasif').on('click', '#btnAddClasif', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent();
      ajaxSave($tr);
    });

    // // Asigna evento click a los botones "Eliminar"
    // $('#tableClasif').on('click', '#btnDelClasif', function(event) {
    //   var $this = $(this),
    //       $tr = $this.parent().parent();

    //   ajaxDelete($tr);
    // });

    // Asigna evento focusout a los inputs que estan en existete, linea1, linea2
    $('.tableClasif').on('focusout', '#fhrs_extras, input[id^=fhoras]', function(event) {
      var $this = $(this),
          $tr =  $this.parents('tr.trempleado');

      event.preventDefault();

      calculaTotalesHrs($tr);
    });

    // Evento para asignar los keys del 0 al 9.
    $('.tableClasif').on('keyup', '#fhrs_extras, input[id^=fhoras]', function(e) {
      var key = e.which,

          $this = $(this),
          $tr =  $this.parents('tr.trempleado');

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalesHrs($tr);
      }
    });

    // Evento para asignar los keys del 0 al 9.
    $('.tableClasif').on('keypress', '#fdescripcion', function(e) {
      var $this = $(this),
          $tr =  $this.parents('tr.trempleado');

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

  var autocompleteEmpresas = function () {
    $("#empresa").autocomplete({
        source: base_url+'panel/facturacion/ajax_get_empresas_fac/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $("#empresaId").val(ui.item.id);
          $(this).css("background-color", "#B0FFB0");
        }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).css("background-color", "#FFD9B3");
          $("#empresaId").val("");
        }
    });
  };


  // Autocomplete codigos live
  var autocompleteCodigos = function () {
    $('.tableClasif').on('focus', 'input.showCodigoAreaAuto:not(.ui-autocomplete-input)', function(event) {
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
    $('.tableClasif').on('focus', 'input.showLabores:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/labores_codigo/ajax_get_labores/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          if ($tr.parent().find('#'+$this.attr('id')+'_id[value='+ui.item.id+']').length == 0) {
            $this.css("background-color", "#B0FFB0");
            $tr.find('#'+$this.attr('id')+'_id').val(ui.item.id);
          }else
            noty({"text": 'La labor ya esta seleccionada para el trabajador', "layout":"topRight", "type": 'error'});
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

    postData.fecha           = $('#ffecha').val();
    postData.id_empresa      = $('#empresaId').val();
    postData.id_area         = $tr.find('#fcentro_costo_id').val();
    postData.id_usuario      = $tr.find('#fempleado_id').val();
    postData.sueldo_diario   = $tr.find('#fsalario_diario').val();
    postData.hrs_extra       = $tr.find('#fhrs_extras').val();
    postData.descripcion     = $tr.find('#fdescripcion').val();
    postData.importe         = $tr.find('#fcosto').val();
    postData.horas           = $tr.find('#fhrs_trabajo').val();
    postData.importe_trabajo = $tr.find('#fhrs_trabajo_importe').val();
    postData.importe_extra   = $tr.find('#fhrs_extra_importe').val();
    postData.flabor_id       = [];
    postData.fhoras          = [];

    $tr.find('.hideLabor').each(function(index, el) {
      postData.flabor_id.push($(this).val());
    });
    $tr.find('.laborhoras').each(function(index, el) {
      postData.fhoras.push($(this).val());
    });

    if ( validTrabajador(postData, $tr) ) {
      $.post(base_url + 'panel/nomina_trabajos/ajax_save/', postData, function(data) {

        if (data.passess) {
          $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);
          noty({"text": 'Se guardo', "layout":"topRight", "type": 'success'});
        }else{
          $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);
          noty({"text": 'Se registro falta al Trabajador', "layout":"topRight", "type": 'success'});
          // noty({"text": 'Ocurrio un error al guardar!', "layout":"topRight", "type": 'error'});
        }
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

  var validTrabajador = function (datos, $trdata) {
    var isValid = true, $trdata = $trdata? $trdata: undefined;
    // for (var i in datos) {
    //   if (i != 'hrs_extra' && i != 'descripcion' && i != 'flabor_id' && i != 'fhoras' && $.trim(datos[i]) == '') {
    //     isValid = false;
    //     break;
    //   }
    // }
    // console.log(datos.flabor_id, datos.fhoras);
    // for (var i in datos.flabor_id) {
    //   if ($.trim(datos.flabor_id[i]) == '' || $.trim(datos.fhoras[i]) == '') {
    //     isValid = false;
    //     break;
    //   }
    // }
    return isValid;
  };

  var jumpIndex = 0;
  var addNewlabor = function () {
    // Agrega los inputs extras de labores
    $(".addNewlabor").on('click', function(event) {
      var $tr = $(this).parent().parent(),
      $trnew = $tr.clone();
      $trnew.find('.addNewlabor').removeClass('icon-plus addNewlabor').addClass('icon-remove removelabor');
      $trnew.find('input').val("").attr('style', '');
      $trnew.find('.ui-helper-hidden-accessible').remove();

      var objIdE = $trnew.find('.showLabores').removeClass('ui-autocomplete-input'),
      idE = objIdE.attr('id').replace('flabor', ''); // id empleado

      $.fn.keyJump.setElem( objIdE.attr('data-next', 'laborhoras'+idE+'_'+jumpIndex) );
      $.fn.keyJump.setElem(
        $trnew.find('.laborhoras')
              .addClass('laborhoras'+idE+'_'+jumpIndex)
              .attr('data-next', 'fhrs_extras'+idE)
      );
      $tr.after($trnew);

      objIdE.focus();

      ++jumpIndex;

      $.fn.removeNumeric();
      // $('#box-content').keyJump.off();
      // $('#box-content').keyJump({
      //   'next': 13,
      // });
      $.fn.setNumericDefault();
    });

    // Elimina los inputs extras de labores
    $(".tableClasif").on('click', '.removelabor', function(event) {
      var $trparent = $(this).parents('tr.trempleado'), $tr = $(this).parent().parent().remove();

      calculaTotalesHrs($trparent);
    });

  };

  var calculaTotalesHrs = function ($tr) {

    var $fsalario_diario = $tr.find('#fsalario_diario'),
        $fhrs_extras = $tr.find('#fhrs_extras'),
        $fhoras      = $tr.find('input[id^=fhoras]'),
        $fdia_semana = $('#fdia_semana'),
        hrs          = 0,
        salario_hr   = (parseFloat($fsalario_diario.val()) || 0) / ($fdia_semana.val() == '6'? 6: 8)
        total = 0;

    $fhoras.each(function(index, el) {
      var $parent = $(this).parent().parent();
      if ( $parent.find('.hideLabor').val() != '')
        hrs += parseFloat($(this).val()) || 0;
    });

    if (hrs > 5) {
      total += (parseFloat($fsalario_diario.val()) || 0); // dia de trabajo

      $tr.find('#fhrs_trabajo_importe').val(total.toFixed(2));
    }
    total += (parseFloat($fhrs_extras.val())||0)*salario_hr; // hrs extras

    $tr.find('#fhrs_trabajo').val(hrs);
    $tr.find('#fhrs_extra_importe').val( ((parseFloat($fhrs_extras.val())||0)*salario_hr).toFixed(2) );
    $tr.find('#fcosto').val(total.toFixed(2));

  };

  // var calculaHoras = function (fecha, v1,v2) {
  //   var startTime = new Date(fecha+' '+v1);
  //   var endTime = new Date(fecha+' '+v2),
  //   hrs = ((endTime.getTime() - startTime.getTime()) / 1000) / 60 / 60;

  //   return isNaN(hrs)? 0: hrs;
  // }

  /**
   * MODAL AREAS
   * @return {[type]} [description]
   */
  var objCodigoArea;
  var showCodigoArea = function() {
    $(".tableClasif").on('click', '.showCodigoArea', function(event) {
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