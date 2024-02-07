(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();

    autocompleteEmpresas();
    autocompleteAutorizo();

    getSucursales();

    eventCalcular();

  });


  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */


  /*
   |------------------------------------------------------------------------
   | Autocompletes
   |------------------------------------------------------------------------
   */

  // Autocomplete para las empresas.
  var autocompleteEmpresas = function () {
    $("#dempresa").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $empresa =  $(this);

        $empresa.val(ui.item.id);
        $("#did_empresa").val(ui.item.id);
        $empresa.css("background-color", "#A1F57A");

        getSucursales();
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#dempresa").css("background-color", "#FFD071");
        $("#did_empresa").val('');

        getSucursales();
      }
    });
  };

  var autocompleteAutorizo= function () {
    $("#djefeTurn").autocomplete({
      source: base_url + 'panel/usuarios/ajax_get_usuarios/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var djefeTurn =  $(this);

        djefeTurn.css("background-color", "#A1F57A");
        $("#djefeTurnId").val(ui.item.id);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#djefeTurn").css("background-color", "#FFD071");
        $("#djefeTurnId").val('');
      }
    });
  };


  /*
   |------------------------------------------------------------------------
   | Events
   |------------------------------------------------------------------------
   */
  var totalKgInyectados = function(event) {
    let peso_prom = parseFloat($('#peso_prom').val())||0;
    let plasta_kg = parseFloat($('#plasta_kg').val())||0;
    let cajas_total = parseFloat($('#cajas_total').val())||0;
    $('#inyectado_kg').val(((peso_prom * cajas_total) + plasta_kg).toFixed(2));
  };

  var eventCalcular = function () {
    $('.box-content').on('keyup', '#cajas_buenas, #cajas_merma', function(event) {
      let cajas_buenas = parseFloat($('#cajas_buenas').val())||0;
      let cajas_merma = parseFloat($('#cajas_merma').val())||0;
      $('#cajas_total').val((cajas_buenas + cajas_merma));

      totalKgInyectados();
    });

    $('.box-content').on('keyup', '#peso_prom, #plasta_kg', totalKgInyectados);

  };




  /*
   |------------------------------------------------------------------------
   | Helpers
   |------------------------------------------------------------------------
   */

  // Regresa true si esta seleccionada una empresa si no false.
  var isEmpresaSelected = function () {
    return $('#did_empresa').val() !== '';
  };

  var getSucursales = function () {
    var params = {
      did_empresa: $('#did_empresa').val()
    };

    hhtml = '<option value=""></option>';
    if (params.did_empresa > 0) {
      $.ajax({
          url: base_url + 'panel/empresas/ajax_get_sucursales/',
          dataType: "json",
          data: params,
          success: function(data) {
            if(data.length > 0) {
              let idSelected = $('#sucursalId').data('selected'), selected = '';
              for (var i = 0; i < data.length; i++) {
                selected = (idSelected == data[i].id_sucursal? ' selected': '');
                hhtml += '<option value="'+data[i].id_sucursal+'" '+selected+'>'+data[i].nombre_fiscal+'</option>';
              }

              $('#sucursalId').html(hhtml).attr('required', 'required');
              $('.sucursales').show();
            } else {
              $('#sucursalId').html(hhtml).removeAttr('required');
              $('.sucursales').hide();
            }
          }
      });
    } else {
      $('#sucursalId').html(hhtml).removeAttr('required');
      $('.sucursales').hide();
    }
  };

});