# TYPO3 Smarty Extension [![Build Status](https://travis-ci.com/4wdmedia/vierwd_smarty.svg?branch=master)](https://travis-ci.com/4wdmedia/vierwd_smarty)

> Use [Smarty](http://www.smarty.net/) in your templates and extbase extensions.

## Installation

Install using [composer](https://getcomposer.org/):
```
composer require 'vierwd/typo3-smarty'
```

### Usage in controllers

To use smarty templates for your extension's actions, just extend the `Vierwd\VierwdSmarty\Controller\ActionController`. Your templates need to be at the same location as your Fluid templates used to be, but with the file extension `.tpl`.

#### Example

```php
// Classes/Controller/BlogController.php
namespace Example\ExampleBlog\Controller;

class BlogController extends \Vierwd\VierwdSmarty\Controller\ActionController {
	/**
	 * @var \Example\ExampleBlog\Domain\Repository\PostRepository
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $postRepository;

	public function listAction() {
		$posts = $this->postRepository->findAll();
		$this->view->assign('posts', $posts);
	}
}
```

```Smarty
{* Resources/Private/Templates/Blog/List.tpl *}

{foreach $posts as $post}
	<div class="post">
		<h1>{$post->getTitle()}</h1>

		{$post->getContent()|escape|nl2p nofilter}
	</div>
{/foreach}
```

### Pre-defined variables

There are some variables, that are always available to your templates:

Variable Name | Contents
--------------|---------
cObj | The current ContentObject (instance of `TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer`)
extensionPath | The path to your extension (`typo3conf/ext/example_blog/`)
extensionName | `ExampleBlog`
pluginName | `Pi1` (or whatever you defined in ext_tables.php)
controllerName | `Blog`
actionName | `list`
context | The controllerContext (instance of `TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext`)
request | The current request (instance of `TYPO3\CMS\Extbase\MvcRequest`)
formPrefix | Prefix form fields need as name to automatically map form fields to arguments
settings | Flexform settings for the plugin
frameworkSettings | TypoScript settings for `plugin.tx_exampleblog`
typolinkService | An instance of `TYPO3\CMS\Frontend\Service\TypoLinkCodecService`
TSFE | `$TSFE` (instance of `TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController`)

### Pre-defined smarty functions, blocks and modifiers

- translate
- uri_resource
- uri_action
- link_action
- flashMessages
- nl2p
- email
- typolink
- typoscript
- fluid
- svg

### Power-Blocks: typoscript and fluid

What's realy good about this extension is, that you can still use typoscript and fluid within your Smarty templates. That way you can ensure, that an element gets exactly the same HTML output as a normal content element like Text-with-images. If you write a form, it's also good to fallback to Fluid for some ViewHelpers.

#### Typoscript
```smarty
{capture assign=text}
<p>Lorem <b>ipsum</b> dolor sit amet, consectetur adipisicing elit. Dolorem, earum est reiciendis modi neque in veniam rerum deleniti et praesentium? Numquam, odit, itaque voluptate pariatur adipisci enim tempora ducimus dolor!</p>
{/capture}

{typoscript header='TypoScript Example' bodytext=$text CType=text}
lib.parseFunc_RTE >
10 < tt_content
{/typoscript}
```

##### Output (Line-breaks added)
```html
<div class="csc-default">
<header class="csc-header"><h1>TypoScript Example</h1></header>
<p>Lorem <b>ipsum</b> dolor sit amet, consectetur adipisicing elit. Dolorem, earum est reiciendis modi neque in veniam rerum deleniti et praesentium? Numquam, odit, itaque voluptate pariatur adipisci enim tempora ducimus dolor!</p>
</div>
```

The changes to the typoscript will not persist. That way you can remove `lib.parseFunc_RTE` in one TypoScript Block and still use it in another. It is also possible to use an array `data` for all arguments:
```smarty
{capture assign=text}
<p>Lorem <b>ipsum</b> dolor sit amet, consectetur adipisicing elit. Dolorem, earum est reiciendis modi neque in veniam rerum deleniti et praesentium? Numquam, odit, itaque voluptate pariatur adipisci enim tempora ducimus dolor!</p>
{/capture}

{$data=[
	CType => text,
	header => 'TypoScript Example',
	header_layout => 1,
	bodytext => $text
]}

{typoscript data=$data header_layout=2}
lib.parseFunc_RTE >
10 < tt_content
{/typoscript}
```

Notice, that parameters in the block-tag override array keys (in this example header_layout):

##### Output (Line-breaks added)
```html
<div class="csc-default">
<header class="csc-header"><h2>TypoScript Example</h2></header>
<p>Lorem <b>ipsum</b> dolor sit amet, consectetur adipisicing elit. Dolorem, earum est reiciendis modi neque in veniam rerum deleniti et praesentium? Numquam, odit, itaque voluptate pariatur adipisci enim tempora ducimus dolor!</p>
</div>
```

## Using Smarty for the base template
It is also possible to use Smarty for the base template of your website in your main TypoScript setup

```
page = PAGE
page.10 < plugin.tx_vierwdsmarty
page.10.settings {
	template = EXT:example_blog/Resources/Private/Templates/main.tpl

	typoscript.navigation < lib.navigation
	typoscript.footerNavigation < lib.footerNavigation
	typoscript.content < styles.content.get
	typoscript.logo < lib.logo
}
```

All entries in settings.typoscript will be parsed and will be available as variables in your template.
```smarty
{* example_blog/Resources/Private/Templates/main.tpl *}
<header>
	{$logo nofilter}
	{$navigation nofilter}
</header>
<div role="main">
	<!--TYPO3SEARCH_begin-->
	{$content nofilter}
	<!--TYPO3SEARCH_end-->
</div>
<footer>{$footerNavigation nofilter}</footer>
```

Note the nofilter argument for Smarty. By default all variables will be escaped to prevent some XSS attacks.

## Using Smarty for Menus

```typoscript
lib.navigation = HMENU
lib.navigation {
	entryLevel = 0

	1 = SMARTY
	1 {
		expAll = 1
		extensionName = vierwd_example
		template = Navigation/Main.tpl

		NO = 1
		ACT = 1
		IFSUB = 1
		ACTIFSUB = 1
	}

	2 < .1
	2.template = Navigation/Submenu.tpl
	3 < .2
}
```

This code block will load the templates at `typo3conf/ext/vierwd_example/Resources/Private/Templates/Navigation/` to render the navigation. Within the template you can iterate over your menu items and output the menu:

```smarty
<nav class="main-navigation">
	<ul>
	{foreach $menu as $item}
		{$hasSubmenu = $menuObject->isSubMenu($item.uid)}
		{$isActive = $menuObject->isActive($item.uid)}
		<li class="{if $isActive}active{/if}">
			<a href="{$item.uid|typolink}">
				{$item.nav_title|default:$item.title}
			</a>
			{$menuObject->submenu($item.uid) nofilter}
		</li>
	{/foreach}
	</ul>
</nav>
```