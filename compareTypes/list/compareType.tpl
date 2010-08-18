<compareXML>
{if $criteria.filter}
	<operand1>`{$criteria.operand1.table}__{$criteria.operand1.index}`.`{$criteria.operand1.field}`</operand1>
	{assign var='criteriaKey' value="field_compare_value_`$criteria.operand1.table`_`$criteria.operand1.field`"}
	{if $criteria.$criteriaKey && !$criteria.filterValue}
		<operator>IN</operator>
		<operand2>({$criteria.$criteriaKey})</operand2>
	{else}
		<operator>=</operator>
		<operand2>{$criteria.filterValue|string_format:"%d"}</operand2>
	{/if}
{else}
	{if $criteria.field_compare_compareField}
		<operand1>`{$criteria.operand1.table}__{$criteria.operand1.index}`.`{$criteria.operand1.field}`</operand1>
		<operator>=</operator>
		<operand2>`{$criteria.operand2.table}__{$criteria.operand2.index}`.`{$criteria.operand2.field}`</operand2>
	{else}
		<operand1>`{$criteria.operand1.table}__{$criteria.operand1.index}`.`{$criteria.operand1.field}`</operand1>
		{if ($criteria.operand1.field == "pid") && ($criteria.field_compare_value_pid|regex_replace:"/[^0-9,]/":"")}
			<operator>IN</operator>
			<operand2>({$criteria.field_compare_value_pid|regex_replace:"/[^0-9,]/":""})</operand2>
		{elseif ($criteria.operand1.field == "uid") && ($criteria.field_compare_value_uid|regex_replace:"/[^0-9,]/":"")}
			<operator>IN</operator>
			<operand2>({$criteria.field_compare_value_uid|regex_replace:"/[^0-9,]/":""})</operand2>
		{else}
			{assign var='criteriaKey' value="field_compare_value_`$criteria.operand1.table`_`$criteria.operand1.field`"}
			{if $criteria.$criteriaKey}
				<operator>IN</operator>
				<operand2>({$criteria.$criteriaKey})</operand2>
			{else}
				<operator>=</operator>
				<operand2>-1</operand2>
			{/if}
		{/if}
	{/if}
{/if}
</compareXML>
