{capture name=content}
	{if $gallery.position.noWrap != 1}
		{render partial="Header" arguments=$smarty.template_object->getTemplateVars()}
	{/if}

	<div class="ce-textpic ce-{$gallery.position.horizontal} ce-{$gallery.position.vertical}{if $gallery.position.noWrap} ce-nowrap{/if}">
		{if $gallery.position.vertical != 'below'}
			{render partial="MediaGallery" arguments=$smarty.template_object->getTemplateVars()}
		{/if}

		<div class="ce-bodytext">
			{if $gallery.position.noWrap}
				{render partial="Header" arguments=$smarty.template_object->getTemplateVars()}
			{/if}
			{typoscript bodytext=$data.bodytext}
			10 = TEXT
			10.field = bodytext
			10.parseFunc < lib.parseFunc_RTE
			{/typoscript}
		</div>

		{if $gallery.position.vertical == 'below'}
			{render partial="MediaGallery" arguments=$smarty.template_object->getTemplateVars()}
		{/if}
	</div>

	{render partial="Footer" arguments=$smarty.template_object->getTemplateVars()}
{/capture}

{layout name="ContentWrap"}