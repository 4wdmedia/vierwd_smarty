{extends file='Layouts/ContentWrap.tpl'}
{block name=content}
	{include 'Partials/Header.tpl'}
	{$smarty.block.child}
	{*include 'Partials/Footer.tpl'*}
{/block}