<compareXML>
	<operand1>`{$criteria.operand1.table}__{$criteria.operand1.index}`.`{$criteria.operand1.field}`</operand1>
	<operator>{if $criteria.field_compare_negate}{if $criteria.field_compare_number=="bigger"}&lt;={elseif $criteria.field_compare_number=="smaller"}&gt;={elseif $criteria.field_compare_number=="equals"}!={elseif $criteria.field_compare_number=="in"} NOT IN {/if}{else}{if $criteria.field_compare_number=="bigger"}&gt;{elseif $criteria.field_compare_number=="smaller"}&lt;{elseif $criteria.field_compare_number=="equals"}={elseif $criteria.field_compare_number=="in"} IN {/if} {/if}</operator>
	<operand2>({$criteria.field_compare_value_int|string_format:"%d"})</operand2>
</compareXML>
