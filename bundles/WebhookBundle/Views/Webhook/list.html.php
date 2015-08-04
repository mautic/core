<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticWebhookBundle:Webhook:index.html.php');
?>

<?php if (count($items)): ?>
    <div class="table-responsive panel-collapse pull out page-list">
        <table class="table table-hover table-striped table-bordered webhook-list" id="webhookTable">
            <thead>
                <tr>
                    <?php
                        echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                            'checkall' => 'true',
                            'target'   => '#webhookTable'
                        ));

                        echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                            'sessionVar' => 'webhook',
                            'orderBy'    => 'e.title',
                            'text'       => 'mautic.core.name',
                            'class'      => 'col-webhook-name',
                            'default'    => true
                        ));

                        echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                            'sessionVar' => 'webhook',
                            'orderBy'    => 'e.webhookUrl',
                            'text'       => 'mautic.webhook.webhook_url',
                            'class'      => 'col-webhook-id visible-md visible-lg'
                        ));

                        echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                            'sessionVar' => 'webhook',
                            'orderBy'    => 'e.id',
                            'text'       => 'mautic.core.id',
                            'class'      => 'col-webhook-id visible-md visible-lg'
                        ));
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php /** @var \Mautic\WebhookBundle\Entity\Webhook $item */ ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php
                                echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', array(
                                    'item'      => $item,
                                    'templateButtons' => array(
                                        'edit'      => $security->hasEntityAccess($permissions['webhook:webhooks:editown'], $permissions['webhook:webhooks:editother'], $item->getCreatedBy()),
                                        'clone'     => $permissions['webhook:webhooks:create'],
                                        'delete'    => $security->hasEntityAccess($permissions['webhook:webhooks:deleteown'], $permissions['webhook:webhooks:deleteother'], $item->getCreatedBy()),
                                    ),
                                    'routeBase'  => 'webhook',
                                    'langVar'    => 'webhook.webhook'
                                ));
                            ?>
                        </td>
                        <td>
                            <div>
                                <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php',array('item'=> $item, 'model' => 'webhook.webhook')); ?>
                                <a href="<?php echo $view['router']->generate('mautic_webhook_action', array("objectAction" => "view", "objectId" => $item->getId())); ?>"" data-toggle="ajax">
                                    <?php echo $item->getTitle(); ?>
                                </a>
                                <?php if ($description = $item->getDescription()): ?>
                                    <div class="text-muted mt-4"><small><?php echo $description; ?></small></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="visible-md visible-lg"><?php echo $item->getWebhookUrl(); ?></td>
                        <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
          </table>
    </div>

    <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => count($items),
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_page_index',
            "baseUrl"         => $view['router']->generate('mautic_webhook_index'),
            'sessionVar'      => 'page'
        )); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
