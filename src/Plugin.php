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
use WebImage\Node\Service\Repository;
use WebImage\Node\Service\RepositoryInterface;

class Plugin extends AbstractPlugin {
	/**
	 * @inheritdoc
	 */
	public function install(ApplicationInterface $app)
	{
		parent::install($app);
		$repository = $this->getRepository($app);
		$typeService = $repository->getNodeTypeService();
		$dictionaryService = $repository->getDictionaryService();

		foreach ($dictionaryService->getTypes() as $typeDef) {
			$type = $typeService->create($typeDef->getParent(), $typeDef->getName(), $typeDef->getPluralName(), $typeDef->getQName(), $typeDef->isExtension());
			$type->setDef($typeDef);
			$type->save();
		}
	}
	/**
	 * @inheritdoc
	 */
	public function load(ApplicationInterface $app)
	{
		$app->getServiceManager()->addServiceProvider(\WebImage\Node\Service\Db\RepositoryServiceProvider::class);
		$this->install($app);
	}

	private function getRepository(ApplicationInterface $app): RepositoryInterface
	{
		return $app->getServiceManager()->get(RepositoryInterface::class);
	}
}