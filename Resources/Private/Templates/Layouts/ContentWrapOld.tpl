{extends file='Layouts/ContentWrap.tpl'}
{block name=content}
	{$cObj->getCurrentVal() nofilter}
{/block}