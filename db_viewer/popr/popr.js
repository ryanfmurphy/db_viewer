
/*
Popr 1.0
Copyright (c) 2015 Tipue
popr is released under the MIT License
http://www.tipue.com/popr
*/

// rfm moved this code
// used to be the meat of popr popup
// now a separate fn so it can be called from callback
// of ajax that gets the dynamic popup content
function showPopup(thisThing, event, popr_show, set, popr_cont) {
    $('.popr_container_top').remove();
    $('.popr_container_bottom').remove();
    
    if (popr_show)
    {
         event.stopPropagation();
         popr_show = false;
    }
    else
    {
         popr_show = true;
    }                   
    
    var d_m = set.mode;
    if ($(thisThing).attr('data-mode'))
    {
         d_m = $(thisThing).attr('data-mode')
         popr_cont = '.popr_container_' + d_m;   
    }
    
    var out = '\
        <div class="popr_container_' + d_m + '">\
            <div class="popr_point_' + d_m + '">\
                <div class="popr_content">'
                    + $('div[data-box-id="' + $(thisThing).attr('data-id') + '"]').html() + '\
                </div>\
            </div>\
        </div>\
    ';
    
    $(thisThing).append(out);

    var w_t = $(popr_cont).outerWidth();
    var w_e = $(thisThing).width();
    var m_l = (w_e / 2) - (w_t / 2);

    $(popr_cont).css('margin-left', m_l + 'px');
    $(thisThing).removeAttr('title alt');
    
    if (d_m == 'top')
    {
         var w_h = $(popr_cont).outerHeight() + 39;
         $(popr_cont).css('margin-top', '-' + w_h + 'px');    
    }
    
    $(popr_cont).fadeIn(set.speed);   


    // added by rfm to keep track of which elem was clicked
    var elem = event.target;
    // track original elem clicked, not popup item
    if (!$(elem).is('.popr-item')) {
        lastClickedElem = event.target;
    }
}

(function($) {

    $.fn.popr = function(options) {
     
          var set = $.extend( {
               
               'speed'        : 200,
               'mode'         : 'bottom'
          
          }, options);

          return this.each(function() {
          
               var popr_cont = '.popr_container_' + set.mode;
               var popr_show = true;

               $(this).click(function(event)
               {
                    if (event.altKey) { // modified by rfm - wanted popup only on alt-click

                        var fieldname = event.target.innerHTML.trim();
                        // this fn calls showPopup in its ajax callback
                        popupJoinTableOptions( // added by rfm
                            fieldname,
                            this, event, popr_show, set, popr_cont
                        );

                    }
               });
                            
               $('html').click(function(e)
               {
                    $('.popr_container_top').remove();
                    $('.popr_container_bottom').remove();
                    popr_show = true;
               });                           
          });
    };
     
})(jQuery);
