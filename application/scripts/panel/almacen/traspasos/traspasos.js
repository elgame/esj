$(function(){
  $.ajaxSetup({ cache: false });

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

      // Autocomplete Empresas
    $("#fempresa_to").autocomplete({
        source: base_url + 'panel/bascula/ajax_get_empresas/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
            $("#fid_empresa_to").val(ui.item.id);
            $("#fempresa_to").val(ui.item.label).css({'background-color': '#99FF99'});
        }
    }).keydown(function(e){
        if (e.which === 8) {
            $(this).css({'background-color': '#FFD9B3'});
            $('#fid_empresa_to').val('');
        }
    });

    productos.init();
});

var productos = (function($) {

    var objr = {};

    function init() {
        $("#frmproductos").submit(function(){
            getProductos();
            return false;
        });

        $('#guardar-productos').on('click', function(event) {
            event.preventDefault();

            if ($('.traspaso-cantidad').length > 0) {
                guardar();
            } else {
                noty({"text": 'Seleccione al menos un producto', "layout":"topRight", "type": 'error'});
            }
        });

        // $("#tblproductosrow .prescantidad.vpositive").on('keypress', setEventRow);
    }

    function getProductos(pag) {
        loader.create();

        var param = {
            "empresa_id": $('#fid_empresa').val(),
            "pag": (pag!=undefined? pag: 0 ),
            "filtro_producto": $('#fproducto').val(),
        };

        $.getJSON(base_url+"panel/productos_traspasos/ajax_get_productos/", param, function(data) {
            if(data.response.ico == 'success') {
                $("#content_productos_salida").html(data.data);

            $(".vpositive").numeric({ negative: false }); //Numero positivo
            // supermodal.on("#content_productos a[rel^=superbox]");
            }
        }).always(function() { loader.close(); });
    }

    function changePage(pag) {
        getProductos( (pag? pag: 0) );
    }

    function checkIfAvailable (productoName, elem) {
        var $btn = $(elem)
            $tr = $btn.parents('tr'),
            getData = {
                empresa_id: $('#fid_empresa_to').val(),
                producto: productoName
            };

        if (isCantidadPermitida($tr)) {
            $.getJSON(base_url + 'panel/productos_traspasos/ajax_verifica_producto/', getData, function(response, textStatus) {
                if (response.existe) {
                    var cantidad = $tr.find('.prod-cantidad').val(),
                        precio = $tr.find('.precio_producto').val();

                    add(response.producto, cantidad, precio);
                }
            });
        } else {
            noty({"text": 'La cantidad a traspasar es mayor a la existente.', "layout":"topRight", "type": 'error'});
        }
    }

    function add (producto, cantidad, precio) {
        var $table = $('#table-productos-traspasar');

        var html = '<tr>' +
                        '<td>' + producto.nombre_producto +
                            ' <input type="hidden" name="producto_nombre[]" value="' + producto.nombre_producto + '" class="input traspaso-nombre">' +
                            ' <input type="hidden" name="producto_id[]" value="' + producto.id_producto + '" class="input traspaso-id">' +
                            ' <input type="hidden" name="producto_precio[]" value="' + (precio || 0) + '" class="input traspaso-precio">' +
                        '</td>' +
                        '<td><input type="text" name="producto_cantidad[]" value="' + (cantidad || 0) + '" class="input traspaso-cantidad" style="width: 50px;" readonly></td>' +
                        '<td><input type="text" name="producto_desc[]" value="Traspaso ' + producto.nombre_producto + '" class="input traspaso-desc" max-length="254" style="width: 220px;"></td>' +
                        '<td><button type="button" class="btn btn-danger del-prod" onclick="productos.del(this)"><i class="icon-remove"></i></button></td>' +
                    '</tr>';

        $(html).appendTo($table.find('tbody'));
    }

    function deleteProd (elem) {
        var $tr = $(elem).parents('tr');

        $tr.remove();
    }

    function guardar () {
        loader.create();

        var productos_nombre = [],
            productos_id = [],
            productos_cantidad = [],
            productos_descripcion = [],
            productos_precio = [];

        productos_nombre = arrayCollection('.traspaso-nombre');
        productos_id = arrayCollection('.traspaso-id');
        productos_cantidad = arrayCollection('.traspaso-cantidad');
        productos_descripcion = arrayCollection('.traspaso-desc');
        productos_precio = arrayCollection('.traspaso-precio');

        $.ajax({
            url: base_url + 'panel/productos_traspasos/agregar',
            type: 'POST',
            dataType: 'JSON',
            data: {
                empresa_id_de: $('#fid_empresa').val(),
                empresa_id_para: $('#fid_empresa_to').val(),
                descripcion: $('#descripcion').val(),
                productos_nombre: productos_nombre,
                productos_id: productos_id,
                productos_cantidad: productos_cantidad,
                productos_descripcion: productos_descripcion,
                productos_precio: productos_precio,
            },
        })
        .done(function(response) {
            if (response.passes) {
                window.location = base_url + 'panel/productos_traspasos/?msg=3&idt=' + response.id;
            };
        }).always(function() {
            loader.close();
        });
    }

    function arrayCollection (selector) {
        var x_array = [];

        $(selector).each(function(index, el) {
            x_array.push($(this).val());
        });

        return x_array;
    }

    function  isCantidadPermitida($btn) {
        return parseFloat($btn.find('.esistema').val()) >= parseFloat($btn.find('.prod-cantidad').val());
    }

    objr.init = init;
    objr.page = changePage;
    objr.check = checkIfAvailable;
    objr.del = deleteProd;

  return objr;
})(jQuery);