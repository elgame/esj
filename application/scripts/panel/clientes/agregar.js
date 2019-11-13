$(function(){
  // Autocomplete Empresas
  $("#fempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#fempresa").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

  autocompleteCatalogos();

  cuentas.init();
});

// Autocomplete para los catalogos.
var autocompleteCatalogos = function () {
  var $dpais =  $("#dpais"), $destado = $("#destado"),
      $dmunicipio = $("#dmunicipio"), $dlocalidad = $("#dlocalidad"),
      $dcp = $("#dcp"), $dcolonia = $("#dcolonia");

  $dpais.autocomplete({
    source: base_url + 'panel/catalogos/cpaises',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dpais.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dpais.val(ui.item.id);
        $("span.dpais").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dpais.css("background-color", "#FFD071");
      $("span.dpais").hide();
    }
  });

  $destado.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/cestados',
        dataType: "json",
        data: {
          'c_pais': $dpais.val(),
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
      $destado.css("background-color", "#A1F57A");
      setTimeout(function(){
        $destado.val(ui.item.id);
        $("span.destado").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $destado.css("background-color", "#FFD071");
      $("span.destado").hide();
    }
  });

  $dmunicipio.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/cmunicipios',
        dataType: "json",
        data: {
          'c_estado': $destado.val(),
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
      $dmunicipio.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dmunicipio.val(ui.item.id);
        $("span.dmunicipio").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dmunicipio.css("background-color", "#FFD071");
      $("span.dmunicipio").hide();
    }
  });

  $dlocalidad.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/clocalidades',
        dataType: "json",
        data: {
          'c_estado': $destado.val(),
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
      $dlocalidad.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dlocalidad.val(ui.item.id);
        $("span.dlocalidad").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dlocalidad.css("background-color", "#FFD071");
      $("span.dlocalidad").hide();
    }
  });

  $dcp.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/ccps',
        dataType: "json",
        data: {
          'c_estado': $destado.val(),
          'c_municipio': $dmunicipio.val(),
          'c_localidad': $dlocalidad.val(),
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
      $dcp.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dcp.val(ui.item.id);
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dcp.css("background-color", "#FFD071");
    }
  });

  $dcolonia.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/ccolonias',
        dataType: "json",
        data: {
          'c_cp': $dcp.val(),
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
      $dcolonia.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dcolonia.val(ui.item.id);
        $("span.dcolonia").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dcolonia.css("background-color", "#FFD071");
      $("span.dcolonia").hide();
    }
  });

};


var cuentas = (function($){
  var objr = {};
  var jumpIndex = 0;

  function init(){
    $('#formcliente').keyJump();

    // $("#tableCuentas").on('change', '.chk_banamex', onChangeBanamex);
    $("#tableCuentas").on('click', '.delProd', onClickDeleteCuenta);
    $("#tableCuentas").on('keypress', '.cuentas_cuenta', onKeypressAddRow);

    // // Autocomplete Empresas
    // $("#fempresa").autocomplete({
    //   source: base_url + 'panel/empresas/ajax_get_empresas/',
    //   minLength: 1,
    //   selectFirst: true,
    //   select: function( event, ui ) {
    //     $("#did_empresa").val(ui.item.id);
    //     $("#fempresa").val(ui.item.label).css({'background-color': '#99FF99'});
    //   }
    // }).keydown(function(e){
    //   if (e.which === 8) {
    //     $(this).css({'background-color': '#FFD9B3'});
    //     $('#did_empresa').val('');
    //   }
    // });
  }

  // function onChangeBanamex(e){
  //   var $this = $(this), $tr = $this.parent().parent();
  //   if ($this.is(":checked")) {
  //     $tr.find('input.cuentas_banamex').val('true');
  //     $tr.find('input.cuentas_sucursal').removeAttr('readonly');
  //     $tr.find('input.cuentas_ref').attr('maxlength', '7');
  //   }else{
  //     $tr.find('input.cuentas_banamex').val('false');
  //     $tr.find('input.cuentas_sucursal').val('').attr('readonly', 'readonly');
  //     $tr.find('input.cuentas_ref').attr('maxlength', '10');
  //   }
  // }

  function onClickDeleteCuenta(e){
    var $this = $(this), $tr = $this.parent().parent();
    $tr.remove();
  }

  function onKeypressAddRow(event){

    if (event.which === 13) {
      var $tr = $(this).parent().parent();
      event.preventDefault();

      if (valida_agregar_cuenta($tr)) {
        $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);
        addRowCuenta();
      } else {
        $tr.find('.cuentas_alias').focus();
        $tr.find('td').effect("highlight", {'color': '#da4f49'}, 500);
        noty({"text": 'Verifique los datos de la cuenta.', "layout":"topRight", "type": 'error'});
      }
    }
  }

  function addRowCuenta(){
    var $tbody = $("#tableCuentas"),
    indexJump = jumpIndex + 1,
    trhtml = '<tr>'+
            '<td>'+
            ' <input type="hidden" name="cuentas_id[]" value="" class="cuentas_id">'+
            ' <select name="fbanco[]" class="fbanco">';
                    $(".fbanco:first option").each(function(index, val) {
                      trhtml += '<option value="'+$(val).attr('value')+'">'+$(val).text()+'</option>';
                    });
        trhtml +=   '</select></td>'+
            '<td><input type="text" name="cuentas_alias[]" value="" class="cuentas_alias jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'"></td>'+
            '<td><input type="text" name="cuentas_cuenta[]" value="" class="cuentas_cuenta vpos-int jump'+jumpIndex+'"></td>'+
            '<td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>'+
        '</tr>';
    $(trhtml).appendTo($tbody);
    $(".vpos-int").removeNumeric().numeric({ decimal: false, negative: false }); //Numero entero positivo

    for (i = indexJump, max = jumpIndex; i <= max; i += 1)
      $.fn.keyJump.setElem($('.jump'+i));

    $('.jump'+(indexJump+1)).focus();

  }


  function valida_agregar_cuenta ($tr) {
    if ($tr.find(".cuentas_alias").val() === '' || $tr.find(".cuentas_cuenta").val() == '') {
      return false;
    }
    else return true;
  }

  objr.init = init;
  return objr;
})(jQuery);