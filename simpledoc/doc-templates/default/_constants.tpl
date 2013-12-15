<h2><a name="constants"></a>Constants</h2>

{if $class->constants}
<table class="members constants">
  <thead>
    <tr>
      <th class="namehead">Name</th>
      <th>&nbsp;</th>
      <th>Description</th>
    </tr>
  </thead>
  <tbody>
  {propsort_to from=$class->constants to=constants}
  {foreach from=$constants item=constant}
    <tr>
      <td class="name">{$constant->name|escape}</td>
      <td class="value">= {$constant->value|escape}</td>
      <td class="description">{if $constant->comment}{$constant->comment->html()}{else}&nbsp;{/if}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
{/if}
