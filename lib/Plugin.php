<?php
/**
 * Class Plugin
 * @pluginName
 */
namespace WebImage\Node;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WebImage\Application\AbstractPlugin;
use WebImage\Application\ApplicationInterface;
use WebImage\Application\HttpApplication;

class Plugin extends AbstractPlugin {
	public function load(ApplicationInterface $app)
	{
		parent::load($app);

		$app->getServiceManager()->addServiceProvider(\WebImage\Node\Service\Db\RepositoryServiceProvider::class);
	}
}