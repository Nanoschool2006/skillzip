define(['jquery', 'core/ajax', 'core/templates'], function($, Ajax, Templates){
    
    const limitto = 10;

    // const initialize = () => {
    //     // Todo: Initialize the js to handle block content.
    //     initLazyLoader();
    // };

    const load = () => {
        $(document).ready(function(){
            let addblockbutton = "#epbaddblockbutton";
            let epbcustommodal = "#epb_custom_modal.modal";
            let epbclosebutton = epbcustommodal + " .modal-content .close";
            let epbcancelbutton = epbcustommodal + " .modal-content .cancel";
            let epbupdatebutton = epbcustommodal + " .modal-content .card-header .update-content";
            let epbupdatelistbutton = epbcustommodal + " .modal-content .action-buttons-modal .updateblocklist";

            $(addblockbutton).on('click', function(){
                $(epbcustommodal).removeClass('d-none');
            });

            $(document).on( "click", addblockbutton, function() {
                $(epbcustommodal).removeClass('d-none');
            });

            $(epbclosebutton + ","+ epbcancelbutton).on('click', function(){
                $(epbcustommodal).addClass('d-none');
            });

            $(document).on( "click", epbupdatebutton, function() {
                let _this = this;

                $(_this).attr('disabled', true);
                $(_this).find('.fa').removeClass('fa-download').addClass("fa-refresh rotate");

                Ajax.call([{
                    methodname: 'edwiserpagebuilder_update_block_content',
                    args: {blockname: $(_this).attr("data-blockname")},
                    done: function(data) {
                        $(_this).find('.fa').removeClass("rotate");
                        if (data.status == false) {
                            updateButton(_this);
                        } else {
                            $(_this).attr('disabled', false);
                        }
                    },
                    fail:function(){
                        $(_this).find('.fa').removeClass("rotate");
                        $(_this).attr('disabled', false);
                    }
                }]);
            });

            $(document).on( "click", epbupdatelistbutton, function() {

                let _this = this;

                $(_this).attr('disabled', true);
                $(_this).find('.fa').removeClass('fa-download').addClass("fa-refresh rotate");
                var edwpageurl = $(_this).next().val();

                Ajax.call([{
                    methodname: 'edwiserpagebuilder_fetch_blocks_list',
                    args: {edwpageurl: (edwpageurl != "0")? edwpageurl:"?"},
                    done: function(data) {
                        $(_this).find('.fa').removeClass("rotate");
                        if (data.status == true) {
                            $(epbcustommodal + ' .addblock-modal-body .default-blocks [data-parentblock="edwiseradvancedblock"]').remove();
                            $(epbcustommodal + " .addblock-modal-body .default-blocks").prepend(data.html);
                            updateButton(_this);
                        } else {
                            $(_this).attr('disabled', false);
                        }
                    },
                    fail:function(){
                        $(_this).find('.fa').removeClass("rotate");
                        $(_this).attr('disabled', false);
                    }
                }]);
            });
            

            function updateButton(button) {
                $(button).removeClass('btn-primary').addClass('btn-success');
                $(button).find('.fa').removeClass('fa-refresh').addClass('fa-check');
                jQuery(button).find('span').html("Updated");
            }

        });
    };

    // const initLazyLoader = () => {
    //     loadResults(0, limitto);
        
    //     $('.addblock-modal-body .block-cards').scroll(function() {
    //         // if($("#loading").css('display') == 'none') {
    //         if($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
    //             var limitStart = $(".addblock-modal-body .block-cards .data").length;
    //             loadResults(limitStart);
    //         }
    //         // }
    //     });
    // };

    // const loadResults = (from, to) => {
    //     Ajax.call([{
    //         methodname: 'edwiserpagebuilder_fetch_blocks_list',
    //         args: {limitfrom: from, limitto: to},
    //         done: function(data) {
    //             Templates.render('theme_remui/block_card', data)
    //             .then(function(html, js) {
    //                 Templates.appendNodeContents('.block-cards.edwiser-blocks', html, js);
    //             }).fail(function(ex) {
    //                 console.log("Error - rendering blocks");
    //             });
    //         },
    //         fail:function(){
    //             console.log("Ajax failed");
    //         }
    //     }]);
    // }

    return {
        // init: initialize,
        load: load
    };
});
