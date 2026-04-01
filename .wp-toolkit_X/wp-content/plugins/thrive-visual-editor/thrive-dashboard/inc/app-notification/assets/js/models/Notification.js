module.exports = Backbone.Model.extend({
	idAttribute: 'id',
	defaults: {
		active: {},
		dismissed: {},
	},
	getDate: function () {
		if (this.has('start') && this.get('start')) {
			let startDate = new Date(this.get('start'));

			let currentDate = new Date(TD_Notification?.date_time_now);
			let timeDiff = currentDate.getTime() - startDate.getTime();

			// Convert the difference to months, days, hours, or minutes
			let yearsDiff = Math.floor(timeDiff / (1000 * 3600 * 24 * 365));
			if (yearsDiff > 0) {
				return yearsDiff + (yearsDiff === 1 ? " year ago" : " years ago");
			}

			let monthsDiff = Math.floor(timeDiff / (1000 * 3600 * 24 * 30));
			if (monthsDiff > 0) {
				return monthsDiff + (monthsDiff === 1 ? " month ago" : " months ago");
			}

			let daysDiff = Math.floor(timeDiff / (1000 * 3600 * 24));
			if (daysDiff > 0) {
				return daysDiff + (daysDiff === 1 ? " day ago" : " days ago");
			}

			let hoursDiff = Math.floor(timeDiff / (1000 * 3600));
			if (hoursDiff > 0) {
				return hoursDiff + (hoursDiff === 1 ? " hour ago" : " hours ago");
			}

			let minsDiff = Math.floor(timeDiff / (1000 * 60));
			if (minsDiff > 0) {
				return minsDiff + (minsDiff === 1 ? " minute ago" : " minutes ago");
			}

			return "just now";
		} else {
			return "";
		}
	}
});

