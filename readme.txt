This plugin enables the Smarty template engine for TYPO3 plugins using extbase.
Instead of using the default fluid template engine, Smarty will be used.
To use it, just enable extend the ActionController:
class ExampleController extends \Vierwd\VierwdSmarty\Controller\ActionController

Your templates must be placed in "Resources/Private/SmartyTemplates/" of your extension.
Most of the fluid ViewHelpers are ported to Smarty functions.