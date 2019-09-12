{extends file='Layouts/HeaderContentFooter.tpl'}
{block name=content}
	{if $files}
		{$taskType = \TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGECROPSCALEMASK}
		<ul class="ce-uploads">
			{foreach $files as $file}
				<li>
					{if $data.uploads_type == 1}
						<img src="{uri_resource path="Icons/FileIcons/{$file->getExtension()}.gif" extensionName='frontend'}" alt="">
					{/if}

					{if $data.uploads_type == 2}
						{* thumbnail *}
						{if $file->getOriginalFile() && $file->getType() == \TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_IMAGE}
							<a href="{$file->getPublicUrl()}">
								{$scaledImage = $file->getOriginalFile()->process($taskType, ['maxWidth' => 150])}
								<img src="{$scaledImage->getPublicUrl()}" alt="{$file->getAlternative()}">
							</a>
						{else}
							<img src="{uri_resource path="Icons/FileIcons/{$file->getExtension()}.gif" extensionName='frontend'}" alt="">
						{/if}
					{/if}

					<div>
						{if $file->getName()}
							<a href="{$file->getPublicUrl()}">
								<span class="ce-uploads-fileName">
									{$file->getName()}
								</span>
							</a>
						{/if}

						{if $file->getDescription() && $data.uploads_description}
							<span class="ce-uploads-description">
								{$file->getDescription()}
							</span>
						{/if}

						{if $data.filelink_size}
							<span class="ce-uploads-filesize">
								{\TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($file->getSize(), ['', ' Kb', 'Mb', 'Gb'])}
							</span>
						{/if}
					</div>
				</li>
			{/foreach}
		</ul>
	{/if}
{/block}