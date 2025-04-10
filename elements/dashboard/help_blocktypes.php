<?php

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Url;

?>

<script>
    (function ($) {
        var $dialog = $( ".ui-dialog-content" );
        var $helpButton = $('<button class="btn-help"><svg><use xlink:href="#icon-dialog-help" /></svg></button>');
        $helpButton.insertBefore($dialog.parent().find('.ui-dialog-titlebar-close'));

        $helpButton.click(function () {
            jQuery.fn.dialog.open({
                href: "<?php echo (string)Url::to("/ccm/system/dialogs/image_carousel/create_ticket"); ?>",
                modal: true,
                width: 500,
                title: "<?php echo h(t("Support"));?>",
                height: '80%'
            });
        });
    })(jQuery);
</script>