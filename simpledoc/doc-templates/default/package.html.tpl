<h1>{$package->name|escape}</h1>

{if $index_guide}
  {$index_guide->html()}
{else}

  <h2>Classes</h2>
  

  <ul>
  {foreach from=$package->classes item=class}
    <li>{class_link class=$class}</li>
  {/foreach}
  </ul>
  
{/if}

{add_to_block block=nav}{navlist selected=$package}{/add_to_block}