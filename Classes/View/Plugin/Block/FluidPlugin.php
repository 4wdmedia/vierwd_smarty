<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Block;

use Smarty_Internal_Template;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Fluid\View\StandaloneView;

class FluidPlugin {

	private ControllerContext $controllerContext;
	private ConfigurationManagerInterface $configurationManager;

	public function __construct(ControllerContext $controllerContext, ConfigurationManagerInterface $configurationManager) {
		$this->controllerContext = $controllerContext;
		$this->configurationManager = $configurationManager;
	}

	public function __invoke(array $params, ?string $content, Smarty_Internal_Template $smarty, bool &$repeat): string {
		if (!isset($content)) {
			return '';
		}

		$data = isset($params['data']) ? $params['data'] : [];
		unset($params['data']);
		$data = $params + $data + $smarty->getTemplateVars();

		$fluidView = GeneralUtility::makeInstance(StandaloneView::class);
		$fluidView->setControllerContext($this->controllerContext);
		$fluidView->assignMultiple($data);
		$fluidView->setTemplateSource($content);

		assert($this->configurationManager instanceof ConfigurationManagerInterface);
		$configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (isset($configuration['view'])) {
			$layoutRootPaths = [];
			if (isset($configuration['view']['layoutRootPaths'])) {
				$layoutRootPaths = $configuration['view']['layoutRootPaths'];
			} else if (isset($configuration['view']['layoutRootPath'])) {
				$layoutRootPaths = [$configuration['view']['layoutRootPath']];
			}
			$layoutRootPaths = array_map(function($path) {
				return GeneralUtility::getFileAbsFileName($path);
			}, $layoutRootPaths);
			$fluidView->setLayoutRootPaths($layoutRootPaths);

			// Partials
			$partialRootPaths = [];
			if (isset($configuration['view']['partialRootPaths'])) {
				$partialRootPaths = $configuration['view']['partialRootPaths'];
			} else if (isset($configuration['view']['partialRootPath'])) {
				$partialRootPaths = [$configuration['view']['partialRootPath']];
			}
			$partialRootPaths = array_map(function($path) {
				return GeneralUtility::getFileAbsFileName($path);
			}, $partialRootPaths);
			$fluidView->setPartialRootPaths($partialRootPaths);
		}

		return $fluidView->render();
	}

}
