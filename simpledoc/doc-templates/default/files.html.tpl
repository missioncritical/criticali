<h1>Files</h1>

{if $files}
<ul>
  {foreach from=$files item=file}
  <li>
    <strong>{$file->filename|escape}</strong>
    {assign var=class_names value=$file->class_names}
    {if $class_names}
    <br/><br/>classes declared:
    <ul>
      {foreach from=$class_names item=name}
      <li>{$name|escape}</li>
      {/foreach}
    </ul>
    <br/>
    {/if}
  </li>
  {/foreach}
</ul>
{else}
<p>No files Found</p>
{/if}
