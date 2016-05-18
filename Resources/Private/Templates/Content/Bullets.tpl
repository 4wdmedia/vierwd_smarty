{extends file='Layouts/HeaderContentFooter.tpl'}
{block name=content}
	{if $bullets}
		{if $data.bullets_type == 0}
			<ul class="ce-bullets">
				{foreach $bullets as $bullet}
					<li>{$bullet}</li>
				{/foreach}
			</ul>
		{else if $data.bullets_type == 1}
			<ol class="ce-bullets">
				{foreach $bullets as $bullet}
					<li>{$bullet}</li>
				{/foreach}
			</ol>
		{else if $data.bullets_type == 2}
			<dl class="ce-bullets">
				{foreach $bullets as $definitionListItem}
					{foreach $definitionListItem as $termDescription}
						{if $termDescription@first}
							<dt>{$termDescription}</dt>
						{else}
							<dd>{$termDescription}</dd>
						{/if}
					{/foreach}
				{/foreach}
			</dl>
		{/if}
	{/if}
{/block}