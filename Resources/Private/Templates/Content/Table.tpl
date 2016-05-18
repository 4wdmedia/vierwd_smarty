{extends file='Layouts/HeaderContentFooter.tpl'}
{block name=content}
	{if $table}
		<table class="table">
			{if $data.table_caption}
				<caption>{$data.table_caption}</caption>
			{/if}
			{foreach $table as $row}

				{if $row@first}
					{if intval($data.table_header_position) & 1}
						<thead>
					{else}
						<tbody>
					{/if}
				{else if $row@last}
					{if $data.table_tfoot}
						</tbody>
						<tfoot>
					{/if}
				{/if}

				<tr>
					{foreach $row as $cell}
						{$cellType = td}
						{$scope = row}
						{if $row@first && intval($data.table_header_position) & 1}
							{$cellType = th}
							{$scope = col}
						{/if}
						{if $cell@first && intval($data.table_header_position) & 2}
							{$cellType = th}
						{/if}

						{if $cellType == td}
							<td>
						{else}
							<th scope="{$scope}">
						{/if}

						{if $cell}
							{$cell|escape|nl2br nofilter}
						{else}
							&nbsp;
						{/if}

						</{$cellType}>
					{/foreach}
				</tr>

				{if $row@first}
					{if intval($data.table_header_position) & 1}
						</thead>
						<tbody>
					{else if $row@last && $data.table_tfoot}
						</tfoot>
						</tbody>
					{/if}
				{/if}
			{/foreach}
		</table>
	{/if}
{/block}