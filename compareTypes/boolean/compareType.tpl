<compareXML>
{if $criteria.filter}
	<operand1>`{$criteria.operand1.table}__{$criteria.operand1.index}`.`{$criteria.operand1.field}`</operand1>
	<operator>{if $criteria.field_compare_negate}!={else}={/if}</operator>
	<operand2>{if ($criteria.filterValue=="Y")}1{elseif ($criteria.filterValue=="N")}0{else}{$criteria.filterValue|string_format:"%d"}{/if}</operand2>
{else}
	<operand1>`{$criteria.operand1.table}__{$criteria.operand1.index}`.`{$criteria.operand1.field}`</operand1>
	<operator>{if $criteria.field_compare_negate}!={else}={/if}</operator>
	<operand2>{$criteria.field_compare_value_bool|string_format:"%d"}</operand2>
{/if}
</compareXML>
