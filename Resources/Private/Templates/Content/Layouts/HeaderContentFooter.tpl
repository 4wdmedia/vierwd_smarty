<div id="c{$data.uid}">
	{render partial="Header" arguments=$smarty.template_object->getTemplateVars()}
	{render section="content"}
	{render partial="Footer" arguments=$smarty.template_object->getTemplateVars()}
</div>