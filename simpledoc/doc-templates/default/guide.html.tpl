<h1>{$guide->name|escape}</h1>

{$guide->html()}

{add_to_block block=nav}{navlist selected=$guide}{/add_to_block}