<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Mail;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

use Vierwd\VierwdSmarty\View\SmartyView;

use Smarty;

/**
 * Send out templated HTML/plain text emails with Smarty.
 *
 * Usage in controllers:
 *
 *   $email = GeneralUtility::makeInstance(SmartyEmail::class, $this->controllerContext);
 *   $email->to('sender@example.com')
 *       ->from('john.doe@example.com')
 *       ->subject('Example subject')
 *       ->format('both')
 *       ->assign('user', $user)
 *       ->setTemplate('Mail/Registration.tpl');
 *
 * The template name will be replaced with "Registration.html.tpl" and "Registration.plain.tpl".
 */
class SmartyEmail extends Email {

	public const FORMAT_HTML = 'html';
	public const FORMAT_PLAIN = 'plain';
	public const FORMAT_BOTH = 'both';

	/** @var string[] */
	protected array $format = ['html', 'plain'];

	protected string $templateName = 'Default.tpl';

	protected SmartyView $view;

	public function __construct(ControllerContext $controllerContext, Headers $headers = null, AbstractPart $body = null) {
		parent::__construct($headers, $body);
		$this->initializeView($controllerContext);
	}

	protected function initializeView(ControllerContext $controllerContext): void {
		$this->view = GeneralUtility::makeInstance(SmartyView::class);
		$this->view->setControllerContext($controllerContext);
		$this->view->initializeView();
		assert($this->view->Smarty instanceof Smarty);

		$this->view->assignMultiple($this->getDefaultVariables());
		$this->format($GLOBALS['TYPO3_CONF_VARS']['MAIL']['format'] ?? self::FORMAT_BOTH);

		// overwrite {uri_action}
		$this->view->Smarty->unregisterPlugin('function', 'uri_action');
		$this->view->Smarty->registerPlugin('function', 'uri_action', function($params, $smarty) {
			$params['absolute'] = true;
			assert($this->view !== null);
			return $this->view->smarty_uri_action($params, $smarty);
		});

		// overwrite {typolink}
		$this->view->Smarty->unregisterPlugin('function', 'typolink');
		$this->view->Smarty->registerPlugin('function', 'typolink', function($params, $smarty) {
			$params['absolute'] = true;
			assert($this->view !== null);
			return $this->view->smarty_helper_typolink($params, $smarty);
		});
	}

	public function format(string $format): self {
		switch ($format) {
			case self::FORMAT_BOTH:
				$this->format = [self::FORMAT_HTML, self::FORMAT_PLAIN];
				break;
			case self::FORMAT_HTML:
				$this->format = [self::FORMAT_HTML];
				break;
			case self::FORMAT_PLAIN:
				$this->format = [self::FORMAT_PLAIN];
				break;
			default:
				throw new \InvalidArgumentException('Setting SmartyEmail->format() must be either "html", "plain" or "both", no other formats are currently supported', 1644931847);
		}
		return $this;
	}

	public function setTemplate(string $templateName): self {
		$this->templateName = $templateName;
		return $this;
	}

	/**
	 * @param mixed $value
	 */
	public function assign(string $key, $value): self {
		$this->view->assign($key, $value);
		return $this;
	}

	public function assignMultiple(array $values): self {
		$this->view->assignMultiple($values);
		return $this;
	}

	/*
	 * Shorthand setters
	 */
	public function setRequest(ServerRequestInterface $request): self {
		$this->view->assign('request', $request);
		if ($request->getAttribute('normalizedParams') instanceof NormalizedParams) {
			$this->view->assign('normalizedParams', $request->getAttribute('normalizedParams'));
		} else {
			$this->view->assign('normalizedParams', NormalizedParams::createFromServerParams($_SERVER));
		}
		return $this;
	}

	protected function getDefaultVariables(): array {
		return [
			'typo3' => [
				'sitename' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
				'formats' => [
					'date' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
					'time' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
				],
				'systemConfiguration' => $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'],
				'information' => GeneralUtility::makeInstance(Typo3Information::class),
			],
		];
	}

	public function ensureValidity(): void {
		$this->generateTemplatedBody();
		parent::ensureValidity();
	}

	public function getBody(): AbstractPart {
		$this->generateTemplatedBody();
		return parent::getBody();
	}

	/**
	 * @return resource|string|null
	 */
	public function getHtmlBody(bool $forceBodyGeneration = false) {
		if ($forceBodyGeneration) {
			$this->generateTemplatedBody('html');
		}
		return parent::getHtmlBody();
	}

	/**
	 * @return resource|string|null
	 */
	public function getTextBody(bool $forceBodyGeneration = false) {
		if ($forceBodyGeneration) {
			$this->generateTemplatedBody('plain');
		}
		return parent::getTextBody();
	}

	protected function generateTemplatedBody(string $forceFormat = ''): void {
		// Use a local variable to allow forcing a specific format
		$format = $forceFormat ? [$forceFormat] : $this->format;

		if (in_array(static::FORMAT_HTML, $format, true)) {
			$content = $this->renderContent('html');
			if ($content) {
				$this->html($content);
			}
		}
		if (in_array(static::FORMAT_PLAIN, $format, true)) {
			$content = $this->renderContent('plain');
			if ($content) {
				$this->text($content);
			}
		}
	}

	protected function renderContent(string $format): string {
		assert($this->view->Smarty instanceof Smarty);
		$this->view->Smarty->setTemplateDir($this->view->resolveTemplateRootPaths());

		$templateName = explode('.', $this->templateName);
		$extension = array_pop($templateName);
		$templateName[] = $format;
		$templateName[] = $extension;
		$templateName = implode('.', $templateName);
		if ($this->view->Smarty->templateExists($templateName)) {
			return $this->view->render($templateName);
		}
		return '';
	}

}
