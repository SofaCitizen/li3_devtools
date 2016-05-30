<div class="debug debug-info">
	<?php if (!empty($stages)): ?>
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
			<tbody class="table-striped">
				<?php foreach($stages as $name => $data): ?>
				<tr>
					<td><?=$data['name'] ?></td>
					<td><?=$data['count'] ?></td>
					<td><?=number_format($data['time'], 2) ?>s</td>
					<td><?=number_format($data['percentage'], 1) ?></td>
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
			<tbody class="table-striped">
				<?php foreach($queries as $i => $data): ?>
				<tr>
					<td><?=$i+1 ?></td>
					<td><?=number_format($data['time'], 2) ?>s</td>
					<td><?=number_format($data['percentage'], 1) ?></td>
					<td><?=$data['sql'] ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
</div>
