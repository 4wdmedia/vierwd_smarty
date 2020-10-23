{*
	Render RTE-Text and replace %MORE% and %VIDEO% placeholders.
	Input: $bodytext
	Prints the rendered bodytext
*}
{$bodytext = $cObj->parseFunc($bodytext, [], '< lib.parseFunc_RTE')}

{$readMore = 'Read more'}
{if $siteLanguage && $siteLanguage->getTwoLetterIsoCode() === 'de'}
	{$readMore = 'Weiterlesen'}
{/if}
{$replacement = "<div class=\"textmedia__more textmedia__more--hidden\">$3</div><p class=\"textmedia__more-link\"><a class=\"button\" href=\"#\">{$readMore}</a></p>"}
{$bodytext = preg_replace('/(<p>)?\s*%MORE%\s*(<\/p>)(.*)$/is', $replacement, $bodytext)}

{$bodytext = preg_replace('/(?:<p>)?%VIDEO%(?<videoID>[^%]*)%(<?:\/p>)?/', '<div class="video"><iframe src="https://www.youtube-nocookie.com/embed/$1?rel=0&color=white" frameborder="0" allowfullscreen></iframe></div>', $bodytext)}

{$bodytext nofilter}
