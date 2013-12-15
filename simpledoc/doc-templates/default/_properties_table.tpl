<table class="members properties {$type}">
  <thead>
    <tr>
      <th class="namehead">Name</th>
      <th>Visibility</th>
      <th>R/W</th>
      <th>Type</th>
      <th>Description</th>
    </tr>
  </thead>
  <tbody>
  {foreach from=$properties item=property}
    <tr class="{$property->visibility()}{if $property->is_synthetic} synthetic{/if}">
      <td class="name">{$property->name|escape}</td>
      <td class="visiblity">{$property->visibility()}</td>
      <td class="rw">{$property->rw}</td>
      <td class="description">{if $property->comment}{$property->comment->html()}{else}&nbsp;{/if}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
