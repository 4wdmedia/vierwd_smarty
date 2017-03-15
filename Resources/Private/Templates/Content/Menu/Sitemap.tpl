{extends file='Layouts/HeaderContentFooter.tpl'}
{block name=content}

{function name=menu menu=[]}
	{if $menu}
		<ul>
			{foreach $menu as $page}
				<li>
					<a href="{$page.link}"{if $page.target} rel="noopener" target="{$page.target}"{/if}>
						{$page.title}
					</a>
					{menu menu=$page.children}
				</li>
			{/foreach}
		</ul>
	{/if}
{/function}

<nav>
{menu menu=$menu}
</nav>

{/block}