define([], function() {

	// require asynchronously to not hold up any module that required this
	require([
		"jquery",
		"lib/domReady!",
		"twitter"
	], function($) {

		// attach a twitter timeline to anything with this class
		$(".twitter-timeline-container").each(function() {
			var self = this;
			var widgetId = $(this).attr("data-twitter-widget-id");
			var height = parseInt($(this).attr("data-twitter-widget-height"));
			
			var $timeline = $("<div />");
			$(this).append($timeline);
			twttr.widgets.createTimeline(widgetId, $timeline[0], {
				height: height,
				theme: "light"
			}).then(function(el) {
				// loaded
			});
		});

	});
});