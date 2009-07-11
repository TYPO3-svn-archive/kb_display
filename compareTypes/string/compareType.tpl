<?xml version="1.0" encoding="ISO-8859-1" standalone="yes"?>
<compareXML>
	<operand1>`{$criteria.operand1.table}__{$criteria.operand1.index}`.`{$criteria.operand1.field}`</operand1>
{if $criteria.search}
	<operator>LIKE</operator>
	{if $criteria.searchCase}
		<operand2>BINARY '%{$criteria.filterValue}%'</operand2>
	{else}
		<operand2>'%{$criteria.filterValue}%'</operand2>
	{/if}
{elseif $criteria.filter}
	<operator>=</operator>
	<operand2>'{$criteria.filterValue}'</operand2>
{else}
	{if $criteria.field_compare_negate}
		<operator>!=</operator>
	{else}
		<operator>=</operator>
	{/if}
	<operand2>'{$criteria.field_compare_value_string|escape}'</operand2>
{/if}
</compareXML>
