{extends file='Layouts/HeaderContentFooter.tpl'}
{block name=content}
	{if $content}
		<ul>
			{foreach $content as $element}
				<li>
					{$link = $element.data.pid|cat:'#':$element.data.uid}
					<a href="{$link|typolink}">
						{$page.data.header}
					</a>
				</li>
			{/foreach}
		</ul>
	{/if}
{/block}
