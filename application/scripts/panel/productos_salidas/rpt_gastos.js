(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {
    setAutocomplete();
    autocompleteEmpresa();
    // autocompleteProductos();

    $('#form').on('submit', function(event) {
      var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          ddesglosado: $("#ddesglosado:checked").val(),
          dmovimientos: $("#dmovimientos:checked").val(),
          dareas: [],
        };

      $(".treeviewcustom input[type=checkbox]:checked").each(function(index, el) {
        url.dareas.push($(this).val());
      });

      linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

      console.log(linkDownXls.attr('href'));

      if (linkDownXls.attr('href') && url.dareas.length == 0) {
        noty({"text": 'Seleccione una area', "layout":"topRight", "type": 'error'});
        return false;
      }
    });

    $(".treeviewcustom").treeview({
      collapsed: true,
      persist: "location",
      unique: true
    }).find('input[data-tipo=1]').attr('checked', 'checked');
    // $(".treeviewcustom").find('li ul').remove();

    $(".treeviewcustom li").each(function(index, el) {
      var $this = $(this);
      if ($this.find('ul').length > 0) {
        $this.find('label').filter(function(index) {
          if ($("> ul", $(this).parent() ).length > 0 && $( ".btnsel", this ).length === 0)
            return true;
          else
            return false;
        }).append(' <span class="label label-warning btnsel" style="cursor:pointer;">Marcar</span>')
      }
    });

    $("#form").submit();
  });

  function setAutocomplete () {
    $('.treeviewcustom').on('click', '.btnsel', function(event) {
      var $this = $(this), $parent = $this.parent();
      $('.treeviewcustom input').removeAttr('checked');
      $parent.parent().find('> ul > li > label > input').attr('checked', 'checked');
      $parent.parent().find('> .hitarea').click();
      setTimeout(function(){ $parent.find('input').removeAttr('checked'); }, 100);
    });
  }

  function autocompleteEmpresa () {
    // Autocomplete Empresas
    $("#dempresa").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_empresa").val(ui.item.id);
        $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        $('#did_empresa').val('');
      }
    });
  }

});
