// takes in a Player Component and handles all communication with it.
// has knowledge of the requests to the server and the responses returned

// communicates with a PlayerControllerQualitiesHander to manage quality selection.
// also manages likes and view count

var PlayerController = null;

$(document).ready(function() {
	
	// qualities handler needs to be an object with the following methods:
	// - getChosenQualityId()
	// 		should return the current chosen quality id
	// - setAvailableQualities(qualities)
	//		called with an array of {id, name}
	//		will be an empty array in the case of there being no video
	
	PlayerController = function(playerInfoUri, registerViewCountUri, registerLikeUri, qualitiesHandler) {
		
		var self = this;
		
		// destroys the controller and player and prevents any future requests
		this.destroy = function() {
			destroyed = true;
			if (timerId !== null) {
				clearTimeout(timerId);
			}
			playerComponent.destroy();
		};
		
		this.getPlayerComponentEl = function() {
			if (playerComponent !== null) {
				return playerComponent.getEl();
			}
			return null;
		};
		
		this.getNumLikes = function() {
			return numLikes;
		};
		
		this.getNumDislikes = function() {
			return numDisikes;
		};
		
		// returns 'like' if person liked this, 'dislike' if disliked, or null if neither
		this.getLikeType = function() {
			return likeType;
		};
		
		this.registerLike = function(type, callback) {
			registerLike(type, callback);
		};
		
		this.getStreamViewCount = function() {
			return streamViewCount;
		};
		
		this.getVodViewCount = function() {
			return vodViewCount;
		};
		
		this.getViewCount = function() {
			if (cachedData !== null) {
				var count = 0;
				if (self.getStreamViewCount() !== null) {
					count += self.getStreamViewCount();
				}
				if (self.getVodViewCount() !== null) {
					count += self.getVodViewCount();
				}
				return count;
			}
			return null;
		};
		
		this.getPlayerType = function() {
			return currentPlayerType;
		}

		var destroyed = false;
		var timerId = null;
		var playerComponent = null;
		var currentPlayerType = null;
		var cachedData = null;
		var vodViewCount = null;
		var streamViewCount = null;
		var numLikes = null;
		var numDislikes = null;
		var likeType = null; // "like", "dislike" or null
		
		$(qualitiesHandler).on("chosenQualityChanged", function() {
			updatePlayer();
		});
		
		// kick it off
		update();
		
		function update() {
			jQuery.ajax(playerInfoUri, {
				cache: false,
				dataType: "json",
				data: {
					csrf_token: getCsrfToken()
				},
				type: "POST"
			}).always(function(data, textStatus, jqXHR) {
				if (jqXHR.status === 200) {
					cachedData = data;
					updatePlayer();
					updateViewCounts();
					updateLikes();
				}
				
				if (!destroyed) {
					// schedule update again in 15 seconds
					timerId = setTimeout(update, 15000);
				}
			});
		}
		
		function updatePlayer() {
			if (cachedData === null) {
				return;
			}
			data = cachedData;
			if (playerComponent === null) {
				playerComponent = new PlayerComponent(data.coverUri);
				$(self).triggerHandler("playerComponentElAvailable");
				$(playerComponent).on("play", function() {
					registerViewCount();
				});
			}
			playerComponent.setStartTime(data.scheduledPublishTime !== null ? new Date(data.scheduledPublishTime) : null);
			playerComponent.setCustomMsg(data.streamInfoMsg);
			playerComponent.showStreamOver(data.streamState === 3);
			playerComponent.showVodAvailableShortly(data.streamState === 3 && data.availableOnDemand);
			if (data.streamState === 2) {
				// stream should be live
				setPlayerType("live");
			}
			else if (data.vodLive) {
				// video should be live
				setPlayerType("vod");
				var qualities = [];
				var qualityIds = [];
				for (var i=0; i<data.videoUris.length; i++) {
					var videoUri = data.videoUris[i];
					qualities.push({
						id:		videoUri.quality.id,
						name:	videoUri.quality.name
					});
					qualityIds.push(videoUri.quality.id);
				}
				qualitiesHandler.setAvailableQualities(qualities);
				var chosenQualityId = qualitiesHandler.getChosenQualityId();
				var videoUri = data.videoUris[qualityIds.indexOf(chosenQualityId)];
				playerComponent.setPlayerUris(videoUri.uris);
			}
			else {
				setPlayerType("ad");
			}
			
			playerComponent.render();
		}
		
		function setPlayerType(type) {
			if (type !== "live" && type !== "vod" && type !== "ad") {
				throw "Type must be either 'live', 'vod' or 'ad'.";
			}
			
			if (currentPlayerType === type) {
				// not changed
				return;
			}
			currentPlayerType = type;
			
			if (type === "ad") {
				playerComponent.showPlayer(false);
			}
			else {
				playerComponent.setPlayerType(type).showPlayer(true);
			}
			$(self).triggerHandler("playerTypeChanged");
		}
		
		function updateViewCounts() {
			var changed = false;
			var vodCountChanged = false;
			var streamCountChanged = false;
			vodCountChanged = vodViewCount !== cachedData.vodViewCount;
			vodViewCount = cachedData.vodViewCount;
			streamCountChanged = streamViewCount !== cachedData.streamViewCount;
			streamViewCount = cachedData.streamViewCount;
			if (vodCountChanged) {
				$(self).triggerHandler("vodViewCountChanged");
			}
			if (streamCountChanged) {
				$(self).triggerHandler("streamViewCountChanged");
			}
			if (vodCountChanged || streamCountChanged) {
				$(self).triggerHandler("viewCountChanged");
			}
		}
		
		function updateLikes() {
			var changed = numLikes !== cachedData.numLikes;
			numLikes = cachedData.numLikes;
			if (changed) {
				$(self).triggerHandler("numLikesChanged");
			}
			var changed = numDislikes !== cachedData.numDislikes;
			numDislikes = cachedData.numDislikes;
			if (changed) {
				$(self).triggerHandler("numDisikesChanged");
			}
			var changed = likeType !== cachedData.likeType;
			likeType = cachedData.likeType;
			if (changed) {
				$(self).triggerHandler("likeTypeChanged");
			}
		}
		
		function registerViewCount() {
			jQuery.ajax(registerViewCountUri, {
				cache: false,
				dataType: "json",
				data: {
					csrf_token: getCsrfToken(),
					type: self.getPlayerType()
				},
				type: "POST"
			});
		}
		
		function registerLike(type, callback) {
			if (type !== "like" && type !== "dislike" && type !== "reset") {
				throw "Type must be 'like', 'dislike' or 'reset'.";
			}
			if (type === "like" && self.getLikeType() === "like" ||
				type === "dislike" && self.getLikeType() === "dislike" ||
				type === "reset" && self.getLikeType() === null) {
				// no change
				if (callback) {
					callback(true);
				}
				return;
			}
			var previousLikeType = self.getLikeType();
			jQuery.ajax(registerLikeUri, {
				cache: false,
				dataType: "json",
				data: {
					csrf_token: getCsrfToken(),
					type: type
				},
				type: "POST"
			}).always(function(data, textStatus, jqXHR) {
				if (jqXHR.status === 200) {
					var success = data.success;
					if (success) {
						// like has changed on server. update cached versions accordingly
						if (previousLikeType === self.getLikeType()) {
							// make sure cache hasn't already been updated before this response was returned
							var likesChanged = false;
							var dislikesChanged = false;
							if (previousLikeType === null) {
								if (type === "like") {
									numLikes++;
									likesChanged = true;
								}
								else if (type === "dislike") {
									numDislikes++;
									dislikesChanged = true;
								}
							}
							else if (previousLikeType === "like") {
								if (type === "dislike") {
									numLikes--;
									likesChanged = true;
									numDislikes++;
									dislikesChanged = true;
								}
								else if (type === "reset") {
									numLikes--;
									likesChanged = true;
								}
							}
							else if (previousLikeType === "dislike") {
								if (type === "like") {
									numLikes++;
									likesChanged = true;
									numDislikes--;
									dislikesChanged = true;
								}
								else if (type === "reset") {
									numDisikes--;
									dislikesChanged = true;
								}
							}
							if (type === "like") {
								likeType = "like";
							}
							else if (type === "dislike") {
								likeType = "dislike";
							}
							else if (type === "reset") {
								likeType = null;
							}
							$(self).triggerHandler("likeTypeChanged");
							
							if (likesChanged) {
								$(self).triggerHandler("numLikesChanged");
							}
							if (dislikesChanged) {
								$(self).triggerHandler("numDisikesChanged");
							}
						}
					}
					if (callback) {
						callback(success);
					}
				}
				else {
					if (callback) {
						callback(false);
					}
				}
			});
		}
		
	};
});