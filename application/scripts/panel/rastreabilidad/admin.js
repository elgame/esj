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
});