<compareXML>
	<operand1>`{$criteria.operand1.table}__{$criteria.operand1.index}`.`{$criteria.operand1.field}`</operand1>
	<operator>{if $criteria.field_compare_negate}!={else}={/if}</operator>
	<operand2>{$criteria.field_compare_value_bool|string_format:"%d"}</operand2>
</compareXML>
