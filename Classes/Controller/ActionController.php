<?php

namespace Vierwd\VierwdSmarty\Controller;

class ActionController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $entityNotFoundMessage = 'The requested entity could not be found.';

	/**
	 * this is needed to use the smarty view
	 */
	protected $namespacesViewObjectNamePattern = 'Vierwd\\VierwdSmarty\\View\\SmartyView';

	/**
	 * initialize the view.
	 * Just call the parent. And assign the configurationManager.
	 * Afterwards you can register some custom template functions/modifiers.
	 *
	 * @see http://www.smarty.net/docs/en/api.register.plugin.tpl
	 */
	protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
		parent::initializeView($view);

		if ($view instanceof \Vierwd\VierwdSmarty\View\SmartyView) {
			$view->setContentObject($this->configurationManager->getContentObject());
		}

		// set template root paths, if available
		if (isset($this->settings['templateRootPaths'])) {
			$templateRootPaths = $this->settings['templateRootPaths'];
			krsort($templateRootPaths);
			$templateRootPaths = array_map(function($rootPath) {
				$rootPath = str_replace('//', '/', $rootPath);
				return GeneralUtility::getFileAbsFileName($rootPath);
			}, $templateRootPaths);
			$templateRootPaths = array_values(array_filter($templateRootPaths));
			$this->view->setTemplateRootPaths($templateRootPaths);
		}

		// $view->Smarty->registerPlugin('function', 'categorylink', array($this, 'smarty_categorylink'));
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response
	 * @return void
	 * @throws \Exception
	 * @override \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
	 * @see http://nerdcenter.de/extbase-fehlerbehandlung/
	 */
	public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {
		try {
			parent::processRequest($request, $response);
		} catch(PropertyException $exception) {
			// If the property mapper did throw a \TYPO3\CMS\Extbase\Property\Exception, because it was unable to find the requested entity, call the page-not-found handler.
			$previousException = $exception->getPrevious();
			if ($previousException instanceof PropertyTargetNotFoundException || $previousException instanceof PropertyInvalidSourceException) {
				$GLOBALS['TSFE']->pageNotFoundAndExit($this->entityNotFoundMessage);
			}
			throw $exception;
		}
	}
}
