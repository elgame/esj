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
    addNewCentroCosto();
    addNewlabor();
    addNewHrExtra();
    changeAsistencia();

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
    $('.tableClasif').on('focusout', 'input[id^=fhrs_extras], input[id^=fhoras]', function(event) {
      var $this = $(this),
          $tr =  $this.parents('tr.trempleado');

      event.preventDefault();

      calculaTotalesHrs($tr);
    });

    // Evento para asignar los keys del 0 al 9.
    $('.tableClasif').on('keyup', 'input[id^=fhrs_extras], input[id^=fhoras]', function(e) {
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
        source: base_url+'panel/catalogos_sft/ajax_get_codigosauto/',
        // source: base_url+'panel/compras_areas/ajax_get_areasauto/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent(),
              $trparent = $this.parents('tr.trempleado'),
              classe = $this.is('.hrsex')? '.hrsex': '';

          if ($trparent.find('.hideCCosto'+classe+'[value='+ui.item.id+']').length == 0) {
            $this.css("background-color", "#B0FFB0");
            setTimeout(function(){
              if ($.trim(ui.item.item.codigo) != '')
                $this.val(ui.item.item.codigo);
            },100)

            $tr.find('#'+$this.attr('id')+'_id').val(ui.item.id);

            calculaTotalesHrs($trparent);
          }else
            noty({"text": 'El Centro de costo ya esta seleccionada para el trabajador', "layout":"topRight", "type": 'error'});
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
              $tr = $this.parent().parent(),
              $tableCosto = $this.parents('table.tableCosto'),
              $trparent = $this.parents('tr.trempleado');

          if ($tableCosto.find('.hideLabor[value='+ui.item.id+']').length == 0) {
            $this.css("background-color", "#B0FFB0");
            $tr.find('#'+$this.attr('id')+'_id').val(ui.item.id);
            calculaTotalesHrs($trparent);
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

  var changeAsistencia = function () {
    $("#actividades_tra #tipo_asistencia").change(function(event) {
      $(this).css('background-color', $("option:selected", this).css('background-color'));
    });;
  }

  var ajaxSave = function ($tr) {

    var postData = {};

    postData.fecha           = $('#ffecha').val();
    postData.id_empresa      = $('#empresaId').val();
    postData.id_usuario      = $tr.find('#fempleado_id').val();
    postData.sueldo_diario   = $tr.find('#fsalario_diario').val();
    // postData.hrs_extra       = $tr.find('#fhrs_extras').val();
    postData.descripcion     = $tr.find('#fdescripcion').val();
    postData.importe         = $tr.find('#fcosto').val();
    postData.horas           = $tr.find('#fhrs_trabajo').val();
    postData.importe_trabajo = $tr.find('#fhrs_trabajo_importe').val();
    postData.importe_extra   = $tr.find('#fhrs_extra_importe').val();
    postData.tipo_asistencia = $tr.find('#tipo_asistencia').val();

    postData.arealhr         = [];
    postData.hrs_extra         = [];
    // postData.flabor_id       = [];
    // postData.fhoras          = [];

    $tr.find('.hideCCosto').each(function(index, el) {
      if ($(this).val() != "" && $(this).is('[id^=fcosto_hrs_ext]')) {
        var $trcc = $(this).parent(),
        item = {
          id_area: $(this).val(),
          fhoras: $trcc.find('.fhrs_extras').val(),
          fimporte: $trcc.find('.fhrs_extras_importe').val()
        };

        postData.hrs_extra.push(item);
      } else {
        var $trcc = $(this).parent().parent(),
        item = {
          id_area: $(this).val(),
          flabor_id: [],
          fhoras: []
        };

        $trcc.find('.hideLabor').each(function(index, el) {
          // if($(this).val() != "")
            item.flabor_id.push($(this).val());
        });
        $trcc.find('.laborhoras').each(function(index, el) {
          // if($(this).val() != "")
            item.fhoras.push($(this).val());
        });

        postData.arealhr.push(item);
      }
    });

    console.log(postData);
    var res_val = validTrabajador(postData, $tr);
    if ( res_val[0] ) {
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
      $tr.find('.showCodigoAreaAuto:first').focus();
      var colorini = $tr.find('td').css('background-color');
      $tr.find('td').animate({backgroundColor: 'red'}, 200, function() {
        $tr.find('td').animate({backgroundColor: colorini}, 200);
      });
      noty({"text": (res_val[1]!=''? res_val[1]:'Todos los campos son requeridos!'), "layout":"topRight", "type": 'error'});
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
    var isValid = true, $trdata = $trdata? $trdata: undefined, msg='';
    // for (var i in datos) {
    //   if (i != 'hrs_extra' && i != 'descripcion' && i != 'arealhr' && $.trim(datos[i]) == '') {
    //     isValid = false;
    //     break;
    //   }
    // }

    for (var i in datos.arealhr) {
      if ($.trim(datos.arealhr[i].id_area) == '' && datos.tipo_asistencia != 'f') {
        isValid = false;
        msg = "El Centro Costo es requerido.";
        break;
      } else {
        for (var ii in datos.arealhr[i].flabor_id) {
          if (($.trim(datos.arealhr[i].flabor_id[ii]) == '' || $.trim(datos.arealhr[i].fhoras[ii]) == '')
               && datos.tipo_asistencia != 'f') {
            isValid = false;
            msg = "Ingresa la labor y las horas.";
            break;
          }
        }
      }
    }
    return [isValid, msg];
  };

  var jumpIndex = 0;
  var addNewCentroCosto = function () {
    // Agrega los inputs extras de labores
    $(".addNewCosto").on('click', function(event) {
      var $tdc = $(this).parents("td.tdCostosLabores"),
      objIdE = $tdc.find('#fempleado_id'),
      jumpAux = jumpIndex;
      idE = objIdE.val(); // id empleado

      var html = '<table class="tablesinborders tableCosto">'+
                  '  <tbody>'+
                  '    <tr>'+
                  '      <td class="tdCodArea">'+
                  '        <input type="text" id="fcentro_costo'+idE+'_'+jumpIndex+'" value="" class="span12 pull-left showCodigoAreaAuto" data-next="flabor'+idE+'_'+(++jumpIndex)+'">'+
                  '        <input type="hidden" id="fcentro_costo'+idE+'_'+(jumpIndex-1)+'_id" value="" class="span12 hideCCosto">'+
                  '        <i class="ico icon-list pull-right showCodigoArea" style="cursor:pointer"></i>'+
                  '        <i class="ico icon-remove pull-right removeCosto" style="cursor:pointer"></i>'+
                  '      </td>'+
                  '      <td> <!-- labores y horas -->'+
                  '        <table>'+
                  '          <tbody>'+
                  '            <tr>'+
                  '              <td>'+
                  '                <input type="text" id="flabor'+idE+'_'+jumpIndex+'" data-id="'+idE+'" value="" class="span12 showLabores" data-next="fhoras'+idE+'_'+(++jumpIndex)+'">'+
                  '                <input type="hidden" id="flabor'+idE+'_'+(jumpIndex-1)+'_id" value="" class="span12 hideLabor">'+
                  '              </td>'+
                  '              <td class="tdLabHoras">'+
                  '                <input type="text" id="fhoras'+idE+'_'+jumpIndex+'" value="" class="span12 pull-left vpositive laborhoras" data-next="fhrs_extras'+idE+'">'+
                  '                <i class="ico icon-plus pull-right addNewlabor" style="cursor:pointer" title="Agregar Labor"></i>'+
                  '              </td>'+
                  '            </tr>'+
                  '          </tbody>'+
                  '        </table>'+
                  '      </td>'+
                  '    </tr>'+
                  '  </tbody>'+
                  '</table>';
      $tdc.append(html);

      $.fn.keyJump.setElem($('#fcentro_costo'+idE+'_'+jumpAux)).focus();
      ++jumpAux;
      $.fn.keyJump.setElem($('#flabor'+idE+'_'+jumpAux));
      ++jumpAux;
      $.fn.keyJump.setElem($('#fhoras'+idE+'_'+jumpAux));


      // ++jumpIndex;

      $.fn.removeNumeric();
      // $('#box-content').keyJump.off();
      // $('#box-content').keyJump({
      //   'next': 13,
      // });
      $.fn.setNumericDefault();
    });

    // Elimina los inputs extras de labores
    $("td.tdCostosLabores").on('click', '.removeCosto', function(event) {
      var $trparent = $(this).parents('tr.trempleado'), $table = $(this).parent().parent().parent().parent().remove();

      calculaTotalesHrs($trparent);
    });

  };

  var addNewlabor = function () {
    // Agrega los inputs extras de labores
    $("#actividades_tra").on('click', '.addNewlabor', function(event) {
      var $tr = $(this).parent().parent(),
      $trnew = $tr.clone();
      $trnew.find('.addNewlabor').removeClass('icon-plus addNewlabor').addClass('icon-remove removelabor');
      $trnew.find('input').val("").attr('style', '');
      $trnew.find('.ui-helper-hidden-accessible').remove();

      var objIdE = $trnew.find('.showLabores').removeClass('ui-autocomplete-input'),
      idE = objIdE.attr('data-id'); // id empleado

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

  var addNewHrExtra = function () {
    // Agrega los inputs extras de labores
    $(".addNewHrsx").on('click', function(event) {
      var $tdc = $(this).parents("td.tdCostosHrsExt"),
      objIdE = $tdc.parents("tr.trempleado").find('#fempleado_id'),
      jumpAux = jumpIndex;
      idE = objIdE.val(); // id empleado

      var html = '<div class="tdCodAreaHrs">'+
                    '<input type="text" id="fcosto_hrs_ext'+idE+'_'+jumpIndex+'" value="" class="span12 pull-left showCodigoAreaAuto hrsex" data-next="fhrs_extras'+idE+'_'+(++jumpIndex)+'" placeholder="Centro Costo">'+
                    '<input type="hidden" id="fcosto_hrs_ext'+idE+'_'+(jumpIndex-1)+'_id" value="" class="span12 hideCCosto hrsex">'+
                    '<i class="ico icon-list pull-right showCodigoArea" style="cursor:pointer"></i>'+
                    '<i class="ico icon-remove pull-right removeHrsx" style="cursor:pointer"></i>'+
                    '<input type="text" id="fhrs_extras'+idE+'_'+jumpIndex+'" value="" class="span12 fhrs_extras vpositive" placeholder="Horas extras" data-next="tipo_asistencia'+idE+'">'+
                    '<input type="hidden" id="importe_fhrs_extras'+idE+'" value="" class="span12 fhrs_extras_importe">'+
                  '</div>';
      $tdc.append(html);

      $.fn.keyJump.setElem($('#fcosto_hrs_ext'+idE+'_'+jumpAux)).focus();
      ++jumpAux;
      $.fn.keyJump.setElem($('#fhrs_extras'+idE+'_'+jumpAux));


      // ++jumpIndex;

      $.fn.removeNumeric();
      // $('#box-content').keyJump.off();
      // $('#box-content').keyJump({
      //   'next': 13,
      // });
      $.fn.setNumericDefault();
    });

    // Elimina los inputs extras de labores
    $("td.tdCostosHrsExt").on('click', '.removeHrsx', function(event) {
      var $trparent = $(this).parents('tr.trempleado'), $table = $(this).parent().remove();

      calculaTotalesHrs($trparent);
    });

  };

  var calculaTotalesHrs = function ($tr) {

    var $fsalario_diario = $tr.find('#fsalario_diario'),
        $fhrs_extras = $tr.find('input[id^=fhrs_extras]'),
        $fhoras      = $tr.find('input[id^=fhoras]'),
        $fdia_semana = $('#fdia_semana'),
        hrs          = 0,
        hrs_extra    = 0,
        salario_hr   = (parseFloat($fsalario_diario.val()) || 0) / ($fdia_semana.val() == '6'? 6: 8),
        total_hrs_extra = 0,
        total = 0;

    $fhoras.each(function(index, el) {
      var $parent = $(this).parents(".tablesinborders");
      if ( $parent.find('.hideLabor').val() != '' && $parent.find('.hideCCosto').val() != '')
        hrs += parseFloat($(this).val()) || 0;
    });

    $fhrs_extras.each(function(index, el) {
      var $parent = $(this).parents(".tdCodAreaHrs");
      if ( $parent.find('.hideCCosto').val() != '') {
        hrs_extra = ( (parseFloat($(this).val()) || 0)*salario_hr ).toFixed(2); // para registrar bono
        $parent.find('.fhrs_extras_importe').val(hrs_extra);
        total_hrs_extra += parseFloat(hrs_extra);
      }
    });

    if (hrs > 5) {
      total += (parseFloat($fsalario_diario.val()) || 0); // dia de trabajo

      $tr.find('#fhrs_trabajo_importe').val(total.toFixed(2));
    }
    total += parseFloat(total_hrs_extra); // hrs extras

    $tr.find('#fhrs_trabajo').val(hrs);
    $tr.find('#fhrs_extra_importe').val( total_hrs_extra );
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
      getAjaxAreas(1, null);
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
        var $trparent = objCodigoArea.parents('tr.trempleado');

        if ($trparent.find('.hideCCosto[value='+radioSel.val()+']').length == 0) {
          objCodigoArea.css("background-color", "#B0FFB0");
          objCodigoArea.val(radioSel.attr('data-codfin'));
          objCodigoArea.parent().find('#'+objCodigoArea.attr('id')+'_id').val(radioSel.val());
          $("#modalAreas").modal('hide');
          objCodigoArea = undefined;

          calculaTotalesHrs($trparent);
        }else
          noty({"text": 'El Centro de costo ya esta seleccionada para el trabajador', "layout":"topRight", "type": 'error'});
      }

    });
  };

  var getAjaxAreas = function(area, padre) {
    $.getJSON(base_url+'panel/catalogos_sft/ajax_get_codigos',
    // $.getJSON(base_url+'panel/compras_areas/ajax_get_areas',
      {id_area: area, id_padre: padre},
      function(json, textStatus) {
        var html = '', attrval = '';
        for (var i = 0; i < json.length; i++) {
          attrval = json[i].codigo!=''? json[i].codigo: json[i].nombre;
          html += '<tr class="areaClick" data-id="'+json[i].id_area+'" data-sig="'+(parseInt(area)+1)+'">'+
                  '<td><input type="radio" name="modalRadioSel" value="'+json[i].id_area+'" data-codfin="'+attrval+'" data-uniform="false"></td>'+
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