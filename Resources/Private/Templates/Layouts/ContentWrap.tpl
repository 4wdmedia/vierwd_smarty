{if $data.wrapClasses}
	<div class="{implode(' ', $data.wrapClasses)}">
{/if}
{if $data.innerWrap}
	<div class="{implode(' ', $data.innerWrap)}">
{/if}
	{block name=content}{/block}
{if $data.innerWrap}
	</div>
{/if}
{if $data.wrapClasses}</div>{/if}
