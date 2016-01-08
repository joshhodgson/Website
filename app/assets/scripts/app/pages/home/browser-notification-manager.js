define([
	"jquery",
	"./notification-bar",
	"./notification-priorities",
	"../../notification-service",
	"../../page-data",
	"../../service-worker",
	"../../helpers/ajax-helpers",
], function($, NotificationBar, NotificationPriorities, NotificationService, PageData, ServiceWorker, AjaxHelpers) {
	var N = ("Notification" in window) ? window.Notification : null;
	if (!N || !N.requestPermission) {
		// html5 browser notifications not supported
		return;
	}

	if (needPermission() && !canRequestPermission()) {
		// user denied notifications
		return;
	}

	var $permissionRequestBar = null;
	var soundUrl = PageData.get("assetsBaseUrl")+"assets/audio/notification.mp3";
	var $soundFX = null;

	var requestBarHandle = null;
	if (needPermission() && canRequestPermission()) {
		requestBarHandle = NotificationBar.createNotification(getRequestBarEl(), NotificationPriorities.notificationsPermissionRequest);
	}
	else {
		onHaveNotificationsPermission();
	}

	function onPermissionGranted() {
		requestBarHandle.remove();
		setTimeout(function() {
			createNotification("Notifications Enabled", "Thanks for letting us send you notifications.");
		}, 1000);
		onHaveNotificationsPermission();
	}

	function onPermissionDenied() {
		requestBarHandle.remove();
	}

	function needPermission() {
		return N && N.permission && N.permission !== 'granted';
	}

	// permission request dialog will only be shown if the user hasn't explicitly denied permission
	// ie "default" state
	function canRequestPermission() {
		return N && N.permission && N.permission === 'default';
	}

	function requestPermission(grantedCallback, deniedCallback) {
		N.requestPermission(function(perm) {
			if (perm === "granted") {
				grantedCallback && grantedCallback();
			}
			else if (perm === "denied") {
				deniedCallback && deniedCallback();
			}
		});
	}

	function onHaveNotificationsPermission() {
		configurePushNotifications().then(function() {
			// push notifications enabled
			// the service worker will trigger notifications
			// and handle incoming events (even when site not open)
			// so nothing else to do
		}).catch(function() {
			// push notifications not in use. use the NotificationService (socketio) events instead
			listenForEvents();
		});
	}

	// gets the current push subscription,
	// attempting to make one first if there isn't one
	function getPushSubscription() {
		return new Promise(function(resolve, reject) {
			ServiceWorker.getPushSubscription().then(function(subscription) {
				resolve(subscription);
			}).catch(function() {
				// no subscription.
				// attempt to get one
				ServiceWorker.subscribeToPush().then(function(subscription) {
					// got a subscription now
					resolve(subscription)
				}).catch(function() {
					reject();
				});
			});
		});
	}

	// resolves if a push subscription is created,
	// and push notifications are supported.
	function configurePushNotifications() {
		return new Promise(function(resolve, reject) {
			if (!("localStorage" in window)) {
				reject();
				return;
			}
			localStorage.setItem('notificationsUrl', PageData.get("notificationsUrl"));
			localStorage.setItem('notificationDefaultIconUrl', PageData.get("assetsBaseUrl")+"assets/img/notification-icon.png");
			
			// see if we have a push subscription
			getPushSubscription().then(function(subscription) {
				// there is a push subscription
				// send it to the server so it can use it push events to
				sendPushSubscriptionToServer(subscription).then(function() {
					resolve();
				}).catch(function() {
					// push notification subscription failed to be updated on the server.
					// If the server doesn't have this url it can't send notifications.
					reject();
				});
			}).catch(function() {
				reject();
			});
		});
	}

	function sendPushSubscriptionToServer(subscription) {
		return new Promise(function(resolve, reject) {
			var sessionId = PageData.get("sessionId");
			var endpointUrl = subscription.endpoint;

			// if the url and session id hasn't changed, don't make the request
			// again as the server will already have the information
			if ("localStorage" in window) {
				var updateNeeded = true;
				try {
					var oldSessionId = localStorage.getItem('pushSubscriptionSessionId');
					var oldUrl = localStorage.getItem('pushSubscriptionEndpointUrl');
					var urlUpdateTime = localStorage.getItem('pushSubscriptionEndpointUrlUpdateTime');
					if (oldSessionId === sessionId && oldUrl === endpointUrl && urlUpdateTime && urlUpdateTime >= Date.now()-600000) {
						// the server already has the url and it has been updated in the last 10 minutes
						// it is important to update occasionally as this request is the only point point to find out if push notifications are disabled
						// if push notifications are disabled the update request will return a 404
						updateNeeded = false;
					}
				}
				catch(e) {}
				if (!updateNeeded) {
					resolve();
					return;
				}
			}

			$.ajax(PageData.get("registerPushNotificationEndpointUrl"), {
				cache: false,
				dataType: "json",
				headers: AjaxHelpers.getHeaders(),
				data: {
					csrf_token: PageData.get("csrfToken"),
					url: endpointUrl
				},
				type: "POST"
			}).always(function(data, textStatus, jqXHR) {
				if (jqXHR.status === 200 && data.success) {
					if ("localStorage" in window) {
						try {
							localStorage.setItem('pushSubscriptionSessionId', sessionId);
							localStorage.setItem('pushSubscriptionEndpointUrl', endpointUrl);
							localStorage.setItem('pushSubscriptionEndpointUrlUpdateTime', Date.now());
						}
						catch(e) {}
					}
					resolve();
				}
				else {
					reject();
				}
			});
		});
	}

	function listenForEvents() {
		NotificationService.on("notification", function(data) {
			createNotification(data.title, data.body, data.url, data.duration, data.iconUrl);
		});
	}

	// returns true if push notifications are supported and in use
	// ie there is a web worker running which is listening for push events
	// the web worker will handle spawning notifications
	function pushNotificationsInUse() {
		return new Promise(function(resolve) {
			ServiceWorker.pushNotificationsEnabled().then(function(enabled) {
				resolve(enabled);
			}).catch(function() {
				resolve(false);
			});
		});
	}

	function createNotification(title, message, link, duration, iconUrl) {
		duration = duration || 8000;
		iconUrl = iconUrl || PageData.get("assetsBaseUrl")+"assets/img/notification-icon.png";
		
		var n = new N(title, {
			lang: "EN",
			body: message,
			icon: iconUrl
		});

		var timerId = null;

		if (link) {
			n.addEventListener("click", function() {
				window.location.href = link;
				n.close();
			});
		}

		n.addEventListener("show", function() {
			if (!$soundFX) {
				$soundFX = $("<audio />").attr("volume", 1).attr("src", soundUrl).attr("preload", "auto").prop("autoplay", true).hide();
				$("body").append($soundFX);
			}
			else {
				$soundFX[0].currentTime = 0;
				$soundFX[0].play();
			}
			timerId = setTimeout(function() {
				timerId = null;
				n.close();
			}, duration);
		});

		n.addEventListener("close", function() {
			if (timerId !== null) {
				clearTimeout(timerId);
			}
		});
	}

	function getRequestBarEl() {
		if ($permissionRequestBar) {
			return $permissionRequestBar;
		}
		$permissionRequestBar = $("<div />").addClass("browser-notification-permission-request-bar");
		var $container = $("<div />").addClass("container");
		var $glyph = $("<span />").addClass("glyphicon glyphicon-bullhorn icon");
		var $line1 = $("<span />").text("Click to enable browser notifications.");
		$container.append($glyph);
		$container.append($line1);
		$permissionRequestBar.append($container);

		$permissionRequestBar.click(function() {
			requestPermission(onPermissionGranted, onPermissionDenied);
		});
		return $permissionRequestBar;
	}
});