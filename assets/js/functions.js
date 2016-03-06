var snippets = document.querySelectorAll('.message-snippet');
for (var i=0;i<snippets.length;i++) {
	var tar = document.getElementById( snippets[i].getAttribute('id') );
	tar.addEventListener('click', function() {
		var url = typeof bbpm_messages_base !== undefined ? bbpm_messages_base : '';
		url += this.getAttribute('data-slug');
		window.location.href = url;
	}, false);
}


var toggleHelp = document.querySelectorAll('.bbpm-toggle-help');
for (var i=0;i<toggleHelp.length;i++) {

	toggleHelp[i].addEventListener('click', function() {
		
		var target = document.getElementsByClassName('bbpm-single-top')[0];
		var _classes = null !== target ? target.getAttribute('class') : '';

		if( _classes.indexOf(' help') > 0 ) {
			target.setAttribute('class', _classes.replace(' help', ''));
		} else {
			target.setAttribute('class', _classes + ' help');
		}

	}, false);

}

var messageTools = document.querySelectorAll('.message-tools > a');
for (var i=0;i<messageTools.length;i++) {

	messageTools[i].addEventListener('click', function(e) {

		var _do = this.href.substring( this.href.indexOf('?do=') ).replace('?do=', '');

		switch( _do ) {

			case 'delete':
				var _conf = _bbpm_conf.del_c;
				break;

			case 'block':
				var _conf = _bbpm_conf.block;
				break;

			case 'unblock':
				var _conf = _bbpm_conf.unblock;
				break;

			default:
				var _conf = false;

		}

		if( typeof _conf == 'string' ) {
			var confirmed = confirm( _conf );
			if( ! confirmed )
				e.preventDefault();
		}

	}, false);

}

var deleteMessage = document.querySelectorAll('.single-pm a.delete-message');
for (var i=0;i<deleteMessage.length;i++) {

	deleteMessage[i].addEventListener('click', function(e) {

		var confirmed = confirm( _bbpm_conf.del_m );
		if( ! confirmed )
			e.preventDefault();

	}, false);

}

var _refresh = document.getElementById('__refresh');
if( null !== _refresh ) {
	_refresh.addEventListener('click', function(event) {
		event.preventDefault();
		window.location.href = window.location.href;
	}, false);
}

window.addEventListener('load', function() {

	var _msg_tools = document.getElementsByClassName('bbpm')[0];
	if( _msg_tools > '' )
		_msg_tools.setAttribute( 'class', _msg_tools.getAttribute('class').replace(' no-js', '') );

	if( window.location.href.indexOf('?done=') > 0 ) {
		if( document.getElementsByClassName('bbpm')[0] > '' ) {
			var _url = window.location.href;
			window.history.pushState("", "", _url.substring(0, _url.indexOf( '?done' )) );
		}
	}

}, false);

function decodeTitle(string) {
	var _t = document.createElement('textarea');
	_t.innerHTML = string;
	var string = _t.value;
	_t.remove();
	return string;
}

var _close_notice = document.querySelector('.bbpm .notice > span');
if( _close_notice > '' ) {
	_close_notice.addEventListener('click', function() {
		if( null !== this.parentElement ) {
			this.parentElement.remove();
			var _url = window.location.href;
			if( _url.indexOf('?done=') > 0 )
				window.history.pushState("", "", _url.substring(0, _url.indexOf( '?done' )) );
		}
	}, false);
}