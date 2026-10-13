{extends file="helpers/form/form.tpl"}

{block name="field"}
{if $input.type == 'file_img'}
<div class="col-lg-8">
	<div class="form-group">
		{if isset($fields_value[$input.name]) && $fields_value[$input.name] != ''}
		<div class="col-lg-12" style="margin-bottom:10px;">
			<img src="{$uri}img/{$fields_value[$input.name]}" class="img-thumbnail" style="max-width:200px;" />
		</div>
		{/if}
		<div class="col-lg-6">
			<input type="file" id="{$input.name}" name="{$input.name}" />
		</div>
	</div>
	{if isset($input.desc) && !empty($input.desc)}
	<p class="help-block">
		{$input.desc}
	</p>
	{/if}
</div>
{else}
{$smarty.block.parent}
{/if}
{/block}
