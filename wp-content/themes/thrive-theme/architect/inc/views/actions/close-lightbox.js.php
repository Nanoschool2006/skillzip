<?php echo 'function(trigger,action,config){' ?>
const closeButtons = ThriveGlobal.$j( '.tve_p_lb_close' );

closeButtons.each( ( index, button ) => {
    button.click();
} );
<?php echo 'return false;}';
