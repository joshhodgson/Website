<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title"><?=$editing?"Edit":"Create"?> Media</h3>
	</div>
	<div class="panel-body">
		<?=FormHelpers::getFormCheckInput(1, "Enabled", "enabled", $form['enabled'] === "y", $formErrors);?>
		<?=FormHelpers::getFormCheckInput(1, "Promoted", "promoted", $form['promoted'] === "y", $formErrors);?>
		<?=FormHelpers::getFormTxtInput(1, "Name", "name", $form['name'], $formErrors);?>
		<?=FormHelpers::getFormTxtAreaInput(1, "Description (Optional)", "description", $form['description'], $formErrors);?>
		<?=FormHelpers::getFormCheckInput(1, "Email Notifications Enabled", "email-notifications-enabled", $form['email-notifications-enabled'] === "y", $formErrors);?>
		<?=FormHelpers::getFormCheckInput(1, "Likes Enabled", "likes-enabled", $form['likes-enabled'] === "y", $formErrors);?>
		<?=FormHelpers::getFormCheckInput(1, "Comments Enabled (Disabling This Will Delete Any Existing Comments)", "comments-enabled", $form['comments-enabled'] === "y", $formErrors);?>
		<?=FormHelpers::getFormUploadInput(1, $coverImageUploadPointId, "Cover Image (Optional) (Should Be 940x150)", "cover-image-id", $form['cover-image-id'], $formErrors, $additionalForm['coverImageFile']['name'], $additionalForm['coverImageFile']['size'], $additionalForm['coverImageFile']['processState'], $additionalForm['coverImageFile']['processPercentage'], $additionalForm['coverImageFile']['processMsg']);?>
		<?=FormHelpers::getFormUploadInput(1, $sideBannersImageUploadPointId, "Side Banners Image (Optional) (Should Be 250x1400)", "side-banners-image-id", $form['side-banners-image-id'], $formErrors, $additionalForm['sideBannersImageFile']['name'], $additionalForm['sideBannersImageFile']['size'], $additionalForm['sideBannersImageFile']['processState'], $additionalForm['sideBannersImageFile']['processPercentage'], $additionalForm['sideBannersImageFile']['processMsg']);?>
		<?=FormHelpers::getFormUploadInput(1, $sideBannersFillImageUploadPointId, "Side Banners Fill Image (Optional) (Should Be 250x1400)", "side-banners-fill-image-id", $form['side-banners-fill-image-id'], $formErrors, $additionalForm['sideBannersFillImageFile']['name'], $additionalForm['sideBannersFillImageFile']['size'], $additionalForm['sideBannersFillImageFile']['processState'], $additionalForm['sideBannersFillImageFile']['processPercentage'], $additionalForm['sideBannersFillImageFile']['processMsg']);?>
		<?=FormHelpers::getFormUploadInput(1, $coverArtUploadPointId, "Cover Art (Optional) (Should Be 16:9)", "cover-art-id", $form['cover-art-id'], $formErrors, $additionalForm['coverArtFile']['name'], $additionalForm['coverArtFile']['size'], $additionalForm['coverArtFile']['processState'], $additionalForm['coverArtFile']['processPercentage'], $additionalForm['coverArtFile']['processMsg']);?>
		<?=FormHelpers::getFormDateInput(1, "Scheduled Publish/Live Time (Optional)", "publish-time", $form['publish-time'], $formErrors);?>
		<?=FormHelpers::getFormGroupStart("credits", $formErrors);
		?><label class="control-label">Credits</label><div class="form-control form-credits" data-initialdata="<?=e($additionalForm['creditsInitialData'])?>"></div><?php
		echo(FormHelpers::getFormHiddenInput(1, "credits", $additionalForm['creditsInput']));
		echo(FormHelpers::getErrMsgHTML($formErrors, "credits"));?></div>
		<div class="panel-group custom-accordian" data-grouptogether="0">
			<div class="panel panel-default vod-panel">
				<div class="panel-heading">
					<h4 class="panel-title">Video On Demand</h4>
				</div>
				<div class="panel-collapse collapse <?=$form['vod-added']==="1"?"in":""?>">
					<div class="panel-body">
						<div class="enabled-input hidden"><?=FormHelpers::getFormHiddenInput(1, "vod-added", $form['vod-added']);?></div>
						<div class="disabled-container">
							<button class="btn btn-primary enable-button">Add Video On Demand</button>
						</div>
						<div class="enabled-container">
							<div class="form-group">
								<button class="btn btn-default disable-button">Remove Video On Demand</button>
							</div>
							<?=FormHelpers::getFormCheckInput(1, "Enabled", "vod-enabled", $form['vod-enabled']==="y", $formErrors);?>
							<?=FormHelpers::getFormUploadInput(1, $vodVideoUploadPointId, "Video", "vod-video-id", $form['vod-video-id'], $formErrors, $additionalForm['vodVideoFile']['name'], $additionalForm['vodVideoFile']['size'], $additionalForm['vodVideoFile']['processState'], $additionalForm['vodVideoFile']['processPercentage'], $additionalForm['vodVideoFile']['processMsg']);?>
							<?=FormHelpers::getFormDateInput(1, "Time Recorded (Optional) (Must Be Empty When Recording Of Live Stream)", "vod-time-recorded", $form['vod-time-recorded'], $formErrors);?>
							<?=FormHelpers::getFormGroupStart("vod-chapters", $formErrors);
							?><label class="control-label">Chapters</label><div class="form-control form-vod-chapters" data-initialdata="<?=e($additionalForm['vodChaptersInitialData'])?>"></div><?php
							echo(FormHelpers::getFormHiddenInput(1, "vod-chapters", $additionalForm['vodChaptersInput']));
							echo(FormHelpers::getErrMsgHTML($formErrors, "vod-chapters"));?></div>
						</div>
					</div>
				</div>
			</div>
			<div class="panel panel-default live-stream-panel">
				<div class="panel-heading">
					<h4 class="panel-title">Live Stream</h4>
				</div>
				<div class="panel-collapse collapse <?=$form['stream-added']==="1"?"in":""?>">
					<div class="panel-body">
						<div class="enabled-input hidden"><?=FormHelpers::getFormHiddenInput(1, "stream-added", $form['stream-added']);?></div>
						<div class="disabled-container">
							<button class="btn btn-primary enable-button">Add Live Stream</button>
						</div>
						<div class="enabled-container">
							<div class="form-group">
								<button class="btn btn-default disable-button">Remove Live Stream</button>
							</div>
							<?php if ($hasDvrRecording): ?>
							<div class="form-group remove-dvr-recording-btn-container" data-ajax-remove-uri="<?=e($dvrRecordingRemoveUri);?>">
								<div class="form-control">
									<button class="btn btn-danger remove-btn">Remove DVR Recording</button>
								</div>
							</div>
							<?php endif; ?>
							<?=FormHelpers::getFormCheckInput(1, "Enabled", "stream-enabled", $form['stream-enabled']==="y", $formErrors);?>
							<?=FormHelpers::getButtonGroupInput(1, "Current Status", "stream-state", $form['stream-state'], $formErrors, true, $additionalForm['streamStateButtonsData']);?>
							<?=FormHelpers::getFormCheckInput(1, "Being Recorded For VOD", "stream-being-recorded", $form['stream-being-recorded']==="y", $formErrors);?>
							<?=FormHelpers::getFormTxtAreaInput(1, "Information Message (Shown When Not Live) (Optional)", "stream-info-msg", $form['stream-info-msg'], $formErrors);?>
							<?=FormHelpers::getFormSelectInput(1, "Stream", "stream-stream-id", $form['stream-stream-id'], $streamOptions, $formErrors);?>
							<?=FormHelpers::getFormTxtInput(1, "External Stream Page Url (Optional)", "stream-external-stream-url", $form['stream-external-stream-url'], $formErrors);?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?=FormHelpers::getFormGroupStart("related-items", $formErrors);
		?><label class="control-label">Related Media Items</label><div class="form-control form-related-items" data-initialdata="<?=e($additionalForm['relatedItemsInitialData'])?>"></div><?php
		echo(FormHelpers::getFormHiddenInput(1, "related-items", $additionalForm['relatedItemsInput']));
		echo(FormHelpers::getErrMsgHTML($formErrors, "related-items"));?></div>
		
	</div>
	<div class="panel-footer clearfix">
		<div class="pull-left">
			<?=FormHelpers::getFormSubmitButton(1, ($editing?"Update":"Create")." Media", "", true, "");?>
		</div>
		<div class="pull-right">
			<a type="button" class="btn btn-default" data-confirm="Are you sure you want to cancel?" href="<?=e($cancelUri)?>">Cancel</a>
		</div>
	</div>
</div>