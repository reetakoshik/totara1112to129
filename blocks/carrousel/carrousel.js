// Javascript functions for carrousel module
$(document).ready(function(){
    $('.carrouseldisabled form').eq(0).slideDown();
    carrousel_events();//initiate check list events for the first time
});
//check list events and triggers
function carrousel_events(){
    //file upload submit
    $('.carrouselwrapper .ffilepicker').unbind('click');
    $('.carrouselwrapper .ffilepicker').on( "click", function(event) {
        var form=$(this).parents('form').eq(0);
        var waitrenderer=setInterval(function(){//return to the editted element
            if($('.file-picker.fp-generallayout:visible').parents('.yui3-panel').length===0){
                clearInterval(waitrenderer);
                carrousel_update_data(form.serialize(),$(this));
            }
         }, 1000);
    });
    //change form input
    $('.carrouselwrapper input,.carrouselwrapper select').unbind('change');
    $('.carrouselwrapper input,.carrouselwrapper select').on( "change", function(event) {
        var form=$(this).parents('form').eq(0);
        carrousel_update_data(form.serialize(),$(this));
    });
}
//send data to server
function carrousel_update_data(data,thisObj){
    var wwwroot=$('.carrouselwrapper').eq(0).attr('wwwroot');
    $.ajax({
          type: 'POST',
          url: wwwroot+'/blocks/carrousel/ajax.php',
          data: data,
          success: function(response) {//after ajax completed successfully to following events should be done
             
              if(response.search('pluginfile.php')>-1){//update image on page
                  var imageUrl=$('.carrouselwrapper').eq(0).attr('wwwroot')+response;
                  var previewImage=thisObj.parents('form').eq(0).find('.previewimage').eq(0);
                  if(previewImage.find('img').length){
                      previewImage.find('img').attr('src',imageUrl);
                  }
              }

          }
    });
    carrousel_clean();
}
//clean and reset visual and data
function carrousel_clean(){
    carrousel_events();
    window.onbeforeunload = null;
}


// Image previewing.  
M.block_carrousel = {};
M.block_carrousel.init = function(){
    $('#id_private').on('change', function () {
        var imgHref = $('.filepicker-filename a').attr('href');
        if (typeof imgHref == 'string') {
            $('.previewimage').html('<img src="' + imgHref + '"/>');
        }
    });     
  };
