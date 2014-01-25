<?php
require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Debug\ExceptionHandler;

// Handle exceptions nicely
ExceptionHandler::register(false);

$app = new Silex\Application();
//$app['debug'] = true;
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());

$app['session']->start();

// Setup translations
$app['app_default_locale'] = 'en';
$app['app_allowed_locales'] = array('en','cy');

$locale = function() use ($app) {
    $path = $_SERVER['REQUEST_URI'];

    $request_tokens = explode('/', $path);
    $locale = in_array($request_tokens[1], $app['app_allowed_locales']) ? $request_tokens[1] : null;
    $localeFromSession = $app['session']->get('locale');

    $isRootUriWithQuery = preg_match('/\?/', $path = $_SERVER['REQUEST_URI']);

    if ($isRootUriWithQuery == 1 || $path == '/') {
        return $app->redirect('/en/', 301);
    }

    if (null == $locale) {
        $app->abort(404, 'Sorry, the page you are looking for could not be found.');
    }

    if($locale != $localeFromSession) {
        $app['session']->set('locale', $locale);
    }

    return $locale;
};

$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale' => $locale,
    'locale_fallback' => $app['app_default_locale'],
));

$app['translator'] = $app->share($app->extend('translator', function ($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());
    $translator->addResource('yaml', __DIR__ . '/translations/en.yml', 'en');
    $translator->addResource('yaml', __DIR__ . '/translations/cy.yml', 'cy');
    return $translator;
}));

// Routes
$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/views',
));

$app->get('/', function() use($app) {
    return $app->redirect('/en/', 301);
});

$app->get('/{_locale}/', function() use($app) {
    $locale = $app['session']->get('locale');
    return $app['twig']->render('index.html.twig', array ('currentLocale' => $locale));
});

$app->run();
?>
