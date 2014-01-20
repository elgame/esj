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
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

});


function calculaTotal($tr){
  var $esistema = $tr.find('input.esistema'),
  $efisica = $tr.find('input.efisica'),
  $diferencia = $tr.find('input.diferencia')
  diferencia = parseFloat($esistema.val()) - parseFloat($efisica.val());

  $diferencia.val( (isNaN(diferencia)?'':diferencia) );
}