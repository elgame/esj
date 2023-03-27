(function(mycode){

  mycode(window.jQuery, window);

})(function ($, window) {

  $(function(){
    $(window).scroll(function(){
      if ($(this).scrollTop() > 135) {
        $('.stickcontent').addClass('fixed');
      } else {
        $('.stickcontent').removeClass('fixed');
      }
    });

    init();
    autocompleteCultivo();
    autocompleteRanchos();
    autocompleteCentroCosto();
    autocompleteEmpresas();
    autocompleteLabores();
    autocompleteEmpleados();

  });

  var init = function () {
    $('#box-content').keyJump();
    $('#addTrabajador').click(function(event) {
      ajaxSave();
    });
    ajaxDelAct();
    ajaxGetAct();

    $("#davance").keyup(function(event) {
      calculaTotales();
    });
  };

  var autocompleteCultivo = function () {
    $("#area").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
        $.ajax({
            url: base_url + 'panel/areas/ajax_get_areas/',
            dataType: "json",
            data: params,
            success: function(data) {
                response(data);
            }
        });
      },
      minLength: 1,
      autoFocus: true,
      select: function( event, ui ) {
        var $area =  $(this);

        $area.val(ui.item.id);
        $("#areaId").val(ui.item.id);
        $area.css("background-color", "#A1F57A");

        $("#rancho").val('').css("background-color", "#FFD071");
        $('#tagsRanchoIds').html('');
        // $("#ranchoId").val('');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#area").css("background-color", "#FFD071");
        $("#areaId").val('');
        $("#rancho").val('').css("background-color", "#FFD071");
        $('#tagsRanchoIds').html('');
        // $("#ranchoId").val('');
      }
    });
  };

  var autocompleteRanchos = function () {
    $("#rancho").autocomplete({
      source: function(request, response) {
        var params = {term : request.term, nomina: 'true'};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
        if(parseInt($("#areaId").val()) > 0)
          params.area = $("#areaId").val();
        $.ajax({
            url: base_url + 'panel/ranchos/ajax_get_ranchos/',
            dataType: "json",
            data: params,
            success: function(data) {
                response(data);
            }
        });
      },
      minLength: 1,
      autoFocus: true,
      select: function( event, ui ) {
        var $rancho =  $(this);

        autocompleteRanchos.addRanchoTag(ui.item);
        setTimeout(function () {
          $rancho.val('');
        }, 200);
        // $rancho.val(ui.item.id);
        // $("#ranchoId").val(ui.item.id);
        // $rancho.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#rancho").css("background-color", "#FFD071");
        // $("#ranchoId").val('');
      }
    });

    autocompleteRanchos.addRanchoTag = function(item) {
      if ($('#tagsRanchoIds .ranchoId[value="'+item.id+'"]').length === 0) {
        $('#tagsRanchoIds').html('<li><span class="tag">'+item.value+'</span>'+
          '<input type="hidden" name="ranchoId[]" class="ranchoId valAddTr" value="'+item.id+'">'+
          '<input type="hidden" name="ranchoText[]" class="ranchoText" value="'+item.value+'">'+
          '</li>');
      } else {
        noty({"text": 'Ya esta agregada el Areas, Ranchos o Lineas.', "layout":"topRight", "type": 'error'});
      }
    };

    $('#tagsRanchoIds').on('click', 'li:not(.disable)', function(event) {
      $(this).remove();
    });
  };

  var autocompleteCentroCosto = function () {
    $("#centroCosto").autocomplete({
      source: function(request, response) {
        var params = {
          term : request.term,
          nomina: 'true',
          id_area: $('#areaId').val(),
          rancho: $('#tagsRanchoIds .ranchoId').val()
        };

        params.tipo = ['melga', 'tabla', 'seccion'];
        // if ($('#tipoOrden').find('option:selected').val() == 'd') {
        //   params.tipo = ['servicio'];
        // }

        $.ajax({
            url: base_url + 'panel/centro_costo/ajax_get_centro_costo/',
            dataType: "json",
            data: params,
            success: function(data) {
                response(data);
            }
        });
      },
      minLength: 1,
      autoFocus: true,
      select: function( event, ui ) {
        var $centroCosto =  $(this);

        autocompleteCentroCosto.addCCTag(ui.item);
        setTimeout(function () {
          $centroCosto.val('');
        }, 200);
        // $centroCosto.val(ui.item.id);
        // $("#centroCostoId").val(ui.item.id);
        // $centroCosto.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#centroCosto").css("background-color", "#FFD071");
        // $("#centroCostoId").val('');
      }
    });

    autocompleteCentroCosto.addCCTag = function(item) {
      if ($('#tagsCCIds .centroCostoId[value="'+item.id+'"]').length === 0) {
        $('#tagsCCIds').append('<li><span class="tag">'+item.value+'</span>'+
          '<input type="hidden" name="centroCostoId[]" class="centroCostoId valAddTr" value="'+item.id+'">'+
          '<input type="hidden" name="centroCostoText[]" class="centroCostoText" value="'+item.value+'">'+
          '</li>');
      } else {
        noty({"text": 'Ya esta agregada el Centro de costo.', "layout":"topRight", "type": 'error'});
      }
    };

    $('#tagsCCIds').on('click', 'li:not(.disable)', function(event) {
      $(this).remove();
    });
  };

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
        autoFocus: true,
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

  var autocompleteEmpleados = function () {
    $("#dempleado").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};

        if ($('#empresaId').val() != '') {
          params.did_empresa = $('#empresaId').val();
        }

        $.ajax({
            url: base_url + 'panel/usuarios/ajax_get_usuarios/?empleados=true',
            dataType: "json",
            data: params,
            success: function(data) {
                response(data);
            }
        });
      },
      minLength: 1,
      autoFocus: true,
      select: function( event, ui ) {
        $("#dempleadoId").val(ui.item.id);
        $(this).css("background-color", "#B0FFB0");
      }
    }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $(this).css("background-color", "#FFD9B3");
        $("#dempleadoId").val("");
      }
    });
  };

  // Autocomplete labores live
  var autocompleteLabores = function () {
    $('.stickcontent-no').on('focus', 'input#dlabor:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/labores_codigo/ajax_get_labores/',
        minLength: 1,
        autoFocus: true,
        select: function( event, ui ) {
          var $this = $(this);

          $this.css("background-color", "#B0FFB0");
          $('#dlaborId').val(ui.item.id);
          $('#dcosto').val(ui.item.item.costo);

          calculaTotales();
          // noty({"text": 'La labor ya esta seleccionada para el trabajador', "layout":"topRight", "type": 'error'});
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $this = $(this);

          $(this).css("background-color", "#FFD9B3");
          $('#dlaborId').val('');
          $('#dcosto').val('0');
          calculaTotales();
        }
      });
    });
  };

  var calculaTotales = function ($tr) {
    var importe = (parseFloat($('#dcosto').val())||0) * (parseFloat($('#davance').val())||0);
    $('#dimporte').val(importe.toFixed(2));
  };

  var ajaxSave = function () {

    var postData = {};

    if ($('#rows').val() != '') {
      postData.rows = $('#rows').val();
    }
    postData.id_empresa     = $('#dempresaId').val();
    postData.fecha          = $('#dfecha').val();
    postData.semana         = $('#dsemana').val();
    postData.anio           = $('#danio').val();
    postData.id_empleado    = $('#dempleadoId').val();
    postData.id_labor       = $('#dlaborId').val();
    postData.costo          = $('#dcosto').val();
    postData.avance         = $('#davance').val();
    postData.avance_real    = $('#davance_real').val();
    postData.importe        = $('#dimporte').val();

    postData.id_area        = $('#areaId').val();
    postData.ranchos        = [];
    postData.centros_costos = [];

    $('#tagsRanchoIds li').each(function(index, el) {
      var $li = $(this),
      item = {};

      item.id = $li.find('.ranchoId').val();
      item.nombre = $li.find('.ranchoText').val();

      postData.ranchos.push(item);
    });

    $('#tagsCCIds li').each(function(index, el) {
      var $li = $(this),
      item = {};

      item.id = $li.find('.centroCostoId').val();
      item.nombre = $li.find('.centroCostoText').val();

      postData.centros_costos.push(item);
    });

    var res_val = validTrabajador();
    if ( res_val[0] ) {
      $.post(base_url + 'panel/nomina_trabajos2/ajax_save/', postData, function(data) {

        if (data.passess) {
          var ranchos = '', centros_costos = '';

          if (data.data.ranchos.length > 0) {
            for (key in data.data.ranchos) {
              ranchos += data.data.ranchos[key].nombre;
              ranchos += (key < data.data.ranchos.length? '<br>': '');
            }
          }

          if (data.data.centros_costos.length > 0) {
            for (key in data.data.centros_costos) {
              centros_costos += data.data.centros_costos[key].nombre;
              centros_costos += (key < data.data.centros_costos.length? '<br>': '');
            }
          }

          if ($('#rows').val() != '') { // si esta editando
            $('#'+data.data.rows+data.data.id_usuario+data.data.id_empresa+data.data.fecha).remove();
          }

          $('#actividades_tra tbody').prepend(
            '<tr class="rowlb" style="cursor: pointer;" id="'+data.data.rows+data.data.id_usuario+data.data.id_empresa+data.data.fecha+'">'+
              '<td>'+data.data.trabajador+'</td>'+
              '<td>'+data.data.labor+'</td>'+
              '<td>'+data.data.cultivo+'</td>'+
              '<td>'+
                ranchos+
              '</td>'+
              '<td>'+
                centros_costos+
              '</td>'+
              '<td>'+data.data.costo+'</td>'+
              '<td>'+data.data.avance+'</td>'+
              '<td>'+data.data.importe+'</td>'+
              '<td class="no">'+
                '<a class="btn btn-danger btnDelAct" '+
                  'data-params="rows='+data.data.rows+'&id_usuario='+data.data.id_usuario+'&empresa='+data.data.empresa+'&empresaId='+data.data.id_empresa+'&ffecha='+data.data.fecha+'">'+
                  '<i class="icon-ban-circle icon-white"></i>'+
                '</a>'+
              '</td>'+
            '</tr>'
          );
          noty({"text": 'Se guardo', "layout":"topRight", "type": 'success'});

          // $('#dempleadoId').val('');
          $('#rows').val('');
          $('#dlabor').val('');
          $('#dlaborId').val('');
          $('#dcosto').val('');
          $('#davance').val('');
          $('#davance_real').val('');
          $('#dimporte').val('');
          // $('#area').val('');
          // $('#areaId').val('');
          // $('#tagsRanchoIds').html('');
          $('#tagsCCIds').html('');
          $('#area').focus();
        }
      }, "json");
    } else {
      noty({"text": (res_val[1]!=''? res_val[1]:'Todos los campos son requeridos!'), "layout":"topRight", "type": 'error'});
    }
  };

  var validTrabajador = function () {
    var isValid = true, msg='';
    var campos = $('.stickcontent-no .valAddTr'), campo = undefined;

    for (var i = campos.length - 1; i >= 0; i--) {
      campo = $(campos[i]);
      if ($.trim(campo.val()) == '') {
        msg = 'El campo '+ campo.attr('id').replace(/(^d|id)/gi, '') + ' es requerido.';
        isValid = false;
        break;
      } else if (campo.is('.not0') && (parseFloat(campo.val())||0) <= 0 ) {
        msg = 'El campo '+ campo.attr('id').replace(/(^d|id)/gi, '') + ' tiene que ser > 0.';
        isValid = false;
        break;
      }
    }

    if ($('#tagsRanchoIds li').length == 0) {
      msg = 'El Areas / Ranchos / Lineas es requerido.';
      isValid = false;
    }
    if ($('#tagsCCIds li').length == 0) {
      msg = 'El Centro de costo es requerido.';
      isValid = false;
    }

    return [isValid, msg];
  };

  var ajaxGetAct = function () {
    $('#actividades_tra').on('click', '.rowlb td:not(.no)', function(event) {
      var $this = $(this), $tr = $(this).parent();
      let postData = $tr.find('.btnDelAct').attr('data-params');
      $.post(base_url + 'panel/nomina_trabajos2/ajax_get/', postData, function(data) {
        console.log('ajax_get', data);
        $('#area').val(data.cultivo);
        $('#areaId').val(data.id_area);
        $('#dempleado').val(data.trabajador);
        $('#dempleadoId').val(data.id_usuario);
        $('#dlabor').val(data.labor);
        $('#dlaborId').val(data.id_labor);
        $('#dcosto').val(data.costo);
        $('#davance').val(data.avance);
        $('#davance_real').val(data.avance_real);
        $('#dimporte').val(data.importe);
        $('#rows').val(data.rows);

        if(data.ranchos && data.ranchos.length > 0){
          let rancho = {id: data.ranchos[0].id_rancho, value: data.ranchos[0].nombre};
          autocompleteRanchos.addRanchoTag(rancho);
        }

        if(data.centros_costos && data.centros_costos.length > 0){
          data.centros_costos.forEach(element => {
            let cc = {id: element.id_centro_costo, value: element.nombre};
            autocompleteCentroCosto.addCCTag(cc);
          });
        }
      }, "json");
    });
  };

  var ajaxDelAct = function () {
    $('#actividades_tra').on('click', '.btnDelAct', function(event) {
      var $this = $(this), $tr = $(this).parent().parent();
      msb.confirm('Estas seguro de eliminar la Actividad?', 'labores', this, function () {
        var postData = $this.attr('data-params');
        $.post(base_url + 'panel/nomina_trabajos2/ajax_del/', postData, function(data) {

          if (data.passess) {
            $tr.remove();
            noty({"text": 'Se elimino correctamente', "layout":"topRight", "type": 'success'});
          }
        }, "json");
        console.log('test');
      });
    });
  };

});