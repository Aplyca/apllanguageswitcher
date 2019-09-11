{def $language_data = $current_node_id
}
{if eq($current_node_id,"")}
    {set $language_data = $requested_uri_string}
{/if}
{foreach language_switcher( $language_data ) as $siteaccessName => $lang}
		<a href="{$lang.url}" {if $lang.currentsiteaccess}class="on"{/if} style="cursor: pointer;" title={$lang.text|i18n('cid/content', ,)}>{$lang.text|i18n('cid/content', ,)}</a>
{/foreach}