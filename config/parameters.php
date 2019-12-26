<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$root = $container->getParameter('kernel.root_dir');
include __DIR__.'/paths_helper.php';

//load default parameters from bundle files
$core    = $container->getParameter('mautic.bundles');
$plugins = $container->getParameter('mautic.plugin.bundles');

$bundles = array_merge($core, $plugins);
unset($core, $plugins);

$mauticParams = [];

foreach ($bundles as $bundle) {
    if (!empty($bundle['config']['parameters'])) {
        $mauticParams = array_merge($mauticParams, $bundle['config']['parameters']);
    }
}

// Find available translations
$locales = [];

$extractLocales = function ($dir) use (&$locales) {
    $locale = $dir->getFilename();

    // Check config
    $configFile = $dir->getRealpath().'/config.json';
    if (file_exists($configFile)) {
        $config           = json_decode(file_get_contents($configFile), true);
        $locales[$locale] = (!empty($config['name'])) ? $config['name'] : $locale;
    }
};

$defaultLocalesDir = new \Symfony\Component\Finder\Finder();
$defaultLocalesDir->directories()->in($root.'/bundles/CoreBundle/Translations')->ignoreDotFiles(true)->depth('== 0');
foreach ($defaultLocalesDir as $dir) {
    $extractLocales($dir);
}

$installedLocales = new \Symfony\Component\Finder\Finder();
$installedLocales->directories()->in($root.'/../'.$paths['translations'])->ignoreDotFiles(true)->depth('== 0');

foreach ($installedLocales as $dir) {
    $extractLocales($dir);
}
unset($defaultLocalesDir, $installedLocales, $extractLocales);

$mauticParams['supported_languages'] = $locales;

// Set the paths
$mauticParams['paths'] = $paths;

foreach ($mauticParams as $k => &$v) {
    switch (true) {
        case is_bool($v):
            $type = 'bool:';
            break;
        case is_int($v):
            $type = 'int:';
            break;
        case is_array($v):
            $type = 'json:';
            break;
        case is_float($v):
            $type = 'float:';
            break;
        default:
            $type = 'nullable:';
    }

    // Add to the container
    $container->setParameter("mautic.{$k}", sprintf('%%env(%sMAUTIC_%s)%%', $type, mb_strtoupper($k)));
}

// Store default parameters into the importer
$parameterImporter = new MauticParameterImporter($paths['local_config'], $paths, $mauticParams);

// Set the router URI for CLI
if ($parameterImporter->has('site_url')) {
    $parts = parse_url($parameterImporter->get('site_url'));

    if (!empty($parts['host'])) {
        $scheme           = (!empty($parts['scheme']) ? $parts['scheme'] : 'http');
        $portContainerKey = ('http' === $scheme) ? 'request_listener.http_port' : 'request_listener.https_port';

        $container->setParameter('router.request_context.host', '%env(MAUTIC_REQUEST_CONTEXT_HOST)%');
        $container->setParameter('router.request_context.scheme', '%env(MAUTIC_REQUEST_CONTEXT_SCHEME)%');
        $container->setParameter('router.request_context.base_url', '%env(MAUTIC_REQUEST_CONTEXT_BASE_URL)%');

        if (!empty($parts['port'])) {
            $container->setParameter($portContainerKey, '%env(MAUTIC_REQUEST_CONTEXT_PORT)%');
        }
    }
}

unset($mauticParams, $replaceRootPlaceholder, $bundles);
