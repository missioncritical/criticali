<h2>Method Detail</h2>

{filter_objects from=$class->methods to=static_methods by=is_static}
{filter_objects from=$class->methods to=instance_methods by=is_static equals=0}

{propsort_to from=$static_methods to=methods by="is_private,is_protected,name"}
{foreach from=$methods item=method}
{include file="_methods_details_item.tpl"}
{/foreach}

{propsort_to from=$instance_methods to=methods by="is_private,is_protected,name"}
{foreach from=$methods item=method}
{include file="_methods_details_item.tpl"}
{/foreach}
