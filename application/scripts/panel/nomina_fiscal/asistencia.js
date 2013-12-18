(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {
    autocompleteEmpresas();
    eventOnChangeSelectDia();
    eventDblClickEmpleado();
  });

  var autocompleteEmpresas = function () {
    $("#empresa").autocomplete({
        source: base_url+'panel/facturacion/ajax_get_empresas_fac/',
        minLength: 1,
        selectFirst: true,
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

  // Evento onchage para los selects de los dias de la semana.
  var eventOnChangeSelectDia = function () {
    $('.select-tipo').on('change', function(event) {
      var $select = $(this),
          $option = $select.find('option:selected');

      color = getColor($option.val());
      $select.css({'background-color': color});
    });
  };

  // Evento double click de los empleados.
  var eventDblClickEmpleado = function () {
    $('.empleado-dbl-click').dblclick(function(event) {
      $('#supermodal').trigger('click');
    });
  };

  // Determina el tipo de color segun la opcion seleccionada en el select.
  var getColor = function (tipo) {
    switch(tipo) {
      case 'a': return 'green'; // Asistencia
      case 'f': return 'red'; // Falta
      case 'in': return 'yellow'; // Incapacidad
    }
  };

});