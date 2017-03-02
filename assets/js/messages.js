(function() {
	"use strict";

	var addEvent = function(callback) {
		if(window.attachEvent) {
	        window.attachEvent('onload', callback);
	    } else {
	        if(window.onload) {
	            var curronload = window.onload;
	            var newonload = function(evt) {
	                curronload(evt);
	                callback(evt);
	            };
	            window.onload = newonload;
	        } else {
	            window.onload = callback;
	        }
	    }
	}, links, chatFakeLinkers = [
		'.bbpm-chats .bbpm-chat .bbpm-details .bbpm-excerpt',
		'.bbpm-chats .bbpm-chat .bbpm-details .bbpm-heading',
		'.bbpm-chats .bbpm-chat .bbpm-icon img'
	];

	addEvent(function(){
		for ( var i in chatFakeLinkers ) {
			links = document.querySelectorAll(chatFakeLinkers[i]);
			for ( var link in links ) {
				if ( links.hasOwnProperty(link) ) {
					if ( null !== links[link] ) {
						links[link].onclick = function() {
							window.location.href = (
								BBP_MESSAGES.messages_base + this.dataset.chatid + '/'
							);
						}
					}
				}
			}
		}
	});

	addEvent(function(){
		var chatDeleteLabel = document.querySelector('.bbpm-chat-settings #delete-chat')
		  , chatDeleteForm = document.querySelector('.bbpm-chat-settings form.delete-chat-form');
		if ( null !== chatDeleteLabel && null !== chatDeleteForm ) {
			chatDeleteLabel.onclick = function(){
				if ( confirm(chatDeleteForm.dataset.confirm||'Are you sure?') ) {
					if ( chatDeleteForm.getAttribute('data-action') ) {
						chatDeleteForm.setAttribute('action', chatDeleteForm.getAttribute('data-action'));
						chatDeleteForm.removeAttribute('data-action');
					}
					chatDeleteForm.submit();
				}
			}
		}
	});
})();