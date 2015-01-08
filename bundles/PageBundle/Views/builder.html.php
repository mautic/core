<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//extend the template chosen
$view->extend(":$template:page.html.php");

$view['assets']->addScriptDeclaration("var mauticBasePath = '$basePath';");
$view['assets']->addScriptDeclaration("var mauticAjaxUrl = '" . $view['router']->generate("mautic_core_ajax") . "';");
$view['assets']->addCustomDeclaration($view['assets']->getSystemScripts(true, true));
$view['assets']->addScript('app/bundles/PageBundle/Assets/builder/builder.js');
$view['assets']->addStylesheet('app/bundles/PageBundle/Assets/builder/builder.css');
//Set the slots
foreach ($slots as $slot => $slotConfig) {

    // backward compatibility - if slotConfig array does not exist
    if (is_numeric($slot)) {
        $slot = $slotConfig;
        $slotConfig = array();
    }

    // define default config if does not exist
    if (!isset($slotConfig['type'])) {
        $slotConfig['type'] = 'html';
    }

    if (!isset($slotConfig['placeholder'])) {
        $slotConfig['placeholder'] = 'mautic.page.builder.addcontent';
    }

    if ($slotConfig['type'] == 'html' || $slotConfig['type'] == 'text') {
        $value = isset($content[$slot]) ? $content[$slot] : "";
        $view['slots']->set($slot, "<div id=\"slot-{$slot}\" class=\"mautic-editable\" contenteditable=true data-placeholder=\"{$view['translator']->trans('mautic.page.builder.addcontent')}\">{$value}</div>");
    }

    if ($slotConfig['type'] == 'slideshow') {
        if (isset($content[$slot])) {
            $options = json_decode($content[$slot], true);
        } else {
            $options = array(
                'width' => '100%',
                'height' => '250px',
                'background-color' => 'transparent',
                'show-arrows' => false,
                'show-dots' => true,
                'interval' => 5000,
                'pause' => 'hover',
                'wrap' => true,
                'keyboard' => true
            );
        }

        // Create sample slides for first time or if all slides were deleted
        if (empty($options['slides'])) {
            $options['slides'] =  array (
                array (
                    'order' => 0,
                    'background-image' => 'http://placehold.it/1900x250/4e5d9d&text=Slide+One',
                    'content' => '',
                    'captionheader' => 'Caption 1'
                ),
                array (
                    'order' => 1,
                    'background-image' => 'http://placehold.it/1900x250/4e5d9d&text=Slide+Two',
                    'content' => '',
                    'captionheader' => 'Caption 2'
                )
            );
        }

        $options['slot'] = $slot;
        $options['public'] = false;

        // create config form
        $options['configForm'] = $formFactory->createNamedBuilder(
            null,
            'slideshow_config',
            array(),
            array('data' => $options)
        )->getForm()->createView();

        // create slide config forms
        foreach ($options['slides'] as $key => &$slide) {
            $slide['key'] = $key;
            $slide['slot'] = $slot;
            $slide['form'] = $formFactory->createNamedBuilder(
                null,
                'slideshow_slide_config',
                array(),
                array('data' => $slide)
            )->getForm()->createView();
        }

        $view['slots']->set($slot, $view->render('MauticPageBundle:Page:Slots/slideshow.html.php', $options));
    }
}

//add builder toolbar
$view['slots']->start('builder');?>
<input type="hidden" id="builder_entity_id" value="<?php echo $page->getSessionId(); ?>" />
<?php
$view['slots']->stop();
?>
