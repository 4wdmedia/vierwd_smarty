{extends file='Layouts/HeaderContentFooter.tpl'}
{block name=content}
	{if $data.pi_flexform_backup}
		{$data.pi_flexform = $data.pi_flexform_backup}
	{/if}
	{typoscript data=$data}
	10 < tt_content.list.20.{$data.list_type}
	{/typoscript}
{/block}