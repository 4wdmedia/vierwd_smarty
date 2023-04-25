{$data = $data + [
	'header_layout' => null,
	'header' => '',
	'subheader' => '',
	'date' => null
]}
{if (int)$data.header_layout !== 100 && $data.header}
	{$header_layout = $data.header_layout|default:1}
	<header class="header">
		{if $data.subheader}<div class="header__text">{/if}
			<h{$header_layout}>{$data.header}</h{$header_layout}>

			{if $data.subheader}
			<h{$header_layout + 1}>{$data.subheader}</h{$header_layout + 1}>
			{/if}
		{if $data.subheader}</div>{/if}

		{if $data.date}
			<p class="header__date">
				<time datetime="{$data.date|date_format:'%Y-%m-%d'}">
					{$data.date|date_format:'%d.%m.%Y'}
				</time>
			</p>
		{/if}
	</header>
{/if}