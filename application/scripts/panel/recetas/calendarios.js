(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    autocompleteCultivo();
    autocompleteEmpresas();
    iniCalendario();

    loadEventosCalendario();
  });

  var getCalendarios = function () {
    $.ajax({
        url: base_url + 'panel/recetas/ajax_get_calendarios/',
        dataType: "json",
        data: {id_area: $('#areaId').val()},
        success: function(data) {
          console.log('test', data);
          if (data && data.length > 0) {
            html = '';
            $.each(data, function(index, val) {
               html += '<option value="'+val.id+'">'+val.nombre+'</option>';
            });
            $('#calendario').html(html);
          }
        }
    });
  };

  /*
   |------------------------------------------------------------------------
   | Autocompletes
   |------------------------------------------------------------------------
   */

  var autocompleteCultivo = function () {
    $("#darea").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0) {
          params.did_empresa = $("#empresaId").val();
        }
        $.ajax({
            url: base_url + 'panel/areas/ajax_get_areas/',
            dataType: "json",
            data: params,
            success: function(data) {
                response(data);
            }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $area =  $(this);

        $("#areaId").val(ui.item.id);
        $area.css("background-color", "#A1F57A");

        getCalendarios();
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#darea").css("background-color", "#FFD071");
        $("#areaId").val('');
      }
    });
  };

  // Autocomplete para las empresas.
  var autocompleteEmpresas = function () {
    $("#empresa").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $empresa =  $(this);

        $empresa.val(ui.item.id);
        $("#empresaId").val(ui.item.id);
        $empresa.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#empresa").css("background-color", "#FFD071");
        $("#empresaId").val('');
      }
    });
  };

  var loadEventosCalendario = function () {
    // $('#ffecha1').on('change', function(event) {
    //   var fecha = $(this).val();
    //   $('#calendar').fullCalendar('gotoDate', fecha );
    //   // $('#calendar').fullCalendar('option', 'locale', 'fr');
    //   // $('#calendar').fullCalendar('option', {
    //   //   defaultDate: fecha,
    //   // });
    // });
  };


  function iniCalendario() {
    var fecha = $("#ffecha1").val(),
    eventos = JSON.parse($('#eventos').text());
    console.log('test', eventos);

    //initialize the calendar
    $('#calendar').fullCalendar({
      header: {
        left: 'prev,next today',
        center: 'title',
        right: 'month'
      },
      editable: false,
      droppable: false, // this allows things to be dropped onto the calendar !!!
      defaultDate: fecha,
      lang: 'es',
      events: eventos
    });
  }

});
