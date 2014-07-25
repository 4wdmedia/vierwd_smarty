This plugin enables the Smarty template engine for TYPO3 plugins using extbase.
Instead of using the default fluid template engine, Smarty will be used.
To use it, just enable extend the ActionController:
... extends Tx_VierwdSmarty_Controller_ActionController

Your templates must be placed in "Resources/Private/SmartyTemplates/" of your extension.
Most of the fluid ViewHelpers are ported to Smarty functions.