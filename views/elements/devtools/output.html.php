<div class="debug debug-info">
	<?php if (!empty($timers)): ?>
	<div class="debug-section">
		<h2>Time to Load</h2>
		<table class='table'>
			<thead>
				<tr>
					<th>Name</th>
					<th>Count</th>
					<th>Time</th>
					<th>%</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($timers as $name => $data): ?>
				<tr>
					<td class="text"><?=$data['name'] ?></td>
					<td class="number"><?=$data['count'] ?></td>
					<td class="number"><?=number_format($data['time'], 2) ?>s</td>
					<td class="number"><?=number_format($data['percentage'], 1) ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
	<?php if (!empty($queries)): ?>
	<div class="debug-section">
		<h2>Database Queries</h2>
		<table class='table'>
			<thead>
				<tr>
					<th>N</th>
					<th>Time</th>
					<th>%</th>
					<th>SQL</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($queries as $i => $data): ?>
				<tr>
					<td class="number"><?=$i+1 ?></td>
					<td class="number"><?=number_format($data['time'], 2) ?>s</td>
					<td class="number"><?=number_format($data['percentage'], 1) ?></td>
					<td class="code"><?=$data['sql'] ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
</div>
