<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'email');

$variantParent = $email->getVariantParent();
$subheader = ($variantParent) ? '<span class="small"> - ' . $view['translator']->trans('mautic.email.header.editvariant', array(
    '%name%' => $email->getSubject(),
    '%parent%' => $variantParent->getSubject()
)) . '</span>' : '';

$header = ($email->getId()) ?
    $view['translator']->trans('mautic.email.header.edit',
        array('%name%' => $email->getSubject())) :
    $view['translator']->trans('mautic.email.header.new');

$view['slots']->set("headerTitle", $header.$subheader);
?>
    <!-- start: box layout -->
<?php echo $view['form']->start($form); ?>
    <div class="box-layout">
        <!-- container -->
        <div class="col-md-9 bg-auto height-auto">
            <div class="pa-md">
                <div class="row">
                    <div class="col-md-6">
                        <?php echo $view['form']->row($form['subject']); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo $view['form']->row($form['contentMode']); ?>
                    </div>
                </div>
                <div id="customHtmlContainer">
                    <?php echo $view['form']->row($form['customHtml']); ?>
                </div>
                <?php echo $view['form']->row($form['plainText']); ?>
            </div>
        </div>
        <div class="col-md-3 bg-white height-auto bdr-l">
            <div class="pr-lg pl-lg pt-md pb-md">
                <?php if (isset($form['variantSettings'])): ?>
                    <?php echo $view['form']->row($form['variantSettings']); ?>
                    <?php echo $view['form']->row($form['template']); ?>
                    <?php echo $view['form']->row($form['isPublished']); ?>
                    <?php echo $view['form']->row($form['publishUp']); ?>
                    <?php echo $view['form']->row($form['publishDown']); ?>
                <?php endif; ?>
                <?php echo $view['form']->rest($form); ?>
            </div>
        </div>
    </div>
<?php echo $view['form']->end($form); ?>

<div class="hide builder email-builder">
    <div class="builder-content">
        <input type="hidden" id="EmailBuilderUrl" value="<?php echo $view['router']->generate('mautic_email_action', array('objectAction' => 'builder', 'objectId' => $email->getSessionId())); ?>" />
    </div>
    <div class="builder-panel">
        <p>
            <button type="button" class="btn btn-primary btn-close-builder" onclick="Mautic.closeEmailEditor();"><?php echo $view['translator']->trans('mautic.email.builder.close'); ?></button>
        </p>
        <div class="well well-sm margin-md-top"><em><?php echo $view['translator']->trans('mautic.email.token.help'); ?></em></div>
        <div class="panel-group margin-sm-top" id="pageTokensPanel">
            <?php foreach ($tokens as $k => $t): ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?php echo $t['header']; ?></h4>
                    </div>
                    <div class="panel-body">
                        <?php echo $t['content']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>