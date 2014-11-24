<?php

namespace Vierwd\VierwdSmarty\Controller;

class ActionController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * this is needed to use the smarty view
	 */
	protected $viewObjectNamePattern = 'Vierwd\\VierwdSmarty\\View\\SmartyView';
	protected $deprecatedViewObjectNamePattern = 'Vierwd\\VierwdSmarty\\View\\SmartyView';
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

		$view->assign('configurationManager', $this->configurationManager);

		// $view->Smarty->registerPlugin('function', 'categorylink', array($this, 'smarty_categorylink'));
	}
}
?>