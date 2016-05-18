{extends file='Layouts/ContentWrap.tpl'}
{block name=content}
	{typoscript bodytext=$data.bodytext assign=bodytext}
	10 = TEXT
	10.field = bodytext
	10.parseFunc < lib.parseFunc_RTE
	{/typoscript}

	{if $data.CType == text || !count($files)}
		{* only render the text *}
		<div class="textmedia__text">
			{include 'Partials/Header.tpl'}

			{$bodytext nofilter}
		</div>
	{else}
		{include 'Partials/Header.tpl'}

		<div class="row">
			<div class="col col-xs-12 col-md-6">
				{include 'Partials/Image.tpl'}
			</div>
			<div class="col col-xs-12 col-lg-6">
				<div class="textmedia__text">
					{$bodytext nofilter}
				</div>
			</div>
		</div>
	{/if}
{/block}