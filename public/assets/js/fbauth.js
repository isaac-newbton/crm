let access_token = null;
window.fbAsyncInit = function () {
	FB.init({ appId: '235215051190155', xfbml: true, version: 'v6.0' });
};

// todo: try fetch the access token from the backend somehow
console.log(access_token)
if (access_token) {
	const p = document.createElement('p');
	p.innerText = `Currently authorized - Expires [expiredate]`;
	document.getElementById('root').appendChild(p);
} else {
	// otherwise present the fb login button
	const button = document.createElement('button');
	const list = document.createElement('ul');

	button.addEventListener('click', myFacebookLogin);
	button.innerText = 'Login with Facebook';
	list.setAttribute('id', 'list');

	document.getElementById('root').appendChild(button);
	document.getElementById('root').appendChild(list);
	(function (d, s, id) {
		var js,
			fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) {
			return;
		}
		js = d.createElement(s);
		js.id = id;
		js.src = 'https://connect.facebook.net/en_US/sdk.js';
		fjs.parentNode.insertBefore(js, fjs);
	})(document, 'script', 'facebook-jssdk');

	function subscribeApp(page_id, page_access_token) {
		console.log('Subscribing page to app! ' + page_id);
		FB.api(
			'/' + page_id + '/subscribed_apps',
			'post',
			{
				access_token: page_access_token,
				subscribed_fields: ['feed'],
			},
			function (response) {
				console.log('Successfully subscribed page', response);
			},
		);
	}

	// Only works after `FB.init` is called
	function myFacebookLogin() {
		FB.login(
			function (response) {
				console.log('Successfully logged in', response);
				/**
				 * ! todo: resonse.accessToken should be stored somewhere (env?), this is what is used to fetch lead data from the leadgen object
				 */

				FB.api('/me/accounts', function (response) {
					console.log('Successfully retrieved pages', response);
					var pages = response.data;
					var ul = document.getElementById('list');
					for (var i = 0, len = pages.length; i < len; i++) {
						var page = pages[i];
						var li = document.createElement('li');
						var a = document.createElement('a');
						a.href = '#';
						a.onclick = subscribeApp.bind(
							this,
							page.id,
							page.access_token,
						);
						a.innerHTML = page.name;
						li.appendChild(a);
						ul.appendChild(li);
					}
				});
			},
			{
				scope: 'manage_pages',
			},
		);
	}
}
