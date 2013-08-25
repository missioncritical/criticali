{capture name='content'}
{include file="layouts/_exception_message.tpl"}
{/capture}
{assign_block name='content' value=$smarty.capture.content}
{assign var="content" value="layouts/_exception_message.tpl"}
{include file="layouts/application.tpl"}