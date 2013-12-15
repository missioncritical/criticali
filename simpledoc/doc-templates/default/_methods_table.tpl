<table class="members methods {$type}">
  <thead>
    <tr>
      <th class="namehead">Visibility</th>
      <th>&nbsp;</th>
      <th>Signature</th>
    </tr>
  </thead>
  <tbody>
  {foreach from=$methods item=method}
    <tr class="{$method->visibility()}{if $method->is_final} final{/if}{if $method->is_abstract} abstract{/if}{if $method->is_synthetic} synthetic{/if}">
      <td class="visiblity">{$method->visibility()}{if $method->is_final} final{/if}{if $method->is_abstract} abstract{/if}</td>
      <td class="return">{$method->type|escape|default:"&nbsp;"}</td>
      <td class="name"><a href="#_{$method->name|escape}">{if $method->is_byref}&amp;{/if}{$method->name|escape}</a><span class="params">({$method->parameter_declaration()|escape})</span></td>
    </tr>
  {/foreach}
  </tbody>
</table>
