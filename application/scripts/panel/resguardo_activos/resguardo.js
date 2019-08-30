$(function(){
  autocompleteCatalogos();

  cuentas.init();
});

// Autocomplete para los catalogos.
var autocompleteCatalogos = function () {
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

  // Autocomplete Producto
  $("#dproducto").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_producto").val(ui.item.id);
      $("#dproducto").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_producto').val('');
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