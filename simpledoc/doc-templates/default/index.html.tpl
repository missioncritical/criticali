<h1>{$title}</h1>

{if $index_guide}
  {$index_guide->html()}
{else}

  <h2>Packages</h2>
  
  {foreach from=$packages item=package}
    <h3>{$package->name|escape}</h3>

    <h4>Classes</h4>

    <ul>
    {foreach from=$package->classes item=class}
      <li><a href="{$package->name|file_safe|escape}/{$class->name|file_safe|escape}.html">{$class->name|escape}</a></li>
    {/foreach}
    </ul>
  
  {/foreach}
{/if}

{add_to_block block=nav}{navlist}{/add_to_block}