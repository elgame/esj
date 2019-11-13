(function(mycode){

  mycode(window.jQuery, window);

})(function ($, window) {

  $(function(){

    initDate();
    iniTableEntradas();
    iniTableRendimientos();
    iniTableDanosExt();
    iniTableObsInter();

    onChangeArea();

    $('#box-content').keyJump();

  });

  var onChangeArea = function() {
    $("#parea").on('change', function(event) {
      window.location = base_url + 'panel/rastreabilidad_pinia/?gfecha='+$("#gfecha").val()+'&parea='+$("#parea").val();
    });
  };

  var initDate = function () {
    $('#gfecha').on('change', function(event) {
      var $form = $('#form'),
          $selectLote = $('#glote');

      $selectLote.val('');
      $form.submit();
    });
  };

  var iniTableEntradas = function () {
    $('#tableEntradas').on('keypress', '#fmelga', function(event) {
      var $this = $(this),
          $tr =  $this.parent().parent();

      if (event.which === 13)
      {
        // if ($this.val() != '')
          $tr.find('#btnAddEntradas').trigger('click');
      }
    });

    // Asigna evento click a los botones "Guardar"
    $('#tableEntradas').on('click', '#btnAddEntradas', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent(),
          id = $tr.attr('id');

      btnAddEntradas($tr);
    });

    // Asigna evento click a los botones "Eliminar"
    $('#tableEntradas').on('click', '#btnDelEntradas', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent(),
          id = $tr.attr('id');

      if (id !== undefined) btnDelEntradas($tr);
      $tr.find("#fmelga").val("");
    });

    var btnAddEntradas = function ($tr) {
      var postData = {};

      postData.parea       = $('#parea').val();
      postData.gfecha      = $('#gfecha').val();
      postData.fid_bascula = $tr.find('#fid_bascula').val();
      postData.fkilos      = $tr.find('#fkilos').val();
      postData.fpiezas     = $tr.find('#fpiezas').val();
      postData.francho     = $tr.find('#francho').val();
      postData.fmelga      = $tr.find('#fmelga').val();

      if (postData.fmelga != '') {
        $.post(base_url + 'panel/rastreabilidad_pinia/ajax_save_entradas/', postData, function(data) {

          if (data.passess) {
            noty({"text": 'Se guardo correctamente la entrada.', "layout":"topRight", "type": 'success'});
          }else{
            noty({"text": 'Ocurrio un error al guardar la entrada', "layout":"topRight", "type": 'error'});
            $tr.find('#fmelga').val("").focus();
          }
          calculaTotalesEntradas();
        }, "json");
      } else {
        noty({"text": 'Tiene que espesificar el No de melga', "layout":"topRight", "type": 'error'});
        $tr.find('#fmelga').focus();
      }
    };

    function btnDelEntradas($tr) {

      var postData = {};

      msb.confirm('Estas seguro de borrar el No de melga?', 'Rastreabilidad', $tr,
      function($tr, $obj)
      {
        // si
        postData = {
          'parea': $('#parea').val(),
          'gfecha': $('#gfecha').val(),
          'fid_bascula': $tr.find('#fid_bascula').val(),
          'fkilos': $tr.find('#fkilos').val(),
          'fpiezas': $tr.find('#fpiezas').val(),
          'francho': $tr.find('#francho').val(),
          'fmelga': $tr.find('#fmelga').val(),
        };

        $tr.find('#fmelga').val("").focus();

        $.post(base_url + 'panel/rastreabilidad_pinia/ajax_del_entradas/', postData, function(data) {
          noty({"text": 'Se borro correctamente la entrada', "layout":"topRight", "type": 'success'});
          calculaTotalesEntradas();
        });
      },
      function()
      {
        // no
      });
    };
  };

  var iniTableRendimientos = function () {
    $('#tableClasif').on('keypress', '#ftipo', function(event) {
      var $this = $(this),
          $tr =  $this.parent().parent();

      if (event.which === 13)
      {
        // if ($this.val() != '')
          $tr.find('#btnAddClasif').trigger('click');
      }
    });

    // Asigna evento click a los botones "Guardar"
    $('#tableClasif').on('click', '#btnAddClasif', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent(),
          id = $tr.attr('id');

      btnAddRendimiento($tr);
    });

    // Asigna evento click a los botones "Eliminar"
    $('#tableClasif').on('click', '#btnDelClasif', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent(),
          id = $tr.attr('id');

      if (id !== undefined) btnDelRendimiento($tr);
      else $tr.remove();
    });

    var btnAddRendimiento = function ($tr) {
      var postData = {};

      postData.parea           = $('#parea').val();
      postData.gfecha          = $('#gfecha').val();
      postData.fid_rendimiento = $tr.find('#fid_rendimiento').val();
      postData.ftamano         = $tr.find('#ftamano').val();
      postData.funidad         = $tr.find('#funidad').val();
      postData.fcolor          = $tr.find('#fcolor').val();
      postData.fkilos          = $tr.find('#fkilos').val();
      postData.ftipo           = $tr.find('#ftipo').val();

      if (postData.ftamano != '' && postData.funidad != '' && postData.fcolor != '' && postData.fkilos != '' &&
        postData.ftipo != '') {
        $.post(base_url + 'panel/rastreabilidad_pinia/ajax_save_rendimiento/', postData, function(data) {

          if (data.passess) {
            $tr.find('#fid_rendimiento').val(data.id_rendimiento);
            $tr.attr('id', data.id_rendimiento);
            noty({"text": 'Se guardo correctamente el rendimiento.', "layout":"topRight", "type": 'success'});

            calculaTotalesRendimiento();

            addNewTrRendimiento($tr);
          }else{
            noty({"text": 'Ocurrio un error al guardar el rendimiento', "layout":"topRight", "type": 'error'});
            $tr.find('#ftamano').focus();
          }
        }, "json");
      } else {
        noty({"text": 'Todos los campos son requeridos.', "layout":"topRight", "type": 'error'});
        $tr.find('#ftamano').focus();
      }
    };

    function btnDelRendimiento($tr) {

      var postData = {};

      msb.confirm('Estas seguro de borrar el rendimiento?', 'Rastreabilidad', $tr,
      function($tr, $obj)
      {
        // si
        postData = {
          'parea': $('#parea').val(),
          'gfecha': $('#gfecha').val(),
          'fid_rendimiento': $tr.find('#fid_rendimiento').val(),
          'ftamano': $tr.find('#ftamano').val(),
          'funidad': $tr.find('#funidad').val(),
          'fcolor': $tr.find('#fcolor').val(),
          'fkilos': $tr.find('#fkilos').val(),
          'ftipo': $tr.find('#ftipo').val(),
        };

        $.post(base_url + 'panel/rastreabilidad_pinia/ajax_del_rendimiento/', postData, function(data) {
          $tr.remove();
          noty({"text": 'Se borro correctamente el rendimiento', "layout":"topRight", "type": 'success'});

          calculaTotalesRendimiento();
        });
      },
      function()
      {
        // no
      });
    };

    var jumpIndex = 0;
    function addNewTrRendimiento($tr) {

      var $tabla = $('#tableClasif'),
          trHtml = '',

          indexJump = jumpIndex + 1;

      trHtml =  '<tr>'+
                  '<td>'+
                    '<input type="text" id="ftamano" value="" class="span12 vpositive jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">'+
                    '<input type="hidden" id="fid_rendimiento" value="" class="span12">'+
                  '</td>'+
                  '<td>'+
                    '<select id="funidad" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">';
                      for (var i in json_unidades) {
                        trHtml += '<option value="'+json_unidades[i].id_unidad+'">'+json_unidades[i].nombre+'</option>';
                      }
          trHtml += '</select>'+
                  '</td>'+
                  '<td>'+
                    '<input type="text" id="fcolor" value="" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
                  '</td>'+
                  '<td>'+
                    '<input type="text" id="fkilos" value="" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
                  '</td>'+
                  '<td>'+
                    '<select id="ftipo" class="span12 jump'+jumpIndex+'">'+
                        '<option value="1ra">1ra</option>'+
                        '<option value="2da">2da</option>'+
                        '<option value="3ra">3ra</option>'+
                    '</select>'+
                  '</td>'+
                  '<td>'+
                    '<button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>'+
                    '<button type="button" class="btn btn-success btn-small" id="btnDelClasif">Eliminar</button>'+
                  '</td>'+
                '</tr>';

      $(trHtml).appendTo($tabla.find('tbody'));

      for (i = indexJump, max = jumpIndex; i <= max; i += 1)
      {
        $.fn.keyJump.setElem($('.jump'+i));
        $("input#ftamano.jump"+i+", input#fkilos.jump"+i).numeric({ negative: false });
      }

      $('.jump'+indexJump).focus();
    };

  };

  var calculaTotalesEntradas = function () {

    var ftkilos_entrada = $("#ftkilos_entrada"),
    ftkilos_entrada_spn = $("#ftkilos_entrada_spn"),
    total_entradas = 0;

    $("#tableEntradas tbody tr").each(function(index, el) {
      var $tr = $(this);
      if (parseFloat($tr.find('#fmelga').val()) > 0) {
        total_entradas += parseFloat($tr.find('#fkilos').val())||0;
      }
    });

    ftkilos_entrada_spn.html(util.darFormatoNum(total_entradas, ''));
    ftkilos_entrada.val(total_entradas);

    calculaTotales();
  };

  var calculaTotalesRendimiento = function () {

    var $rendimientos = $("#ftkilos_rendimientos"),
    $rendimientos_spn = $("#ftkilos_rendimientos_spn"),
    total_rendimientos = 0, total_1ra = 0, total_2da = 0, total_3ra = 0;

    $("#tableClasif tbody tr").each(function(index, el) {
      var $tr = $(this);
      if ($tr.attr('id') !== undefined) {
        total_rendimientos += parseFloat($tr.find('#fkilos').val())||0;
        switch($tr.find('#ftipo').val()){
          case '1ra': total_1ra += parseFloat($tr.find('#fkilos').val())||0; break;
          case '2da': total_2da += parseFloat($tr.find('#fkilos').val())||0; break;
          case '3ra': total_3ra += parseFloat($tr.find('#fkilos').val())||0; break;
        }
      }
    });

    $rendimientos_spn.html(util.darFormatoNum(total_rendimientos, ''));
    $rendimientos.val(total_rendimientos);

    $("#ftotal_1ra").val(total_1ra);
    $("#ftotal_2da").val(total_2da);
    $("#ftotal_3ra").val(total_3ra);

    calculaTotales();
  };

  var calculaTotales = function () {

    var merma = parseFloat($("#ftkilos_entrada").val()) - parseFloat($("#ftkilos_rendimientos").val()),
    total = parseFloat($("#ftotal_1ra").val())+parseFloat($("#ftotal_2da").val())+parseFloat($("#ftotal_3ra").val())+merma;

    $("#ftotal_merma").val(merma.toFixed(2));
    $("#ftotal").val(total.toFixed(2));

    var postData = {
      'parea': $('#parea').val(),
      'gfecha': $('#gfecha').val(),
      'ftotal_id': $('#ftotal_id').val(),
      'ftotal_1ra': $('#ftotal_1ra').val(),
      'ftotal_2da': $('#ftotal_2da').val(),
      'ftotal_3ra': $('#ftotal_3ra').val(),
      'ftotal_merma': $('#ftotal_merma').val(),
      'ftotal': $('#ftotal').val(),
    };

    $.post(base_url + 'panel/rastreabilidad_pinia/ajax_total_rendimiento/', postData, function(data) {
      if (data.passess) {
        $('#ftotal_id').val(data.id_pinia_rendtotal);
        noty({"text": 'Se guardaron correctamente los totales.', "layout":"topRight", "type": 'success'});
      }else{
        noty({"text": 'Ocurrio un error al guardar los totales', "layout":"topRight", "type": 'error'});
      }
    }, "json");

  };

  var iniTableDanosExt = function () {
    $("#tableDanosExt input[id^='dex_valor_']").on('change', function(event) {
      var id_parts = $(this).attr('id').split('_');

      var postData = {
        'parea': $('#parea').val(),
        'gfecha': $('#gfecha').val(),
        'dex_id': $('#dex_id_'+id_parts[2]+'_'+id_parts[3]).val(),
        'dex_dano': $('#dex_dano_'+id_parts[2]+'_'+id_parts[3]).val(),
        'dex_parte': $('#dex_parte_'+id_parts[2]+'_'+id_parts[3]).val(),
        'dex_valor': $('#dex_valor_'+id_parts[2]+'_'+id_parts[3]).val(),
      };

      $.post(base_url + 'panel/rastreabilidad_pinia/ajax_save_dano/', postData, function(data) {
        if (data.passess) {
          $('#dex_id_'+id_parts[2]+'_'+id_parts[3]).val(data.id_danio_ext);
          noty({"text": 'Se guardaron correctamente el daño.', "layout":"topRight", "type": 'success'});
        }else{
          noty({"text": 'Ocurrio un error al guardar el daño', "layout":"topRight", "type": 'error'});
        }
      }, "json");
    });
  };

  var iniTableObsInter = function () {
    $('#tableObsInter').on('keypress', '#fbrix', function(event) {
      var $this = $(this),
          $tr =  $this.parent().parent();

      if (event.which === 13)
      {
        // if ($this.val() != '')
          $tr.find('#btnAddObsInter').trigger('click');
      }
    });

    // Asigna evento click a los botones "Guardar"
    $('#tableObsInter').on('click', '#btnAddObsInter', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent(),
          id = $tr.attr('id');

      btnAddObsInter($tr);
    });

    // Asigna evento click a los botones "Eliminar"
    $('#tableObsInter').on('click', '#btnDelObsInter', function(event) {
      var $this = $(this),
          $tr = $this.parent().parent(),
          id = $tr.attr('id');

      if (id !== undefined) btnDelObsInter($tr);
      else $tr.remove();
    });

    var btnAddObsInter = function ($tr) {
      var postData = {};

      postData.parea         = $('#parea').val();
      postData.gfecha        = $('#gfecha').val();
      postData.fid_obs_inter = $tr.find('#fid_obs_inter').val();
      postData.fcorchosis    = $tr.find('#fcorchosis').is(':checked')? 't': 'f';
      postData.ftraslucidez  = $tr.find('#ftraslucidez').val();
      postData.fcolor        = $tr.find('#fcolor').val();
      postData.ftamano       = $tr.find('#ftamano').val();
      postData.fbrix         = $tr.find('#fbrix').val();

      if (postData.fcorchosis != '' && postData.ftraslucidez != '' && postData.fcolor != '' && postData.ftamano != '' &&
        postData.fbrix != '') {
        if (postData.fcolor < 1 || postData.fcolor > 4) {
          noty({"text": 'El valor del color es incorrecto, se permite (1 al 4)', "layout":"topRight", "type": 'error'});
          return false;
        }
        if (postData.ftamano < 4 || postData.ftamano > 10) {
          noty({"text": 'El valor del tamaño es incorrecto, se permite (4 al 10)', "layout":"topRight", "type": 'error'});
          return false;
        }
        if (postData.fbrix < 0 || postData.fbrix > 100) {
          noty({"text": 'El valor del brix es incorrecto, se permite (0 al 100)', "layout":"topRight", "type": 'error'});
          return false;
        }

        $.post(base_url + 'panel/rastreabilidad_pinia/ajax_save_obsinter/', postData, function(data) {

          if (data.passess) {
            $tr.find('#fid_obs_inter').val(data.id_obs_inter);
            $tr.attr('id', data.id_obs_inter);
            noty({"text": 'Se guardo correctamente la prueba.', "layout":"topRight", "type": 'success'});

            addNewTrObsInter($tr);
          }else{
            noty({"text": 'Ocurrio un error al guardar la prueba', "layout":"topRight", "type": 'error'});
            $tr.find('#ftamano').focus();
          }
        }, "json");
      } else {
        noty({"text": 'Todos los campos son requeridos.', "layout":"topRight", "type": 'error'});
        $tr.find('#ftamano').focus();
      }
    };

    function btnDelObsInter($tr) {

      var postData = {};

      msb.confirm('Estas seguro de borrar la prueba?', 'Rastreabilidad', $tr,
      function($tr, $obj)
      {
        // si
        postData = {
          'parea': $('#parea').val(),
          'gfecha': $('#gfecha').val(),
          'fid_obs_inter': $tr.find('#fid_obs_inter').val(),
          'fcorchosis': $tr.find('#fcorchosis').val(),
          'ftraslucidez': $tr.find('#ftraslucidez').val(),
          'fcolor': $tr.find('#fcolor').val(),
          'ftamano': $tr.find('#ftamano').val(),
          'fbrix': $tr.find('#fbrix').val(),
        };

        $.post(base_url + 'panel/rastreabilidad_pinia/ajax_del_obsinter/', postData, function(data) {
          if (data.passess) {
            $tr.remove();
            noty({"text": 'Se borro correctamente la prueba', "layout":"topRight", "type": 'success'});
          } else
            noty({"text": 'No puede borrar esta prueba, tiene que ser de la ultima hacia atras.', "layout":"topRight", "type": 'error'});
        }, "json");
      },
      function()
      {
        // no
      });
    };

    var jumpIndex = 0;
    function addNewTrObsInter($tr) {

      var $tabla = $('#tableObsInter'),
          trHtml = '',

          indexJump = jumpIndex + 1;

      trHtml =  '<tr>'+
                    '<td>'+
                      '<input type="checkbox" id="fcorchosis" value="" class="jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">'+
                      '<input type="hidden" id="fid_obs_inter" value="" class="span12">'+
                    '</td>'+
                    '<td>'+
                      '<input type="text" id="ftraslucidez" value="" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
                    '</td>'+
                    '<td>'+
                      '<input type="text" id="fcolor" value="" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
                    '</td>'+
                    '<td>'+
                      '<input type="text" id="ftamano" value="" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
                    '</td>'+
                    '<td>'+
                      '<input type="text" id="fbrix" value="" class="span12 vpositive jump'+jumpIndex+'">'+
                    '</td>'+
                    '<td>'+
                      '<button type="button" class="btn btn-success btn-small" id="btnAddObsInter">Guardar</button>'+
                      '<button type="button" class="btn btn-success btn-small" id="btnDelObsInter">Eliminar</button>'+
                    '</td>'+
                  '</tr>';

      $(trHtml).appendTo($tabla.find('tbody'));

      for (i = indexJump, max = jumpIndex; i <= max; i += 1)
      {
        $.fn.keyJump.setElem($('.jump'+i));
        $("input#fcolor.jump"+i+", input#ftamano.jump"+i+", input#fbrix.jump"+i).numeric({ negative: false });
      }

      $('.jump'+indexJump).focus();
    };

  };

});