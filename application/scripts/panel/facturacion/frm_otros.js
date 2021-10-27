$(function(){
  $('#table_prod, #table_prod2').on('click', '.btn.ventasmore', function(){
    var $group = $(this).parents(".btn-group"),
      $ul = $group.find('.dropdown-menu.ventasmore');
    if ($ul.css('display') == 'none') {
      $ul.show();
      $group.find('#prod_dcalidad').focus();
    } else {
      $ul.hide();
    }
  });

  $('#table_prod, #table_prod2').on('click', '.btn.impuestosEx', function(){
    var $group = $(this).parents(".btn-group"),
      $ul = $group.find('.dropdown-menu.impuestosEx');
    if ($ul.css('display') == 'none') {
      $ul.show();
      $group.find('#dieps').focus();
    } else {
      $ul.hide();
    }
  });

  $("#table_prod tbody tr .btn-group .btn.ventasmore").click();
  $("#table_prod tbody tr:last-child .btn-group .btn.ventasmore").click();

  autocompleteCalidadLive();
  autocompleteTamanioLive();
  autocompleteTamanioProdLive();
  closeGroupMoreOut();
  extrasProductosEspeciales();

  // ComercioExterior
  // Mercancias
  eventOnClickBtnAddMercancias();
  eventOnClickBtnDelMercancias();
  eventOnClickBtnAddMercanciasDescEspe();
  autocompleteCatalogos();
  autocompleteFraccionArancelaria();
  //tabs Comercio exterior
  $('#myTab a:first').tab('show');
  $('#myTab a').click(function (e) {
    e.preventDefault();
    $(this).tab('show');
  });
  //tooltip
  $('.icon-question-sign.helpover').tooltip({"placement":"bottom",delay: { show: 150, hide: 50 }});

});

function openGroupMore(select) {
  var $group = $(select).parents(".btn-group");
  $group.find('.btn.ventasmore').click();
}

function closeGroupMoreOut() {
  $('#table_prod').on('focusout', '#prod_ddescripcion2', function(){
    var $group = $(this).parents(".btn-group");
    $group.find('.btn.ventasmore').click();
  });
}

function autocompleteCalidadLive () {
  $('#table_prod').on('focus', 'input#prod_dcalidad:not(.ui-autocomplete-input)', function(event) {
    $(this).autocomplete({
      source: base_url+'panel/areas_otros/ajax_get_calidades/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this),
            $tr = $this.parent().parent();

        $this.css("background-color", "#B0FFB0");

        $tr.find('#prod_did_calidad').val(ui.item.id);
      }
    }).keydown(function(event){
      if(event.which == 8 || event == 46) {
        var $tr = $(this).parent().parent();

        $(this).css("background-color", "#FFD9B3");
        $tr.find('#prod_did_calidad').val('');
      }
    });
  });
}

function autocompleteTamanioLive () {
  $('#table_prod').on('focus', 'input#prod_dtamanio:not(.ui-autocomplete-input)', function(event) {
    $(this).autocomplete({
      source: base_url+'panel/areas_otros/ajax_get_tamano/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this),
            $tr = $this.parent().parent();

        $this.css("background-color", "#B0FFB0");

        $tr.find('#prod_did_tamanio').val(ui.item.id);
      }
    }).keydown(function(event){
      if(event.which == 8 || event == 46) {
        var $tr = $(this).parent().parent();

        $(this).css("background-color", "#FFD9B3");
        $tr.find('#prod_did_tamanio').val('');
      }
    });
  });
}

function autocompleteTamanioProdLive () {
  $('#table_prod').on('focus', 'input#prod_dtamanio_prod:not(.ui-autocomplete-input)', function(event) {
    $(this).autocomplete({
      source: base_url+'panel/rastreabilidad/ajax_get_calibres/?tipo=c',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this),
            $tr = $this.parent().parent();

        $this.css("background-color", "#B0FFB0");

        $tr.find('#prod_did_tamanio_prod').val(ui.item.id);
      }
    }).keydown(function(event){
      if(event.which == 8 || event == 46) {
        var $tr = $(this).parent().parent();

        $(this).css("background-color", "#FFD9B3");
        $tr.find('#prod_did_tamanio_prod').val('');
      }
    });
  });
}

/****** Supervisor, certificados, etc  ********/
function extrasProductosEspeciales() {

  $("#btn_supcarga_add").click(function(event) {
    var $this = $(this),
      $modal = $this.parents("#modal-supcarga"),
      grup = $modal.find('.modal-body .grup_datos:first').clone();

    var prov = grup.find('label[for="pproveedor_supcarga"]');
    prov.append('<i class="icon-remove"></i> ');
    grup.find('#pproveedor_supcarga').val('').css("background-color", "inherit").one('focus', setAutocompleteProveedores);
    grup.find('#supcarga_id_proveedor').val('');
    grup.find('#supcarga_numero').val('');
    grup.find('#supcarga_bultos').val('');
    grup.find('#supcarga_num_operacion').val('');

    $modal.find('.modal-body').append(grup);

    prov.find('i.icon-remove').click(function(event) {
      grup.remove();
    });
  });

  $("#btn_seguro_add").click(function(event) {
    var $this = $(this),
      $modal = $this.parents("#modal-seguro"),
      grup = $modal.find('.modal-body .grup_datos:first').clone();

    var prov = grup.find('label[for="pproveedor_seguro"]');
    prov.append('<i class="icon-remove"></i> ');
    grup.find('#pproveedor_seguro').val('').css("background-color", "inherit").one('focus', setAutocompleteProveedores);
    grup.find('#seg_id_proveedor').val('');
    grup.find('#seg_poliza').val('');

    $modal.find('.modal-body').append(grup);

    prov.find('i.icon-remove').click(function(event) {
      grup.remove();
    });
  });

  $("#btn_certificado51_add").click(function(event) {
    var $this = $(this),
      $modal = $this.parents("#modal-certificado51"),
      grup = $modal.find('.modal-body .grup_datos:first').clone();

    var prov = grup.find('label[for="pproveedor_certificado51"]');
    prov.append('<i class="icon-remove"></i> ');
    grup.find('#pproveedor_certificado51').val('').css("background-color", "inherit").one('focus', setAutocompleteProveedores);
    grup.find('#cert_id_proveedor51').val('');
    grup.find('#cert_certificado51').val('');
    grup.find('#cert_bultos51').val('');
    grup.find('#cert_num_operacion51').val('');

    $modal.find('.modal-body').append(grup);

    prov.find('i.icon-remove').click(function(event) {
      grup.remove();
    });
  });

  $("#btn_certificado52_add").click(function(event) {
    var $this = $(this),
      $modal = $this.parents("#modal-certificado52"),
      grup = $modal.find('.modal-body .grup_datos:first').clone();

    var prov = grup.find('label[for="pproveedor_certificado52"]');
    prov.append('<i class="icon-remove"></i> ');
    grup.find('#pproveedor_certificado52').val('').css("background-color", "inherit").one('focus', setAutocompleteProveedores);
    grup.find('#cert_id_proveedor52').val('');
    grup.find('#cert_certificado52').val('');
    grup.find('#cert_bultos52').val('');
    grup.find('#cert_num_operacion52').val('');

    $modal.find('.modal-body').append(grup);

    prov.find('i.icon-remove').click(function(event) {
      grup.remove();
    });
  });

}


/**
 * COMERCIO EXTERIOR
 */
var eventOnClickBtnAddMercancias = function () {
  $("#indexMercancias").text( (parseInt($("#indexMercancias").text())||0)+1 );
  $('#btn-add-mercancias').on('click', function(event) {
    addMercancias();
  });
};

var eventOnClickBtnDelMercancias = function () {
  $('#table-mercancias').on('click', '.btn-del-mercancias', function(event) {
    if ($(this).attr('data-index')) {
      $("tr.DescripcionesEspecificas"+$(this).attr('data-index')).remove();
    }
    $(this).parents('tr').remove();
  });
};

var eventOnClickBtnAddMercanciasDescEspe = function () {
  $('#table-mercancias').on('click', '.btn-add-desc-especifica', function(event) {
    addMercanciasDescEspe($(this).attr('data-index'), $(this).parent().parent());
  });
};

var jumpIndexMercancias = 0, indexMercancias = 0;
var addMercancias = function () {
  var $tabla    = $('#table-mercancias'),
      trHtml    = '',
      indexJump = jumpIndexMercancias + 1,
      exist     = false,
      $selectAdquiriente = $('#adquiriente_copro_soc'),
      auxAgregar = false,
      coproSoci = $selectAdquiriente.find('option:selected').val();
      indexMercancias = parseInt($("#indexMercancias").text())||0;

  $trHtml = $('<tr>' +
                '<td class="center"><input type="text" name="comercioExterior[mercancias][noIdentificacion]['+indexMercancias+']" value="" class="span12 sikey jumpMercancia'+jumpIndexMercancias+'" data-next="jumpMercancia'+(++jumpIndexMercancias)+'" maxlength="100"></td>' +
                '<td class="center"><input type="text" name="comercioExterior[mercancias][fraccionArancelaria]['+indexMercancias+']" value="" class="fraccionArancelaria span12 sikey jumpMercancia'+jumpIndexMercancias+'" data-next="jumpMercancia'+(++jumpIndexMercancias)+'" maxlength="20"></td>' +
                '<td class="center"><input type="text" name="comercioExterior[mercancias][cantidadAduana]['+indexMercancias+']" value="" class="span12 sikey jumpMercancia'+jumpIndexMercancias+' vpositive" data-next="jumpMercancia'+(++jumpIndexMercancias)+'"></td>' +
                '<td class="center">' +
                  '<select name="comercioExterior[mercancias][unidadAduana]['+indexMercancias+']" class="span12 sikey ceUnidadAduana jumpMercancia'+jumpIndexMercancias+'" data-next="jumpMercancia'+(++jumpIndexMercancias)+'">' +
                  $("#mercancias-unidades").html() +
                  '</select>' +
                '</td>' +
                '<td class="center"><input type="text" name="comercioExterior[mercancias][valorUnitarioAduana]['+indexMercancias+']" value="" class="span12 sikey jumpMercancia'+jumpIndexMercancias+' vpositive" data-next="jumpMercancia'+(++jumpIndexMercancias)+'"></td>' +
                '<td class="center"><input type="text" name="comercioExterior[mercancias][valorDolares]['+indexMercancias+']" value="" class="span12 sikey jumpMercancia'+jumpIndexMercancias+' vpositive" data-next="jumpMercancia'+(++jumpIndexMercancias)+'"></td>' +
                '<td class="center">' +
                  '<button type="button" class="btn btn-danger btn-del-mercancias" data-index="'+indexMercancias+'"><i class="icon-remove"></i></button>' +
                  '<button type="button" class="btn btn-success btn-add-desc-especifica" data-index="'+indexMercancias+'"><i class="icon-plus"></i></button>' +
                '</td>' +
              '</tr>'
            );

  $($trHtml).appendTo($tabla.find('tbody'));
  ++indexMercancias;
  $("#indexMercancias").text(indexMercancias);

  for (i = indexJump-1, max = jumpIndexMercancias; i <= max; i += 1) {
    $.fn.keyJump.setElem($('.jumpMercancia'+i));
  }

  $(".vpositive").numeric({ negative: false }); //Numero positivo
};

var addMercanciasDescEspe = function (index, $tr) {
  var $tabla    = $('#table-mercancias'),
      trHtml    = '',
      indexJump = jumpIndexMercancias + 1,
      exist     = false,
      $selectAdquiriente = $('#adquiriente_copro_soc'),
      auxAgregar = false,
      coproSoci = $selectAdquiriente.find('option:selected').val();

  $trHtml = $('<tr class="DescripcionesEspecificas'+index+'">' +
                '<td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas]['+index+'][marca][]" value="" placeholder="Marca" class="span12 sikey jumpMercancia'+jumpIndexMercancias+'" data-next="jumpMercancia'+(++jumpIndexMercancias)+'" maxlength="35"></td>' +
                '<td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas]['+index+'][modelo][]" value="" placeholder="Modelo" class="span12 sikey jumpMercancia'+jumpIndexMercancias+'" data-next="jumpMercancia'+(++jumpIndexMercancias)+'" maxlength="80"></td>' +
                '<td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas]['+index+'][subModelo][]" value="" placeholder="SubModelo" class="span12 sikey jumpMercancia'+jumpIndexMercancias+'" data-next="jumpMercancia'+(++jumpIndexMercancias)+'" maxlength="50"></td>' +
                '<td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas]['+index+'][numeroSerie][]" value="" placeholder="NumeroSerie" class="span12 sikey jumpMercancia'+jumpIndexMercancias+'" data-next="jumpMercancia'+(++jumpIndexMercancias)+'" maxlength="40"></td>' +
                '<td class="center">' +
                  '<button type="button" class="btn btn-danger btn-del-mercancias"><i class="icon-remove"></i></button>' +
                '</td>' +
              '</tr>'
            );

  $tr.after($trHtml);

  for (i = indexJump-1, max = jumpIndexMercancias; i <= max; i += 1) {
    $.fn.keyJump.setElem($('.jumpMercancia'+i));
  }

  $(".vpositive").numeric({ negative: false }); //Numero positivo
};

// Autocomplete para los catalogos.
var autocompleteCatalogos = function () {
  var $dpais =  $("#cce_destinatario_dom_pais"), $destado = $("#cce_destinatario_dom_estado"),
      $dmunicipio = $("#cce_destinatario_dom_municipio"), $dlocalidad = $("#cce_destinatario_dom_localidad"),
      $dcp = $("#cce_destinatario_dom_codigopostal"), $dcolonia = $("#cce_destinatario_dom_colonia"),
      $cce_emisor_pais = $("#cce_emisor_pais"), $cce_emisor_estado = $("#cce_emisor_estado"), $cce_emisor_municipio = $("#cce_emisor_municipio"),
      $cce_emisor_localidad = $("#cce_emisor_localidad"), $cce_emisor_cp = $("#cce_emisor_cp"), $cce_emisor_colonia = $("#cce_emisor_colonia"),
      $cce_receptor_pais = $("#cce_receptor_pais"), $cce_receptor_estado = $("#cce_receptor_estado"),
      $cce_propietario_residenciaFiscal = $("#cce_propietario_residenciaFiscal");

  $cce_propietario_residenciaFiscal.autocomplete({
    source: base_url + 'panel/catalogos/cpaises',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $cce_propietario_residenciaFiscal.css("background-color", "#A1F57A");
      setTimeout(function(){
        $cce_propietario_residenciaFiscal.val(ui.item.id);
        $("span.cce_propietario_residenciaFiscal").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $cce_propietario_residenciaFiscal.css("background-color", "#FFD071");
      $("span.cce_propietario_residenciaFiscal").hide();
    }
  });

  // ---------------------------------

  $cce_receptor_pais.autocomplete({
    source: base_url + 'panel/catalogos/cpaises',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $cce_receptor_pais.css("background-color", "#A1F57A");
      setTimeout(function(){
        $cce_receptor_pais.val(ui.item.id);
        $("span.cce_receptor_pais").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $cce_receptor_pais.css("background-color", "#FFD071");
      $("span.cce_receptor_pais").hide();
    }
  });

  $cce_receptor_estado.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/cestados',
        dataType: "json",
        data: {
          'c_pais': $cce_receptor_pais.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $cce_receptor_estado.css("background-color", "#A1F57A");
      setTimeout(function(){
        $cce_receptor_estado.val(ui.item.id);
        $("span.cce_receptor_estado").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $cce_receptor_estado.css("background-color", "#FFD071");
      $("span.cce_receptor_estado").hide();
    }
  });

  // -------------------------------------------------

  $cce_emisor_pais.autocomplete({
    source: base_url + 'panel/catalogos/cpaises',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $cce_emisor_pais.css("background-color", "#A1F57A");
      setTimeout(function(){
        $cce_emisor_pais.val(ui.item.id);
        $("span.cce_emisor_pais").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $cce_emisor_pais.css("background-color", "#FFD071");
      $("span.cce_emisor_pais").hide();
    }
  });

  $cce_emisor_estado.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/cestados',
        dataType: "json",
        data: {
          'c_pais': $cce_emisor_pais.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $cce_emisor_estado.css("background-color", "#A1F57A");
      setTimeout(function(){
        $cce_emisor_estado.val(ui.item.id);
        $("span.cce_emisor_estado").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $cce_emisor_estado.css("background-color", "#FFD071");
      $("span.cce_emisor_estado").hide();
    }
  });

  $cce_emisor_municipio.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/cmunicipios',
        dataType: "json",
        data: {
          'c_estado': $cce_emisor_estado.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $cce_emisor_municipio.css("background-color", "#A1F57A");
      setTimeout(function(){
        $cce_emisor_municipio.val(ui.item.id);
        $("span.cce_emisor_municipio").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $cce_emisor_municipio.css("background-color", "#FFD071");
      $("span.cce_emisor_municipio").hide();
    }
  });

  $cce_emisor_localidad.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/clocalidades',
        dataType: "json",
        data: {
          'c_estado': $cce_emisor_estado.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $cce_emisor_localidad.css("background-color", "#A1F57A");
      setTimeout(function(){
        $cce_emisor_localidad.val(ui.item.id);
        $("span.cce_emisor_localidad").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $cce_emisor_localidad.css("background-color", "#FFD071");
      $("span.cce_emisor_localidad").hide();
    }
  });

  $cce_emisor_cp.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/ccps',
        dataType: "json",
        data: {
          'c_estado': $cce_emisor_estado.val(),
          'c_municipio': $cce_emisor_municipio.val(),
          'c_localidad': $cce_emisor_localidad.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $cce_emisor_cp.css("background-color", "#A1F57A");
      setTimeout(function(){
        $cce_emisor_cp.val(ui.item.id);
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $cce_emisor_cp.css("background-color", "#FFD071");
    }
  });

  $cce_emisor_colonia.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/ccolonias',
        dataType: "json",
        data: {
          'c_cp': $cce_emisor_cp.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $cce_emisor_colonia.css("background-color", "#A1F57A");
      setTimeout(function(){
        $cce_emisor_colonia.val(ui.item.id);
        $("span.cce_emisor_colonia").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $cce_emisor_colonia.css("background-color", "#FFD071");
      $("span.cce_emisor_colonia").hide();
    }
  });

  // -------------------------------------------------

  $dpais.autocomplete({
    source: base_url + 'panel/catalogos/cpaises',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dpais.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dpais.val(ui.item.id);
        $("span.dpais").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dpais.css("background-color", "#FFD071");
      $("span.dpais").hide();
    }
  });

  $destado.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/cestados',
        dataType: "json",
        data: {
          'c_pais': $dpais.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $destado.css("background-color", "#A1F57A");
      setTimeout(function(){
        $destado.val(ui.item.id);
        $("span.destado").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $destado.css("background-color", "#FFD071");
      $("span.destado").hide();
    }
  });

  $dmunicipio.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/cmunicipios',
        dataType: "json",
        data: {
          'c_estado': $destado.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dmunicipio.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dmunicipio.val(ui.item.id);
        $("span.dmunicipio").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dmunicipio.css("background-color", "#FFD071");
      $("span.dmunicipio").hide();
    }
  });

  $dlocalidad.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/clocalidades',
        dataType: "json",
        data: {
          'c_estado': $destado.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dlocalidad.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dlocalidad.val(ui.item.id);
        $("span.dlocalidad").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dlocalidad.css("background-color", "#FFD071");
      $("span.dlocalidad").hide();
    }
  });

  $dcp.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/ccps',
        dataType: "json",
        data: {
          'c_estado': $destado.val(),
          'c_municipio': $dmunicipio.val(),
          'c_localidad': $dlocalidad.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dcp.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dcp.val(ui.item.id);
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dcp.css("background-color", "#FFD071");
    }
  });

  $dcolonia.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/ccolonias',
        dataType: "json",
        data: {
          'c_cp': $dcp.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dcolonia.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dcolonia.val(ui.item.id);
        $("span.dcolonia").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dcolonia.css("background-color", "#FFD071");
      $("span.dcolonia").hide();
    }
  });

};

var setCeDatosEmisorRece = function (data, tipo = 'emisor') {
  $('#cce_'+tipo+'_calle').val(data.calle)
  $('#cce_'+tipo+'_no_exterior').val(data.no_exterior)
  $('#cce_'+tipo+'_no_interior').val(data.no_interior)
  $('#cce_'+tipo+'_pais').val(data.pais)
  $('#cce_'+tipo+'_estado').val(data.estado)
  $('#cce_'+tipo+'_municipio').val(data.municipio)
  $('#cce_'+tipo+'_localidad').val(data.localidad)
  $('#cce_'+tipo+'_cp').val(data.cp)
  $('#cce_'+tipo+'_colonia').val(data.colonia)
};

// Autocomplete para las empresas.
var autocompleteFraccionArancelaria = function () {
  $('#table-mercancias').on('focus', 'input.fraccionArancelaria:not(.ui-autocomplete-input)', function(event) {
    $(this).autocomplete({
      source: base_url + 'panel/catalogos/fraccionArancelaria',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $empresa =  $(this),
        $tr = $empresa.parent().parent();

        setTimeout(function(){
          $empresa.val(ui.item.id).css("background-color", "#A1F57A");
        }, 300);
        $tr.find('select.ceUnidadAduana option[value="'+ui.item.item.unidad+'"]').prop('selected', true);
        console.log($tr.find('select.ceUnidadAduana option[value="'+ui.item.item.unidad+'"]'), $tr);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $(this).css("background-color", "#FFD071");
      }
    });
  });
};