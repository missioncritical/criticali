<div class="top-nav">
  <ul class="quicklinks">
    <li><a href="#constants">constants</a></li>
    <li><a href="#properties">properties</a></li>
    <li><a href="#methods">methods</a></li>
  </ul>
  <div class="type">
    {if $class->is_final}final{/if}
    {if $class->is_abstract}abstract{/if}
    class
  </div>
  <h1>{$class->name|escape}</h1>
</div>

<div class="class_properties">
{if $class->parent_class_name || $class->interface_names}
  <dl>
    {if $class->parent_class_name}
    <dt>Inheritance</dt>
    <dd>{class_link class=$class->parent_class_name}</dd>
    {/if}
    {if $class->interface_names}
    <dt>Implements</dt>
    <dd>
      {sort_to from=$class->interface_names to=interface_names}
      {foreach from=$interface_names item=iface key=idx}
        {if $idx > 0},{/if}
        {class_link class=$iface}
      {/foreach}
    </dd>
    {/if}
  </dl>
{/if}
</div>

<div class="description">
{if $class->comment}
{$class->comment->html()}
{/if}
</div>

{if $class->constants}{include file="_constants.tpl"}{/if}

{if $class->properties}{include file="_properties.tpl"}{/if}

{if $class->methods}
{include file="_methods.tpl"}
{include file="_method_details.tpl"}
{/if}
