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

  $("#table_prod tbody tr .btn-group .btn.ventasmore").click();
  $("#table_prod tbody tr:last-child .btn-group .btn.ventasmore").click();

  autocompleteCalidadLive();
  autocompleteTamanioLive();
  closeGroupMoreOut();

  // //tabs Comercio exterior
  // $('#myTab a:first').tab('show');
  // $('#myTab a').click(function (e) {
  //   e.preventDefault();
  //   $(this).tab('show');
  // });
  // //tooltip
  // $('.icon-question-sign.helpover').tooltip({"placement":"bottom",delay: { show: 150, hide: 50 }});

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