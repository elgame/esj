$(function(){
  $('body').on('click', '.del-all', function(event) {
    var $a = $('#pallet'+$(this).attr('data-pallet')), href = '';
    if ($(this).is(':checked')) {
      $a.attr('href', $a.attr('href')+'&d=t');
    } else {
      href = $a.attr('href').split('&d=t');
      $a.attr('href', href[0]);
    }
  });

  $('.modal-series').on('click', function(event) {
    $("#modal-series").modal('show');
    $('#BtnRemisionar').data('id', $(this).data('id'));
    // $('#BtnRemisionar').attr('href', $('#BtnRemisionar').data('href')+$(this).data('id'));
  });

  $('#BtnRemisionar').on('click', function(event) {
    var $this = $(this);
    $this.attr('href', $this.data('href')+$this.data('id')+'&serie='+$('#serieRemisionar').val());
  });
});