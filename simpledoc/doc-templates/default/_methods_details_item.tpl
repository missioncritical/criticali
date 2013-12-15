<h4 class="method_detail"><a name="_{$method->name|escape}"></a>{$method|method_signature}</h4>

{if $method->parameters}
<h5>Parameters</h5>

<table class="parameters">
  <tbody>
    {foreach from=$method->parameters item=param}
    <tr>
      <td class="name">{if $param->is_byref}&amp;{/if}${$param->name|escape}</td>
      <td class="type">{$param->type|escape|default:"&nbsp;"}</td>
      <td class="description">{$param->description|markdown|default:"&nbsp;"}</td>
    </tr>
    {/foreach}
  </tbody>
</table>

{/if}

{if $method->return_description}
<h5>Return Value</h5>

<div class="method_return_description">
  {$method->return_description|markdown}
</div>
{/if}

{if $method->comment}<h5>Description</h5>{/if}

<div class="method_description">
  {if $method->comment}{$method->comment->html()}{/if}
</div>

