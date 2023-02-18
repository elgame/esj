$(function () {
    // Autocomplete Empresas
  $("#pempresa").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#pid_empresa").val(ui.item.id);
      $("#pempresa").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#pid_empresa').val('');
    }
  });

  autocompleteLabores();
});

var autocompleteLabores = function () {
  $('.form-horizontal').on('focus', 'input#departamento:not(.ui-autocomplete-input)', function(event) {
    $(this).autocomplete({
      source: base_url + 'panel/labores_codigo/ajax_get_departamentos/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        // var $this = $(this);
        // $this.css("background-color", "#B0FFB0");
        // $('#dlaborId').val(ui.item.id);
      }
    }).keydown(function(event){
      if(event.which == 8 || event == 46) {
        // var $this = $(this);
        // $(this).css("background-color", "#FFD9B3");
        // $('#dlaborId').val('');
      }
    });
  });
};