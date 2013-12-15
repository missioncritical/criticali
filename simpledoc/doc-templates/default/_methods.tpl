<h2><a name="methods"></a>Methods</h2>

{filter_objects from=$class->methods to=static_methods by=is_static}
{filter_objects from=$class->methods to=instance_methods by=is_static equals=0}

{if $static_methods}
<h3>Static Methods</h3>

{propsort_to from=$static_methods to=methods by="is_private,is_protected,name"}
{include file="_methods_table.tpl" type="static"}
{/if}

{if $instance_methods}
<h3>Instance Methods</h3>

{propsort_to from=$instance_methods to=methods by="is_private,is_protected,name"}
{include file="_methods_table.tpl" type="instance"}
{/if}
