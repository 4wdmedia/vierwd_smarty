{extends file='Layouts/HeaderContentFooter.tpl'}
{block name=content}
	{if $menu}
		<ul>
			{foreach $menu as $page}
				<li>
					<a href="{$page.link}"{if $page.target} rel="noopener" target="{$page.target}"{/if}>
						{$page.title}
					</a>
					{if $page.data.abstract}
						{$cObj->parseFunc($page.data.abstract, [], '< lib.parseFunc_RTE') nofilter}
					{/if}
				</li>
			{/foreach}
		</ul>
	{/if}
{/block}
