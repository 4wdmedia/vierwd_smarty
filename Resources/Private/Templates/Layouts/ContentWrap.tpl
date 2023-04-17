{if !empty($data.wrapClasses)}
	<div class="{implode(' ', $data.wrapClasses)}">
{/if}
{if !empty($data.innerWrap)}
	<div class="{implode(' ', $data.innerWrap)}">
{/if}
	{block name=content}{/block}
{if !empty($data.innerWrap)}
	</div>
{/if}
{if !empty($data.wrapClasses)}</div>{/if}
