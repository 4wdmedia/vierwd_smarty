{$taskType = constant('TYPO3\\CMS\\Core\\Resource\\ProcessedFile::CONTEXT_IMAGECROPSCALEMASK')}

{foreach $files as $image}
	{$link = $image->getLink()|typolink}
	{$zoomLink = ''}

	{if $data.image_zoom}
		{$zoomLink = $image->getOriginalFile()->process($taskType, ['maxWidth' => 1200, 'maxHeight' => 1200])->getPublicUrl()}
		{if $zoomLink && !$link}
			{$link = $zoomLink}
			{$zoomLink = ''}
		{/if}
	{/if}

	<figure class="textmedia__figure">
		{if $link}
			<a href="{$link}" rel="lightbox">
		{/if}

		<picture>
			{$imageLG = $image->getOriginalFile()->process($taskType, ['maxWidth' => 1200])}
			{$imageMD = $image->getOriginalFile()->process($taskType, ['maxWidth' => 992])}
			{$imageSM = $image->getOriginalFile()->process($taskType, ['maxWidth' => 768])}
			{$imageXS = $image->getOriginalFile()->process($taskType, ['maxWidth' => 400])}
			<!--[if IE 9]><video style="display: none;"><![endif]-->
			<source srcset="{$imageLG->getPublicUrl()}" media="(min-width: 1200px)">
			<source srcset="{$imageMD->getPublicUrl()}" media="(min-width: 992px)">
			<source srcset="{$imageSM->getPublicUrl()}" media="(min-width: 592px)">
			<source srcset="{$imageXS->getPublicUrl()}">
			<!--[if IE 9]></video><![endif]-->
			<img src="{$imageMD->getPublicUrl()}" alt="{$image->getAlternative()}"{if $image->getTitle()} title="{$image->getTitle()}"{/if}>
		</picture>

		{if $image->getDescription()}
			<figcaption class="textmedia__caption">
				{$image->getDescription()}
			</figcaption>
		{/if}

		{if $link}
			</a>
		{/if}
	</figure>
{/foreach}