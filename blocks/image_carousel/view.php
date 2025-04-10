<?php

/**
 * @project:   Bitter Theme
 *
 * @author     Fabian Bitter (fabian@bitter.de)
 * @copyright  (C) 2021 Fabian Bitter (www.bitter.de)
 * @version    X.X.X
 */

defined('C5_EXECUTE') or die('Access denied');

use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Utility\Service\Identifier;

/** @var array $images */

$app = Application::getFacadeApplication();
/** @var Identifier $identifier */
$identifier = $app->make(Identifier::class);

$c = Page::getCurrentPage();
$uniqueIdentifier = "ccm-image-carousel-" . $identifier->getString();
?>

<?php if ($c instanceof Page && $c->isEditMode()): ?>
    <div class="ccm-edit-mode-disabled-item">
        <?php echo t('Image Carousel is disabled in edit mode.') ?>
    </div>
<?php else: ?>
    <div id="<?php echo $uniqueIdentifier; ?>" class="image-carousel hidden">
        <?php foreach ($images as $image): ?>
            <div>
                <img src="<?php echo $image["url"]; ?>" alt="<?php echo h($image["title"]); ?>"/>
            </div>
        <?php endforeach; ?>
    </div>

    <!--suppress JSUnresolvedVariable -->
    <script type="text/javascript">
        (function ($) {
            $(document).ready(function () {
                $('#<?php echo $uniqueIdentifier; ?>').on('init', function (event, slick) {
                    slick.$slider.removeClass("hidden");
                }).slick(<?php echo json_encode($config); ?>);
            });
        })(jQuery);
    </script>
<?php endif; ?>