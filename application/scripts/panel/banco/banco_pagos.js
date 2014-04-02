$(function(){

  $('.ref_numerica').numeric({ decimal: false, negative: false })
  $(".tipo_cuenta").on('change', function(event) {
    event.preventDefault();
    var $this = $(this), datos = $this.val().split('-'), $tr = $this.parents("tr");
    if(datos[1] == 't') // es banamex
    {
      $tr.find('.ref_alfa').attr('maxlength', '10').numeric({ decimal: false, negative: false });
      if( !$.isNumeric($tr.find('.ref_alfa').val()) )
        $tr.find('.ref_alfa').val("");
    }else // es interbancario
    {
      $tr.find('.ref_alfa').attr('maxlength', '40').removeNumeric();
    }
  });

  $(".monto").on('change', function(event) {
    var suma = 0;
    $(".monto").each(function(index, el) {
      suma += parseFloat($(this).val());
    });
    $("#total_pagar").text(util.darFormatoNum(suma));
  });

  $("#cuenta_retiro").on('change', function(event) {
    var banamex = $("#downloadBanamex").attr('href').split('&cuentaretiro'),
    interban = $("#downloadInterban").attr('href').split('&cuentaretiro'),
    aplicarPagos = $("#aplicarPagos").attr('href').split('?cuentaretiro');
    $("#downloadBanamex").attr('href', banamex[0]+"&cuentaretiro="+$(this).val());
    $("#downloadInterban").attr('href', interban[0]+"&cuentaretiro="+$(this).val());
    $("#aplicarPagos").attr('href', aplicarPagos[0]+"?cuentaretiro="+$(this).val());
  });

  $("#dempresa").autocomplete({
      source: base_url+'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_empresa").val(ui.item.id);
        $("#dempresa").css("background-color", "#B0FFB0");
      }
  }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $("#dempresa").css("background-color", "#FFD9B3");
        $("#did_empresa").val("");
      }
  });

});