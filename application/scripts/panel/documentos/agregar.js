(function (closure) {

  closure(window.jQuery, window);

})(function ($, window) {

  $(function(){

    autocompleteLineasT();
    loadTicket();

  });

  var autocompleteLineasT = function () {
    $("#dlinea_trans").autocomplete({
      source: base_url+'panel/lineas_transporte/ajax_get_lineas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_linea").val(ui.item.id);
        $("#dlinea_trans").css("background-color", "#B0FFB0");
        $("#dlinea_tel").val(ui.item.item.telefonos);
        $("#dlinea_ID").val(ui.item.item.id);
      }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $("#did_linea").val("");
          $("#dlinea_trans").css("background-color", "#FFD9B3");
          $("#dlinea_tel").val("");
          $("#dlinea_ID").val("");
        }
    });
  };

  var loadTicket = function () {
    $('#loadTicket').on('click', function(event) {
      var $ticket = $('#ticket');

      if ($ticket.val() != '' || $ticket.val() != 0) {
        $.get(base_url + 'panel/bascula/ajax_load_ticket_docu/', {id: $ticket.val()}, function(data) {

        });
      } else {
        noty({"text": 'Especifique un No. de Ticket', "layout":"topRight", "type": 'error'});
      }
    });
  };

});