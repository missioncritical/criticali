<h2><a name="properties"></a>Properties</h2>

{filter_objects from=$class->properties to=static_properties by=is_static}
{filter_objects from=$class->properties to=instance_properties by=is_static equals=0}

{if $static_properties}
<h3>Static Properties</h3>

{propsort_to from=$static_properties to=properties by="is_private,is_protected,name"}
{include file="_properties_table.tpl" type="static"}
{/if}

{if $instance_properties}
<h3>Instance Properties</h3>

{propsort_to from=$instance_properties to=properties by="is_private,is_protected,name"}
{include file="_properties_table.tpl" type="instance"}
{/if}
