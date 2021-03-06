<div class="playlist-element">
	<?php if (!is_null($headerRowData)): ?>
	<div class="button-row clearfix">
		<div class="buttons">
			<?php if (!is_null($headerRowData['seriesUri'])): ?>
			<div class="item">
				<a class="btn btn-default btn-xs" href="<?=e($headerRowData['seriesUri'])?>">View All Series</a>
			</div>
			<?php endif; ?>
			<?php if (!is_null($headerRowData['navButtons'])):?>
			<?php if (isset($headerRowData['navButtons']['showAutoPlayButton']) && $headerRowData['navButtons']['showAutoPlayButton']): ?>
			<div class="item auto-continue-btn-item"></div>
			<?php endif; ?>
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
		<table class="playlist-table table table-bordered <?=$stripedTable?"table-striped":""?> table-hover">
			<tbody>
				<?php foreach($tableData as $row):?>
				<tr class="<?=$row['active'] ? "chosen" : ""?> zoom-animation-container" data-link="<?=e($row['uri']);?>">
					<?php if (!is_null($row['episodeNo'])): ?>
					<td class="col-episode-no"><?=e($row['episodeNo'])?>.</td>
					<?php endif; ?>
					<td class="col-thumbnail" data-thumbnailuri="<?=e($row['thumbnailUri']);?>">
						<div class="height-helper"></div>
						<div class="image-container">
							<div class="image-holder zoom-animation"></div>
						</div>
						<?php if (!is_null($row['thumbnailFooter'])): ?>
						<div class="footer">
							<div><?=$row['thumbnailFooter']['isLive']?"Live":"Available"?></div>
							<div><?=e($row['thumbnailFooter']['dateTxt']);?></div>
						</div>
						<?php elseif(!is_null($row['duration'])): ?>
						<div class="duration"><?=e($row['duration']);?></div>
						<?php endif; ?>
						<a class="hyperlink" href="<?=e($row['uri']);?>"></a>
					</td>
					<td class="col-title clearfix">
						<?php if (!is_null($row['playlistName'])): ?>
						<div class="subtitle"><span class="label label-info"><?=e($row['playlistName']);?></span></div>
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
