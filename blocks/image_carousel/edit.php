<?php

/**
 * @project:   Bitter Theme
 *
 * @author     Fabian Bitter (fabian@bitter.de)
 * @copyright  (C) 2021 Fabian Bitter (www.bitter.de)
 * @version    X.X.X
 */

defined('C5_EXECUTE') or die('Access denied');

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\View\View;

/** @var array $breakpoints */
/** @var array $thumbnailTypes */
/** @var array $fileSets */
/** @var int $fileSetId */
/** @var int $thumbnailType */
/** @var int $speed */
/** @var int $autoplaySpeed */
/** @var int $autoplay */
/** @var int $infinite */
/** @var bool $dots */

$app = Application::getFacadeApplication();
/** @var Form $form */
$form = $app->make(Form::class);

View::element("dashboard/help_blocktypes", [], "image_carousel");
?>

<div class="alert alert-info">
    <?php /** @noinspection HtmlUnknownTarget */
    echo t("Go to %s to create a file set.", sprintf("<a href=\"%s\">%s</a>", Url::to("/dashboard/files/search"), t("File Manager"))); ?>
</div>

<div class="form-group">
    <?php echo $form->label("fileSetId", t("File Set")); ?>
    <?php echo $form->select("fileSetId", $fileSets, $fileSetId); ?>
</div>

<div class="form-group">
    <?php echo $form->label("thumbnailType", t("Thumbnail Type")); ?>
    <?php echo $form->select("thumbnailType", $thumbnailTypes, $thumbnailType); ?>
</div>

<div class="form-group">
    <?php echo $form->label("speed", t("Speed")); ?>

    <div class="input-group">
        <?php echo $form->number("speed", $speed, ["min" => 0, "max" => 10000]); ?>

        <span class="input-group-text" id="basic-addon2">
            <?php echo t("ms"); ?>
        </span>
    </div>
</div>

<div class="form-group">
    <?php echo $form->label("autoplaySpeed", t("Autoplay Speed")); ?>

    <div class="input-group">
        <?php echo $form->number("autoplaySpeed", $autoplaySpeed, ["min" => 0, "max" => 10000]); ?>

        <span class="input-group-text" id="basic-addon2">
            <?php echo t("ms"); ?>
        </span>
    </div>
</div>

<div class="checkbox">
    <label>
        <?php echo $form->checkbox("autoplay", 1, $autoplay); ?>
        <?php echo t("Autoplay"); ?>
    </label>
</div>

<div class="checkbox">
    <label>
        <?php echo $form->checkbox("infinite", 1, $infinite); ?>
        <?php echo t("Loop Infinite"); ?>
    </label>
</div>

<div class="checkbox">
    <label>
        <?php echo $form->checkbox("dots", 1, $dots); ?>
        <?php echo t("Show Dots"); ?>
    </label>
</div>

<hr>

<table class="table">
    <thead>
    <tr>
        <th>
            <?php echo t("Breakpoint"); ?>
        </th>

        <th>
            <?php echo t("Slides to Show"); ?>
        </th>

        <th>
            <?php echo t("Slides to Scroll"); ?>
        </th>
    </tr>
    </thead>

    <tbody>
    <?php $rowCounter = 0; ?>

    <?php foreach ($breakpoints as $breakpoint): ?>
        <?php $rowCounter++; ?>

        <tr>
            <td>
                <?php echo $form->number("breakpoints[" . $rowCounter . "][breakpoint]", $breakpoint["breakpoint"], ["min" => 0, "max" => 5120]); ?>
            </td>

            <td>
                <?php echo $form->number("breakpoints[" . $rowCounter . "][slidesToShow]", $breakpoint["slidesToShow"], ["min" => 1, "max" => 12]); ?>
            </td>

            <td>
                <?php echo $form->number("breakpoints[" . $rowCounter . "][slidesToScroll]", $breakpoint["slidesToScroll"], ["min" => 1, "max" => 12]); ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
