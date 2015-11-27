{capture name=content}
	{render section="content"}
	{render partial="Footer" arguments=$smarty.template_object->getTemplateVars()}
{/capture}
{layout name="ContentWrap"}
