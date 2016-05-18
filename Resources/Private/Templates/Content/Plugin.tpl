{extends file='Layouts/HeaderContentFooter.tpl'}
{block name=content}
	{typoscript data=$data}
	10 < tt_content.list.20.{$data.list_type}
	{/typoscript}
{/block}