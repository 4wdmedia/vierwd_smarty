{extends file='Layouts/HeaderContentFooter.tpl'}
{block name=content}
	{if $menu}
		<ul>
			{foreach $menu as $page}
				<li>
					<a href="{$page.link}"{if $page.target} rel="noopener" target="{$page.target}"{/if}>
						{$page.title}
					</a>
					{if $page.content}
						<ul>
							{foreach $page.content as $element}
								<li>
									<a href="{$page.link}#c{$element.data.uid}"{if $page.target} rel="noopener" target="{$page.target}"{/if}>
										{$element.data.header}
									</a>
								</li>
							{/foreach}
						</ul>
					{/if}
				</li>
			{/foreach}
		</ul>
	{/if}
{/block}
