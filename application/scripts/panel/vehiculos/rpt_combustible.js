$(function(){
  $("#frmcombus").submit(function(event) {
    if($("#fid_vehiculo").val() != '')
      return true;
    else
      noty({"text":"El vehivulo es requerido", "layout":"topRight", "type":"error"});
      return false;
  });
	// Autocomplete para los Vehiculos.
    $("#fvehiculo").autocomplete({
      source: base_url + 'panel/vehiculos/ajax_get_vehiculos/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $vehiculo =  $(this);

        $vehiculo.val(ui.item.id);
        $("#fid_vehiculo").val(ui.item.id);
        $vehiculo.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#fvehiculo").css("background-color", "#FFD071");
        $("#fid_vehiculo").val('');
      }
    });

});
