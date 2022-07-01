$(function(){
  $('input.efisica').on('keyup', function(e) {
    var key = e.which,
        $this = $(this),
        $tr = $this.parent().parent();

    if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
      calculaTotal($tr);
    }
  });

  // Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});
      cargaListaFamlias(ui.item.id);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

  autocompleteConcepto();

});

var autocompleteConcepto = function () {
  $("#dproducto").autocomplete({
    source: function (request, response) {
      if (isEmpresaSelected()) {
        $.ajax({
          url: base_url + 'panel/compras_ordenes/ajax_producto/',
          dataType: 'json',
          data: {
            term : request.term,
            ide: $('#did_empresa').val(),
            tipo: 'p',
            id_familia: $('#dfamilias').val(),
            id_almacen: $('#id_almacen').val(),
          },
          success: function (data) {
            response(data);
          }
        });
      } else {
        noty({"text": 'Seleccione una empresa para mostrar sus productos.', "layout":"topRight", "type": 'error'});
      }
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      var $fconcepto    = $(this);
      $fconcepto.css("background-color", "#B6E7FF");
      $("#dproductoId").val(ui.item.id);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      var $fconcepto = $(this);
      $(this).css("background-color", "#FDFC9A");
      $("#dproductoId").val('');
    }
  });
};

var isEmpresaSelected = function () {
  return $('#did_empresa').val() !== '';
};

function truncateDecimals (num, digits) {
    var numS = num.toString(),
        decPos = numS.indexOf('.'),
        substrLength = decPos == -1 ? numS.length : 1 + decPos + digits,
        trimmedResult = numS.substr(0, substrLength),
        finalResult = isNaN(trimmedResult) ? 0 : trimmedResult;

    return parseFloat(finalResult).toFixed(2);
}

function calculaTotal($tr){
  var $esistema = $tr.find('input.esistema'),
  $efisica = $tr.find('input.efisica'),
  $diferencia = $tr.find('input.diferencia'),
  diferencia = truncateDecimals(parseFloat($esistema.val() || 0) - parseFloat($efisica.val()), 4);

  $diferencia.val( (isNaN(diferencia)?'':diferencia) );
}

function cargaListaFamlias ($empresaId) {
  $.getJSON(base_url+'panel/inventario/ajax_get_familias/', {'fid_empresa': $empresaId},
    function(data){
      var html = '';
      for (var i in data.familias) {
        html += '<option value="'+data.familias[i].id_familia+'">'+data.familias[i].nombre+'</option>';
      };
      $("#dfamilias").html(html);
  });
}