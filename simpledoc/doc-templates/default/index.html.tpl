<h1>Class Listing</h1>

{foreach from=$packages item=package}
  <h2>{$package->name|escape}</h2>

  <ul>
  {foreach from=$package->classes item=class}
    <li><a href="{$package->name|file_safe|escape}/{$class->name|file_safe|escape}.html">{$class->name|escape}</a></li>
  {/foreach}
  </ul>
  
{/foreach}
