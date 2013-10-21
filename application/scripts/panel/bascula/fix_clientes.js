$(function(){
  $('.breadcrumb').parent().remove()
  $('#content').removeClass('span10').addClass('span12');
  $('.form-actions')
    // .append('<button type="button" class="btn" id="closeSupermodal">Cerrar</button>')
    .find('a.btn')
    .remove();
  $('form').attr('action', base_url + 'panel/bascula/show_view_agregar_cliente');
});