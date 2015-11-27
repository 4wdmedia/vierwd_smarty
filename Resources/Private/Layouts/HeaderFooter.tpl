{$captureName = uniqid('content')}
{capture name=$captureName}
	{render partial="Header" arguments=$smarty.template_object->getTemplateVars()}
	{render partial="Footer" arguments=$smarty.template_object->getTemplateVars()}
{/capture}
{layout name=ContentWrap}