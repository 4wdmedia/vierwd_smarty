{$taskType = constant('TYPO3\\CMS\\Core\\Resource\\ProcessedFile::CONTEXT_IMAGECROPSCALEMASK')}

{foreach $files as $image}
	{$link = $image->getLink()}
	{include 'Partials/LinkData.tpl' scope=parent}

	{if $data.image_zoom && !$link}
		{$zoomLink = $image->getOriginalFile()->process($taskType, ['maxWidth' => 1200, 'maxHeight' => 1200])->getPublicUrl()}
		{if $zoomLink}
			{$isLightbox = true}
		{/if}
	{/if}

	{$crop = "{getCrop image=$image}"}

	<figure class="textmedia__figure">
		{if $link}
			<a href="{$link}"{if $isExternal} class="external-link-new-window"{/if}
				{if $isDownload} download{/if}
				{if $isLightbox} rel="lightbox"{/if}
				{if $isExternal} target="_blank" rel="noopener"{/if}>
		{/if}

		<picture>
			{$imageLG = $image->getOriginalFile()->process($taskType, ['crop' => $crop, 'maxWidth' => 1200])}
			{$imageMD = $image->getOriginalFile()->process($taskType, ['crop' => $crop, 'maxWidth' => 992])}
			{$imageSM = $image->getOriginalFile()->process($taskType, ['crop' => $crop, 'maxWidth' => 768])}
			{$imageXS = $image->getOriginalFile()->process($taskType, ['crop' => $crop, 'maxWidth' => 400])}
			<!--[if IE 9]><video style="display: none;"><![endif]-->
			<source srcset="{$imageLG->getPublicUrl()}" media="(min-width: 1200px)">
			<source srcset="{$imageMD->getPublicUrl()}" media="(min-width: 992px)">
			<source srcset="{$imageSM->getPublicUrl()}" media="(min-width: 592px)">
			<source srcset="{$imageXS->getPublicUrl()}">
			<!--[if IE 9]></video><![endif]-->
			<img src="{$imageMD->getPublicUrl()}" class="textmedia__image" alt="{$image->getAlternative()}"{if $image->getTitle()} title="{$image->getTitle()}"{/if}>
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