( function ( $ ) {

	const BaseView = require( './Base' );

	module.exports = BaseView.extend( {
		render: function () {
			this.$el.append( this.getHtml() );
			return this;
		},

		getHtml: function () {
			let theme_class = TD_Notification.notify_class ? TD_Notification.notify_class : '';
			let html = '<div class="tve-notification notification-'+ theme_class +'">' +
				'<div class="icon icon-'+ theme_class +'">' +
				'<svg class="tve-circle-close type-' + this.model.get('type') + '" xmlns="http://www.w3.org/2000/svg">><use xlink:href="#icon-notification-type-' + this.model.get('type') + '"></use></svg>' +
				'</div>' +
				'<div class="body">' +
				'<div class="title title-'+ theme_class +'">' +
				'<div>' + this.model.get('title') + '</div>';

			if (this.model.getDate()) {
				html += '<div class="date date-'+ theme_class +'">' + this.model.getDate() + '</div>';
			}

			html += '</div>' +
				'<div class="notification-content notification-content-'+ theme_class +'">' + this.addMediaPlayer(this.model.get('content')) + '</div>' +
				'<div class="tvd-notification-actions">';

			if ( this.model.get( 'button1_action' )) {
				html += '<a type="button" class="tve-dash-button notify-primary notify-primary-'+ theme_class +'" href="' + this.model.get( 'button1_action' ) + '" target="_blank" rel="noopener noreferrer">' +  this.model.get( 'button1_label' ) + '</a>';
			}

			if ( this.model.get( 'button2_action' ) ) {
				html += '<a type="button" class="tve-dash-button notify-secondary notify-secondary-'+ theme_class +'" href="' + this.model.get( 'button2_action' ) + '" target="_blank" rel="noopener noreferrer">' + this.model.get( 'button2_label' ) + '</a>';
			}

			if ( this.model.get('dismissed') != 1 ) {
				html += '<span class="dismiss-notification dismiss-notification-'+ theme_class +'" data-id="' + this.model.get('notification_id') + '">Dismiss</span>';
			}

			html += '</div>' +
				'</div>' +
				'</div>';

			return html;

		},

		addMediaPlayer: function (content) {
			content = content.replace(/&nbsp;/g, '<br>');

			// Check for direct video links (mp4, webm, ogg)
			const videoRegex = /(https?:\/\/\S+\.(mp4|webm|ogg))/gi;
			if (videoRegex.test(content)) {
				content = content.replace(videoRegex, (match, videoUrl) => {
					return `<div class="notification-content-video-container"><video class="custom-video-player" controls>
                        <source src="${videoUrl}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video></div>`;
				});
			}

			// Check for YouTube links
			const youtubeRegex = /(https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]+|https?:\/\/youtu\.be\/[\w-]+)/gi;
			if (youtubeRegex.test(content)) {
				content = content.replace(youtubeRegex, (match, youtubeUrl) => {
					const urlParams = new URLSearchParams(new URL(youtubeUrl).search);
					const videoId = urlParams.get('v') || youtubeUrl.split('/').pop();
					const embedUrl = `https://www.youtube.com/embed/${videoId}`;
					return `<div class="notification-content-video-container"><iframe class="custom-iframe" src="${embedUrl}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>`;
				});
			}

			const youtubeFigureRegex = /<figure[^>]*>\s*<div[^>]*>\s*(https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]+|https?:\/\/youtu\.be\/[\w-]+)\s*<\/div>\s*<\/figure>/gi;
			content = content.replace(youtubeFigureRegex, (match, youtubeUrl) => {
				const urlParams = new URLSearchParams(new URL(youtubeUrl).search);
				const videoId = urlParams.get('v') || youtubeUrl.split('/').pop();
				const embedUrl = `https://www.youtube.com/embed/${videoId}`;
				return `<div class="notification-content-video-container"><iframe class="custom-iframe" src="${embedUrl}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>`;
			});


			// Check for GIF images inside <a> tags
			const gifInAnchorRegex = /<a[^>]*>\s*(https?:\/\/\S+\.(gif))\s*<\/a>/gi;
			if (gifInAnchorRegex.test(content)) {
				content = content.replace(gifInAnchorRegex, (match, gifUrl) => {
					return `<div class="notification-content-image-container"><img class="custom-image-player" src="${gifUrl}" alt="GIF"></div>`;
				});
			}

			// Check for image links (jpg, jpeg, png, gif)
			const imageRegex = /(https?:\/\/\S+\.(jpg|jpeg|png|gif))/gi;
			if (content.includes('<img')) {
				return content;
			}
			if (imageRegex.test(content)) {
				content = content.replace(imageRegex, (match, imageUrl) => {
					return `<div class="notification-content-image-container"><img class="custom-image-player" src="${imageUrl}" alt="Image"></div>`;
				});
			}

			const htmlTagRegex = /<\/?[a-z][\s\S]*>/i;
			if (!htmlTagRegex.test(content)) {
				content = content.replace(/\n/g, '<br>');
			}

			return content;
		}
	} );

} )( jQuery );
