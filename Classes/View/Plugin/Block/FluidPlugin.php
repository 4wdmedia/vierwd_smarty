<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Block;

use Psr\Http\Message\ServerRequestInterface;
use Smarty_Internal_Template;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

use function Safe\file_put_contents;

class FluidPlugin {

	private RenderingContextInterface $renderingContext;
	private ConfigurationManagerInterface $configurationManager;
	private ViewFactoryInterface $viewFactory;

	public function __construct(RenderingContextInterface $renderingContext, ConfigurationManagerInterface $configurationManager, ViewFactoryInterface $viewFactory) {
		$this->renderingContext = $renderingContext;
		$this->configurationManager = $configurationManager;
		$this->viewFactory = $viewFactory;
	}

	public function __invoke(array $params, ?string $content, Smarty_Internal_Template $smarty, bool &$repeat): string {
		if (!isset($content)) {
			return '';
		}

		$data = isset($params['data']) ? $params['data'] : [];
		unset($params['data']);
		$data = $params + $data + $smarty->getTemplateVars();

		assert($this->configurationManager instanceof ConfigurationManagerInterface);
		$configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$layoutRootPaths = [];
		$partialRootPaths = [];
		if (isset($configuration['view'])) {
			if (isset($configuration['view']['layoutRootPaths'])) {
				$layoutRootPaths = $configuration['view']['layoutRootPaths'];
			} else if (isset($configuration['view']['layoutRootPath'])) {
				$layoutRootPaths = [$configuration['view']['layoutRootPath']];
			}
			$layoutRootPaths = array_map(function($path) {
				return GeneralUtility::getFileAbsFileName($path);
			}, $layoutRootPaths);

			// Partials
			if (isset($configuration['view']['partialRootPaths'])) {
				$partialRootPaths = $configuration['view']['partialRootPaths'];
			} else if (isset($configuration['view']['partialRootPath'])) {
				$partialRootPaths = [$configuration['view']['partialRootPath']];
			}
			$partialRootPaths = array_map(function($path) {
				return GeneralUtility::getFileAbsFileName($path);
			}, $partialRootPaths);
		}

		$cacheDirectory = Environment::getVarPath() . '/cache/vierwd_smarty/fluid-in-smarty/';
		GeneralUtility::mkdir_deep($cacheDirectory);
		$cacheFile = $cacheDirectory . '/' . md5($content) . '.html';
		if (!file_exists($cacheFile)) {
			file_put_contents($cacheFile, $content);
		}

		$request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
		$viewFactoryData = new ViewFactoryData(
			// templateRootPaths: ['EXT:backend/Resources/Private/Templates'],
			partialRootPaths: $partialRootPaths,
			layoutRootPaths: $layoutRootPaths,
			request: $request,
			templatePathAndFilename: $cacheFile,
		);
		$fluidView = $this->viewFactory->create($viewFactoryData);
		$fluidView->assignMultiple($data);

		return $fluidView->render();
	}

}
