{if $data.header_layout != 100 && $data.header}
	{$header_layout = $data.header_layout|default:$settings.defaultHeaderType}
	{if $data.subheader || $data.date}
	<header>
	{/if}
		<h{$header_layout}>{$data.header}</h{$header_layout}>

		{if $data.subheader}
		<h{$header_layout + 1}>{$data.subheader}</h{$header_layout + 1}>
		{/if}

		{if $data.date}
			<p>
				<time datetime="{$data.date|date_format:'%Y-%m-%d'}">
					{$data.date|date_format:'%B %e, %Y'}
				</time>
			</p>
		{/if}
	{if $data.subheader || $data.date}
	</header>
	{/if}
{/if}