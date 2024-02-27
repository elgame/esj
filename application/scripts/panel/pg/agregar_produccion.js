(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();

    autocompleteEmpresas();
    autocompleteAutorizo();
    autocompleteClasifi();

    getSucursales();

    eventCalcular();
    eventAddProducto();

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

  const autocompleteClasifi = function() {
    $('.box-content').on('focus', 'input#clasificacion:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('#id_clasificacion').val(ui.item.id);


          setTimeout(function(){
            let parts = $this.val().split(' - ');
            $this.val((parts.length > 1? parts[0]: $this.val()));
          }, 300);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#id_clasificacion').val('');
        }
      });
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

  let eventAddProducto = function () {
    function valAdd() {
      let fields = ["#clasificacion", "#id_clasificacion", "#cajas_buenas", "#cajas_merma", "#cajas_total",
        "#peso_prom", "#plasta_kg", "#inyectad_kg", "#tiempo_ciclo"];
      let valida = true;
      for (var i = 0; i < fields.length; i++) {
        if ($(fields[i]).val() == '') {
          valida = false;
        }
      }

      return valida;
    };

    $('#addProducto').click(function(event) {
      if (valAdd()) {
        let htmltr = `
          <tr>
            <td>${$("#clasificacion").val()}
              <input type="hidden" name="prod_id[]" id="prod_id" value="">
              <input type="hidden" name="prod_clasificacion[]" id="prod_clasificacion" value="${$("#clasificacion").val()}">
              <input type="hidden" name="prod_id_clasificacion[]" id="prod_id_clasificacion" value="${$("#id_clasificacion").val()}">
              <input type="hidden" name="prod_cajas_buenas[]" id="prod_cajas_buenas" value="${$("#cajas_buenas").val()}">
              <input type="hidden" name="prod_cajas_merma[]" id="prod_cajas_merma" value="${$("#cajas_merma").val()}">
              <input type="hidden" name="prod_total_cajas[]" id="prod_total_cajas" value="${$("#cajas_total").val()}">
              <input type="hidden" name="prod_peso_promedio[]" id="prod_peso_promedio" value="${$("#peso_prom").val()}">
              <input type="hidden" name="prod_plasta[]" id="prod_plasta" value="${$("#plasta_kg").val()}">
              <input type="hidden" name="prod_Kgs_inyectados[]" id="prod_Kgs_inyectados" value="${$("#inyectado_kg").val()}">
              <input type="hidden" name="prod_ciclo[]" id="prod_ciclo" value="${$("#tiempo_ciclo").val()}">
              <input type="hidden" name="prod_del[]" id="prod_del" value="false">
            </td>
            <td>${$("#cajas_buenas").val()}</td>
            <td>${$("#cajas_merma").val()}</td>
            <td>${$("#cajas_total").val()}</td>
            <td>${$("#peso_prom").val()}</td>
            <td>${$("#plasta_kg").val()}</td>
            <td>${$("#inyectado_kg").val()}</td>
            <td>${$("#tiempo_ciclo").val()}</td>
            <td>
              <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
            </td>
          </tr>`;
        $("#table_prod tbody").append(htmltr);

        $("#clasificacion").val('').focus();
        $("#id_clasificacion").val('');
        $("#cajas_buenas").val('');
        $("#cajas_merma").val('');
        $("#cajas_total").val('');
        $("#peso_prom").val('');
        $("#plasta_kg").val('');
        $("#inyectado_kg").val('');
        $("#tiempo_ciclo").val('');
      } else {
        noty({"text":"Los Campos son requeridos para agregar el producto.", "layout":"topRight", "type":"error"});
        $("#clasificacion").focus();
      }
    });

    $('#table_prod').on('click', '#delProd', function(event) {
      event.preventDefault();
      if ($("#prod_id").val() == '') {
        $(this).parents('tr').remove();
      } else {
        $(this).parents('tr').find('#prod_del').val('true');
        $(this).parents('tr').hide();
      }
    });
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