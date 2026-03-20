/* Genel JS: sidebar toggle, ajax helper, toast */
$(function(){
  $('#sidebarToggle').on('click', function(){
    $('#sidebar').toggleClass('collapsed');
    if($('#sidebar').hasClass('collapsed')){
      $('#sidebar').css('width','72px');
      $('#sidebar .brand h5, #sidebar .brand small').hide();
    } else {
      $('#sidebar').css('width','260px');
      $('#sidebar .brand h5, #sidebar .brand small').show();
    }
  });

  $('#mobileMenu').on('click', function(){
    $('#sidebar').toggleClass('open');
  });
});

function apiGet(url, success, fail){
  $.ajax({url:url, dataType:'json', cache:false})
  .done(success)
  .fail(function(xhr){ if(fail) fail(xhr); else console.error(xhr); });
}

function toastSuccess(msg){
  Swal.fire({toast:true,position:'top-end',icon:'success',title:msg,showConfirmButton:false,timer:2000});
}

function toastError(msg){
  Swal.fire({toast:true,position:'top-end',icon:'error',title:msg,showConfirmButton:false,timer:2500});
}
