{capture name=content}
	{render partial="Header" arguments=$smarty.template_object->getTemplateVars()}
	{render partial="Footer" arguments=$smarty.template_object->getTemplateVars()}
{/capture}
{layout name="ContentWrap"}
