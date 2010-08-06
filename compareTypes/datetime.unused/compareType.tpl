<compareXML>
	<operand1>`{$criteria.operand1.table}__{$criteria.operand1.index}`.`{$criteria.operand1.field}`</operand1>
	<operator>{strip}
{if $criteria.field_compare_date=='smaller'}
<
{elseif $criteria.field_compare_date=='bigger'}
>
{elseif $criteria.field_compare_date=='equals'}
=
{elseif $criteria.field_compare_date=='smallerNow'}
<
{elseif $criteria.field_compare_date=='biggerNow'}
>
{/if}
	{/strip}</operator>
	<operand2>{strip}
{if $criteria.field_compare_date=='smaller'}
	{$criteria.field_compare_value_datetime|string_format:"%d"}
{elseif $criteria.field_compare_date=='bigger'}
	{$criteria.field_compare_value_datetime|string_format:"%d"}
{elseif $criteria.field_compare_date=='equals'}
	{$criteria.field_compare_value_datetime|string_format:"%d"}
{elseif $criteria.field_compare_date=='smallerNow'}
	unix_timestamp()+{$criteria.field_compare_value_dateoffset|string_format:"%d"}
{elseif $criteria.field_compare_date=='biggerNow'}
	unix_timestamp()+{$criteria.field_compare_value_dateoffset|string_format:"%d"}
{/if}
	{/strip}</operand2>
</compareXML>
