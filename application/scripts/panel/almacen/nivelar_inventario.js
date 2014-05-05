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

});

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