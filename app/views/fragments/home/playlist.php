<div class="playlist-element">
	<?php if (!is_null($headerRowData)): ?>
	<div class="button-row clearfix">
		<div class="buttons">
			<div class="item">
				<button class="btn btn-default btn-xs" type="button">View All Series</button>
			</div>
			<?php if (!is_null($headerRowData['navButtons'])):?>
			<div class="item">
				<?php if (!is_null($headerRowData['navButtons']['previousItemUri'])): ?>
				<a href="<?=e($headerRowData['navButtons']['previousItemUri']);?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-fast-backward"></span></a>
				<?php else: ?>
				<button disabled type="button" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-fast-backward"></span></button>
				<?php endif; ?>
			</div>
			<div class="item">
				<?php if (!is_null($headerRowData['navButtons']['nextItemUri'])): ?>
				<a href="<?=e($headerRowData['navButtons']['nextItemUri']);?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-fast-forward"></span></a>
				<?php else: ?>
				<button disabled type="button" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-fast-forward"></span></button>
			<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
		<h2 class="playlist-title"><?=e($headerRowData['title']);?></h2>
	</div>
	<?php endif; ?>
	<div class="playlist-table-container">
		<table class="playlist-table table table-bordered table-striped table-hover">
			<tbody>
				<?php foreach($tableData as $row):?>
				<tr class="<?=$row['active'] ? "chosen" : ""?>">
					<td class="col-episode-no"><?=e($row['episodeNo'])?>.</td>
					<td class="col-thumbnail"><a href="<?=e($row['uri']);?>"><img class="img-responsive" src="<?=e($row['thumbnailUri']);?>"/></a></td>
					<td class="col-title clearfix">
						<?php if (!is_null($row['playlistName'])): ?>
						<div class="subtitle"><span class="label label-info"><?=e($row['playlistName']);?></div></div>
						<?php endif; ?>
						<div class="title"><?=e($row['title']);?></div>
						<?php if (!is_null($row['escapedDescription'])): ?>
						<div class="description"><?=$row['escapedDescription'];?></div>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
		</table>
	</div>
</div>