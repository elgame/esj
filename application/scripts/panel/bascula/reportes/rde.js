$(function(){
  // Autocomplete Proveedor
  setAutocomplet('en', 1);

  // Autocomplete Empresas
  $("#fempresa").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_empresa").val(ui.item.id);
      $("#fempresa").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#fid_empresa').val('');
    }
  });

  $("#ftipo").on("change", function(){
    $(".autocomplet_en").hide();
    $(".autocomplet_sa").hide();

    $(".autocomplet_"+$(this).val()).show();

    setAutocomplet($(this).val());
  });

  $("#linkXls").on('click', function(event) {
    var vthis = $(this), url="";
    $(".getjsval").each(function(){
      url += "&"+$(this).attr("name")+"="+$(this).val();
    });
    if($("#fefectivo:checked").length == 1)
      url += "&"+$("#fefectivo:checked").attr("name")+"="+$("#fefectivo:checked").val();
    vthis.attr("href", vthis.attr("data-href")+"?"+url.substring(1));
  });
  $("#fstatus, #fefectivo").on("change", function(){
    $("#linkXls").hide();
    if ($("#fstatus").val() == '1' && $("#fefectivo:checked").length == 1)
      $("#linkXls").show();
  });
});

function setAutocomplet(tipo, first){
  if(first != 1){
    $("#fproveedor").autocomplete("destroy").val("");
    $("#fid_proveedor").val("");
  }
  if (tipo == "en") {
    $("#fproveedor").autocomplete({
      source: base_url + 'panel/bascula/ajax_get_proveedores/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#fid_proveedor").val(ui.item.id);
        $("#fproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
       $(this).css({'background-color': '#FFD9B3'});
        $('#fid_proveedor').val('');
      }
    });
  }else if(tipo == "sa"){
    $("#fproveedor").autocomplete({
      source: base_url + 'panel/bascula/ajax_get_clientes/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#fid_proveedor").val(ui.item.id);
        $("#fproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
       $(this).css({'background-color': '#FFD9B3'});
        $('#fid_proveedor').val('');
      }
    });
  }
}