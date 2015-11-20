{if $gallery.rows}
	<div class="ce-gallery{if $data.imageborder} ce-border{/if}" data-ce-columns="{$gallery.count.columns}" data-ce-images="{$gallery.count.files}">
		{if $gallery.position.horizontal == 'center'}
			<div class="ce-outer">
				<div class="ce-inner">
		{/if}
		{foreach $gallery.rows as $row}
			<div class="ce-row">
				{foreach $row.columns as $column}
					{if $column.media}
						<div class="ce-column">
							{if $column.media->getDescription()}
								<figure>
							{else}
								<div class="ce-media">
							{/if}

							{capture assign=media}
								{fluid}{literal}
									<f:media
										file="{column.media}"
										width="{column.dimensions.width}"
										height="{column.dimensions.height}"
										alt="{column.media.alternative}"
										title="{column.media.title}"
									/>
								{/literal}{/fluid}
							{/capture}

							{if $column.media->getType() == 2}
								{if $column.media->getLink()}
									{typoscript content=$media}
									10 = TEXT
									10.typolink.parameter = {$column.media->getLink()}
									10.field = content
									{/typoscript}
								{else if $data.image_zoom}
									{if empty($popup)}
										{$popup = $settings.media.popup}
										{$popup.enable = 1}

										{$typoScriptService = call_user_func(['TYPO3\CMS\Core\Utility\GeneralUtility', 'makeInstance'], 'TYPO3\CMS\Extbase\Service\\TypoScriptService')}
										{$popup = $typoScriptService->convertPlainArrayToTypoScriptArray($popup)}
									{/if}
									{$cObj->imageLinkWrap($media, $column.media, $popup) nofilter}
								{else}
									{$media nofilter}
								{/if}
							{/if}
							{if $column.media->getType() == 4}
								{$media nofilter}
							{/if}

							{if $column.media->getDescription()}
									<figcaption>
										{$column.media->getDescription()}
									</figcaption>
								</figure>
							{else}
								</div>
							{/if}
						</div>
					{/if}
				{/foreach}
			</div>
		{/foreach}
		{if $gallery.position.horizontal == 'center'}
				</div>
			</div>
		{/if}
	</div>
{/if}