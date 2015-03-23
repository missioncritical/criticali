<h1>{$pkg->name|escape} Functions</h1>

{propsort_to from=$functions to=sorted_functions by="name"}

<table class="functions">
  <thead>
    <tr>
      <th>&nbsp;</th>
      <th class="namehead">Signature</th>
    </tr>
  </thead>
  <tbody>
  {foreach from=$sorted_functions item=function}
    <tr>
      <td class="return">{$function->type|escape|default:"&nbsp;"}</td>
      <td class="name"><a href="#_{$function->name|escape}">{if $function->is_byref}&amp;{/if}{$function->name|escape}</a><span class="params">({$function->parameter_declaration()|escape})</span></td>
    </tr>
  {/foreach}
  </tbody>
</table>

{foreach from=$sorted_function item=function}
<h4 class="function_detail"><a name="_{$function->name|escape}"></a>{$function|function_signature}</h4>

{if $function->parameters}
<h5>Parameters</h5>

<table class="parameters">
  <tbody>
    {foreach from=$function->parameters item=param}
    <tr>
      <td class="name">{if $param->is_byref}&amp;{/if}${$param->name|escape}</td>
      <td class="type">{$param->type|escape|default:"&nbsp;"}</td>
      <td class="description">{$param->description|markdown|default:"&nbsp;"}</td>
    </tr>
    {/foreach}
  </tbody>
</table>

{/if}

{if $function->return_description}
<h5>Return Value</h5>

<div class="function_return_description">
  {$function->return_description|markdown}
</div>
{/if}

{if $function->comment}<h5>Description</h5>{/if}

<div class="function_description">
  {if $function->comment}{$function->comment->html()}{/if}
</div>
{/foreach}

{add_to_block block=nav}{navlist selected=$pkg->functions}{/add_to_block}
